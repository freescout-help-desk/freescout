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

        // Command runs as subprocess and sets cache mutex. If schedule:run command is killed
        // subprocess does not clear the mutex and it stays in the cache until cache:clear is executed.
        // By default, the lock will expire after 24 hours.

        // No need
        // So on receiving a kill signal we need to manually remove all mutexes.
        // $pid = getmypid();
        // register_shutdown_function(function () use ($pid, $schedule) {
        //     if ($pid === getmypid()) {
        //         foreach ($schedule->events() as $event) {
        //             if ($event->description) {
        //                 $event->mutex->forget($event);
        //             }
        //         }
        //     }
        // });
        $schedule->command('queue:work', Config('app.queue_work_params'))
            //->everyMinute()
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
