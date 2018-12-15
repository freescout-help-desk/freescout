<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
        // Remove failed jobs
        $schedule->command('queue:flush')
            ->weekly();

        // Restart processing queued jobs (just in case)
        $schedule->command('queue:restart')
            ->hourly();

        $schedule->command('freescout:fetch-monitor')
            ->everyMinute()
            ->withoutOverlapping();

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
        // $schedule->command('freescout:fetch-emails')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->sendOutputTo(storage_path().'/logs/fetch-emails.log');

        $schedule = \Eventy::filter('schedule', $schedule);

        // Command runs as subprocess and sets cache mutex. If schedule:run command is killed
        // subprocess does not clear the mutex and it stays in the cache until cache:clear is executed.
        // By default, the lock will expire after 24 hours.
        //
        // cache:clear clears the mutex, but sometimes process continues running, so we need to kill it.

        if (function_exists('shell_exec')) {
            $running_commands = 0;

            try {
                $processes = preg_split("/[\r\n]/", shell_exec("ps aux | grep 'queue:work'"));
                foreach ($processes as $process) {
                    preg_match("/^[\S]+\s+([\d]+)\s+/", $process, $m);
                    if (!preg_match("/(sh \-c|grep )/", $process) && !empty($m[1])) {
                        $running_commands++;
                    }
                }
            } catch (\Exception $e) {
                // Do nothing
            }
            if ($running_commands > 1) {
                // queue:work command is stopped by settings a cache key
                \Cache::forever('illuminate:queue:restart', Carbon::now()->getTimestamp());
            }
        }

        $schedule->command('queue:work', Config('app.queue_work_params'))
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path().'/logs/queue-jobs.log');
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
