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
            \App\Jobs\SendAutoReply::dispatch($conversation, $conversation->threads()->first(), $conversation->mailbox, $conversation->customer)
            ->onQueue('emails');
        }
    }
}
