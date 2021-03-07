<?php

namespace App\Listeners;

class ProcessSwiftMessage
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
        return \Eventy::filter('mail.process_swift_message', true, $event->message);
    }
}
