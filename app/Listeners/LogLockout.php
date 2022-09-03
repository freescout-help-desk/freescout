<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;

class LogLockout
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
     * @param Lockout $event
     *
     * @return void
     */
    public function handle(Lockout $event)
    {
        activity()
           //->causedBy($event->user)
           ->withProperties(['ip' => app('request')->ip(), 'email' => $event->request->email])
           ->useLog(\App\ActivityLog::NAME_USER)
           ->log(\App\ActivityLog::DESCRIPTION_USER_LOCKED);
    }
}
