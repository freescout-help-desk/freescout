<?php

namespace App\Listeners;

use App\Events\UserDeleted;

class LogUserDeletion
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
     * @param Login $event
     *
     * @return void
     */
    public function handle(UserDeleted $event)
    {
        activity()
           ->causedBy($event->by_user)
           ->withProperties(['deleted_user' => $event->deleted_user->getFullName().' ['.$event->deleted_user->id.']'])
           ->useLog(\App\ActivityLog::NAME_USER)
           ->log(\App\ActivityLog::DESCRIPTION_USER_DELETED);
    }
}
