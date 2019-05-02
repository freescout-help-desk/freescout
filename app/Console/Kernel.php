<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Misc\Mail;
use App\Option;

class Kernel extends ConsoleKernel
{
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

        $schedule->command('freescout:update-folder-counters')
            ->hourly();

        $schedule->command('freescout:module-check-licenses')
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

        // Fetch emails from mailboxes
        $fetch_command = $schedule->command('freescout:fetch-emails')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path().'/logs/fetch-emails.log');

        switch (Option::get('fetch_schedule')) {
            case Mail::FETCH_SCHEDULE_EVERY_MINUTE:
                $fetch_command->everyMinute();
                break;
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
        }

        $schedule = \Eventy::filter('schedule', $schedule);

        // Command runs as subprocess and sets cache mutex. If schedule:run command is killed
        // subprocess does not clear the mutex and it stays in the cache until cache:clear is executed.
        // By default, the lock will expire after 24 hours.

        if (function_exists('shell_exec')) {
            $running_commands = $this->getRunningQueueProcesses();

            if (count($running_commands) > 1) {
                // Stop all queue:work processes.
                // queue:work command is stopped by settings a cache key
                \Cache::forever('illuminate:queue:restart', Carbon::now()->getTimestamp());
                // Sometimes processes stuck and just continue running, so we need to kill them.
                // Sleep to let processes stop.
                sleep(1);
                // Check processes again.
                $worker_pids = $this->getRunningQueueProcesses();
                
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
            }
        }

        $schedule->command('queue:work', Config('app.queue_work_params'))
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path().'/logs/queue-jobs.log');
    }

    /**
     * Get pids of the queue:work processes.
     * 
     * @return [type] [description]
     */
    protected function getRunningQueueProcesses()
    {
        $pids = [];

        try {
            $processes = preg_split("/[\r\n]/", shell_exec("ps aux | grep 'queue:work'"));
            foreach ($processes as $process) {
                preg_match("/^[\S]+\s+([\d]+)\s+/", $process, $m);
                if (!preg_match("/(sh \-c|grep )/", $process) && !empty($m[1])) {
                    $pids[] = $m[1];
                }
            }
        } catch (\Exception $e) {
            // Do nothing
        }
        return $pids;
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
