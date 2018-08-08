<?php

namespace App\Listeners;

class SendReplyToCustomer
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

        if (!$conversation->imported) {
            \App\Jobs\SendReplyToCustomer::dispatch($conversation, $conversation->getReplies(), $conversation->customer)
            ->onQueue('emails');
        }
    }
}
