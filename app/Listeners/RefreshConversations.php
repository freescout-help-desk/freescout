<?php

namespace App\Listeners;

use App\Conversation;

class RefreshConversations
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
     * @param  $event
     *
     * @return void
     */
    public function handle($event)
    {
        Conversation::refreshConversations($event->conversation, $event->thread ?? $event->last_thread);
    }
}
