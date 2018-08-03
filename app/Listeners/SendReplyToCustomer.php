<?php

namespace App\Listeners;

use App\Events\UserReplied;

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
     *
     * @param UserReplied $event
     *
     * @return void
     */
    public function handle(UserReplied $event)
    {
        $conversation = $event->conversation;

        if (!$conversation->imported) {
            \App\Jobs\SendReplyToCustomer::dispatch($conversation, $conversation->getReplies(), $conversation->customer, auth()->user())
            ->onQueue('emails');
        }
    }
}
