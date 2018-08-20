<?php

namespace App\Observers;

class SendLogObserver
{
    /**
     * Send log created.
     *
     * @param SendLog $send_log
     */
    public function created(SendLog $send_log)
    {
        // Update status for thread if any
        if ($send_log->thread_id && ($send_log->customer_id || ($send_log->user_id && $send_log->user_id == $send_log->thread->user_id)) {
            $send_log->thread->send_status = $send_log->status;
            $send_log->thread->save();
        }
    }
}
