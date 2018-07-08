<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogSuccessfulLogout
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
     * @param  Logout  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        activity()
           ->causedBy($event->user)
           ->withProperties(['ip' => app('request')->ip()])
           ->useLog(\App\ActivityLog::NAME_USER)
           ->log(\App\ActivityLog::DESCRIPTION_USER_LOGOUT);
    }
}
