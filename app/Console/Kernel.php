<?php

namespace App\Console;

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
        \App\Console\Commands\CreateUser::class,
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
            ->daily();

        // Restart processing queued jobs (just in case)
        $schedule->command('queue:restart')
            ->hourly();

        // Fetch emails from mailboxes
        $schedule->command('freescout:fetch-emails')
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path().'/logs/fetch-emails.log');

        // Command runs as subprocess and sets cache mutex. If schedule:run command is killed
        // subprocess does not clear the mutex and it stays in the cache until cache:clear is executed.
        // By default, the lock will expire after 24 hours.
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
