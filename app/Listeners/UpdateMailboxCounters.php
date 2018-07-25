<?php

namespace App\Listeners;

class UpdateMailboxCounters
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
        $event->conversation->mailbox->updateFoldersCounters();
    }
}
