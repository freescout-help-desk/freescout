<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogSuccessfulLogin
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
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        activity()
           ->causedBy($event->user)
           ->withProperties(['ip' => app('request')->ip()])
           ->useLog(\App\ActivityLog::NAME_USER)
           ->log(\App\ActivityLog::DESCRIPTION_USER_LOGIN);
    }
}
