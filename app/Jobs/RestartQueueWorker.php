<?php
/**
 * Used to stop current queue:work in order to start a new one.
 * https://github.com/freescout-helpdesk/freescout/issues/2507#issuecomment-1376006247
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RestartQueueWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->delete();
        // register_shutdown_function() is called on exit(),
        // so commands mutexes are removed.
        exit();
    }
}
