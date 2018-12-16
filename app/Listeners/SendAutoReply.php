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

        if (/*!$conversation->imported &&*/ $conversation->mailbox->auto_reply_enabled) {

            $thread = $conversation->threads()->first();
            
            // Do not send auto reply to auto responders.
            if ($thread->isAutoResponder()) {
                return;
            }
            // Do not send auto replies to bounces.
            if ($thread->isBounce()) {
                return;
            }

            // We can not send auto reply to incoming bounce messages, as it will lead to the infinite loop: 
            // application will be sending auto replies and mail server will be sending bounce messages to auto replies.
            // Bounce detection can not be 100% reliable.
            // So to prevent infinite loop, we are checking number of auto replies sent to the customer recently.
            $created_at = \Illuminate\Support\Carbon::now()->subMinutes(10);
            $auto_replies_sent = SendLog::where('customer_id', $conversation->customer_id)
                ->where('mail_type', SendLog::MAIL_TYPE_AUTO_REPLY)
                ->where('created_at', '>', $created_at)
                ->count();

            if ($auto_replies_sent >= 3) {
                return;
            }

            // 24h limit has been disabled: https://github.com/freescout-helpdesk/freescout/pull/95
            // Send auto reply once in 24h
            /*$created_at = \Illuminate\Support\Carbon::now()->subDays(1);
            $auto_reply_sent = SendLog::where('customer_id', $conversation->customer_id)
                ->where('mail_type', SendLog::MAIL_TYPE_AUTO_REPLY)
                ->where('created_at', '>', $created_at)
                ->first();

            if ($auto_reply_sent) {
                return;
            }*/

            \App\Jobs\SendAutoReply::dispatch($conversation, $thread, $conversation->mailbox, $conversation->customer)
            ->onQueue('emails');
        }
    }
}
