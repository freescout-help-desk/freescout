<?php

namespace App\Listeners;

use App\Events\ConversationCreated;
use App\Thread;

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
     * @param ConversationCreated $event
     *
     * @return void
     */
    public function handle(ConversationCreated $event)
    {
        $conversation = $event->conversation;

        if (!$conversation->imported) {
            \App\Jobs\SendReplyToCustomer::dispatch($conversation, $conversation->getReplies(), $conversation->customer, auth()->user())
            ->onQueue('emails');
        }
    }
}
