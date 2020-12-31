<?php

namespace App\Listeners;

use App\Conversation;

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

        // Do not send email if this is a Phone conversation.
        if ($conversation->isPhone()) {
            return;
        }

        $replies = $conversation->getReplies();

        // Ignore imported messages.
        if ($replies && $replies->first() && $replies->first()->imported) {
            return;
        }

        // We can not check imported here, as after conversation has been imported via API
        // notifications has to be sent.
        //if (!$conversation->imported) {
        \App\Jobs\SendReplyToCustomer::dispatch($conversation, $replies, $conversation->customer)
            ->delay(now()->addSeconds(Conversation::UNDO_TIMOUT))
            ->onQueue('emails');
    }
}
