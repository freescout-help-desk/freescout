<?php

namespace App\Listeners;

use App\SendLog;

class SendAutoReply
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event)
    {
        $conversation = $event->conversation;

        if (!$conversation->imported && $conversation->mailbox->auto_reply_enabled) {

            // Send auto reply once in 24h
            $created_at = \Illuminate\Support\Carbon::now()->subDays(1);
            $auto_reply_sent = SendLog::where('customer_id', $conversation->customer_id)
                ->where('mail_type', SendLog::MAIL_TYPE_AUTO_REPLY)
                ->where('created_at', '>', $created_at)
                ->first();

            if ($auto_reply_sent) {
                return;
            }

            \App\Jobs\SendAutoReply::dispatch($conversation, $conversation->threads()->first(), $conversation->mailbox, $conversation->customer)
            ->onQueue('emails');
        }
    }
}
