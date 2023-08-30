<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Misc\Mail;
use App\Option;

class Kernel extends ConsoleKernel
{
    const FETCH_MAX_EXECUTION_TIME = 30; // minutes

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // It is not clear what for this array
        //\App\Console\Commands\CreateUser::class,
    ];

    /**
     * Define the application's command schedule.
     * If --no-interaction flag is set the script will not run 'queue:work' daemon.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Keep in mind that this function is also called on clearing cache.

        // Remove failed jobs
        $schedule->command('queue:flush')
            ->weekly();

        // Restart processing queued jobs (just in case)
        $schedule->command('queue:restart')
            ->hourly();

        $schedule->command('freescout:fetch-monitor')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('freescout:send-monitor')
            // Every 10 minutes.
            ->cron('*/10 * * * *')
            ->withoutOverlapping();

        $schedule->command('freescout:update-folder-counters')
            ->hourly();

        $app_key = config('app.key');
        if ($app_key) {
            $crc = crc32($app_key);
            $schedule->command('freescout:module-check-licenses')
                ->cron((int)($crc % 59).' '.(int)($crc % 23).' * * *');
        }

        // Check if user finished viewing conversation.
        $schedule->command('freescout:check-conv-viewers')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('freescout:clean-send-log')
            ->monthly();

        $schedule->command('freescout:clean-notifications-table')
            ->weekly();

        $schedule->command('freescout:clean-tmp')
            ->daily();

        // Logs monitoring.
        $alert_logs_period = config('app.alert_logs_period');
        if (config('app.alert_logs') && $alert_logs_period) {
            $logs_cron = '';
            switch ($alert_logs_period) {
                case 'hour':
                    $logs_cron = '0 * * * *';
                    break;
                case 'day':
                    $logs_cron = '0 0 * * *';
                    break;
                case 'week':
                    $logs_cron = '0 0 * * 0';
                    break;
                case 'month':
                    $logs_cron = '0 0 1 * *';
                    break;
            }
            if ($logs_cron) {
                $schedule->command('freescout:logs-monitor')
                    ->cron($logs_cron)
                    ->withoutOverlapping();
            }
        }

        $fetch_command_identifier = \Helper::getWorkerIdentifier('freescout:fetch-emails');

        // Kill fetch commands running for too long.
        // In shedule:run this code is executed every time $schedule->command() in this function is executed.
        if ($this->isScheduleRun() && function_exists('shell_exec')) {
            $fetch_command_pids = \Helper::getRunningProcesses($fetch_command_identifier);

            $mutex_name = $schedule->command('freescout:fetch-emails')
                ->skip(function () {
                    return true;
                })
                ->mutexName();
            
            // If there is no cache mutext but there are running fetch commands 
            // it means the mutex had expired after self::FETCH_MAX_EXECUTION_TIME
            // and the existing command(s) is running longer than self::FETCH_MAX_EXECUTION_TIME.
            if (count($fetch_command_pids) > 0 && !\Cache::get($mutex_name)) {
                // Kill freescout:fetch-emails commands running for too long
                shell_exec('kill '.implode(' | kill ', $fetch_command_pids));
            } elseif (count($fetch_command_pids) == 0) {
                // Make sure 'ps' command actually works.
                $ps_works = \Helper::getRunningProcesses('schedule:run');

                if (count($ps_works)) {
                    // Previous freescout:fetch-emails may have been killed or errored and did not remove the mutex.
                    // So here we are forcefully removing the mutex. Otherwise mutex will live for 24 hours.
                    if (\Cache::has($mutex_name)) {
                        \Cache::forget($mutex_name);
                    }
                }
            }
        }

        // Fetch emails from mailboxes
        $fetch_command = $schedule->command('freescout:fetch-emails --identifier='.$fetch_command_identifier)
            // withoutOverlapping() option creates a mutex in the cache 
            // which by default expires in 24 hours.
            // So we are passing an 'expiresAt' parameter to withoutOverlapping() to
            // prevent fetching from not being executed when fetching command by some reason
            // does not remove the mutex from the cache. 
            ->withoutOverlapping($expiresAt = self::FETCH_MAX_EXECUTION_TIME /* minutes */)
            ->sendOutputTo(storage_path().'/logs/fetch-emails.log');

        switch (config('app.fetch_schedule')) {
            case Mail::FETCH_SCHEDULE_EVERY_FIVE_MINUTES:
                $fetch_command->everyFiveMinutes();
                break;
            case Mail::FETCH_SCHEDULE_EVERY_TEN_MINUTES:
                $fetch_command->everyTenMinutes();
                break;
            case Mail::FETCH_SCHEDULE_EVERY_FIFTEEN_MINUTES:
                $fetch_command->everyFifteenMinutes();
                break;
            case Mail::FETCH_SCHEDULE_EVERY_THIRTY_MINUTES:
                $fetch_command->everyThirtyMinutes();
                break;
            case Mail::FETCH_SCHEDULE_HOURLY:
                $fetch_command->Hourly();
                break;
            default:
                $fetch_command->everyMinute();
                break;
        }

        $schedule = \Eventy::filter('schedule', $schedule);

        // If --no-daemonize flag is passed - do not run 'queue:work' daemon.
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if ($arg == '--no-interaction') {
                return;
            }
        }

        // Command runs as subprocess and sets cache mutex. If schedule:run command is killed
        // subprocess does not clear the mutex and it stays in the cache until cache:clear is executed.
        // By default, the lock will expire after 24 hours.

        $queue_work_params = Config('app.queue_work_params');
        // Add identifier to avoid conflicts with other FreeScout instances on the same server.
        $queue_work_params['--queue'] .= ','.\Helper::getWorkerIdentifier();

        // $schedule->command('queue:work') command below has withoutOverlapping() option,
        // which works via special mutex stored in the cache preventing several 'queue:work' to work at the same time.
        // So when the cache is cleared the mutex indicating that the 'queue:work' is running is removed,
        // and the second 'queue:work' command is launched by cron. When `artisan schedule:run` is executed it sees 
        // that there are two 'queue:work' processes running and kills them.
        // After one minute 'queue:work' is executed by cron via `artisan schedule:run` and works in the background.
        if ($this->isScheduleRun() && function_exists('shell_exec')) {
            $running_commands = \Helper::getRunningProcesses();

            if (count($running_commands) > 1) {
                // Stop all queue:work processes.
                // queue:work command is stopped by settings a cache key
                \Helper::queueWorkerRestart();
                // Sometimes processes stuck and just continue running, so we need to kill them.
                // Sleep to let processes stop.
                sleep(1);
                // Check processes again.
                $worker_pids = \Helper::getRunningProcesses();
                
                if (count($worker_pids) > 1) {
                    // Current process also has to be killed, as otherwise it "stucks"
                    // $current_pid = getmypid();
                    // foreach ($worker_pids as $i => $pid) {
                    //     if ($pid == $current_pid) {
                    //         unset($worker_pids[$i]);
                    //         break;
                    //     }
                    // }
                    shell_exec('kill '.implode(' | kill ', $worker_pids));
                }
            } elseif (count($running_commands) == 0) {
                // Make sure 'ps' command actually works.
                $ps_works = \Helper::getRunningProcesses('schedule:run');

                if (count($ps_works)) {
                    // Previous queue:work may have been killed or errored and did not remove the mutex.
                    // So here we are forcefully removing the mutex.
                    $mutex_name = $schedule->command('queue:work', $queue_work_params)
                        ->skip(function () {
                            return true;
                        })
                        ->mutexName();
                    if (\Cache::get($mutex_name)) {
                        \Cache::forget($mutex_name);
                    }
                }
            }
        }

        $schedule->command('queue:work', $queue_work_params)
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path().'/logs/queue-jobs.log');
    }

    /**
     * This function is needed because every time $schedule->command() is executed 
     * the schedule() is executed also.
     */
    public function isScheduleRun()
    {
        if (!\Helper::isConsole()) {
            return true;
        } else {
            return !empty($_SERVER['argv']) && in_array('schedule:run', $_SERVER['argv']);
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        // Swiftmailer uses $_SERVER['SERVER_NAME'] in transport_deps.php
        // to set the host for EHLO command, if it is empty it uses [127.0.0.1].
        // G Suite sometimes rejects emails with EHLO [127.0.0.1].
        if (empty($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = parse_url(config('app.url'), PHP_URL_HOST);
        }

        require base_path('routes/console.php');
    }
}
