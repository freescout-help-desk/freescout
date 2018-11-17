<?php
/**
 * Used to tirgger Eventy actions with delay.
 */
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class TriggerAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $action;
    public $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($action, $params)
    {
        $this->action = $action;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $args = $this->params;
        array_unshift($args, $this->action);

        call_user_func_array("\Eventy::action", $args);
    }
}
