<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;

class LogFailedLogin
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
     * @param Failed $event
     *
     * @return void
     */
    public function handle(Failed $event)
    {
        activity()
           ->causedBy($event->user)
           ->withProperties(['ip' => app('request')->ip()])
           ->useLog(\App\ActivityLog::NAME_USER)
           ->log(\App\ActivityLog::DESCRIPTION_USER_LOGIN_FAILED);
    }
}
