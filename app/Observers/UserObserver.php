<?php

namespace App\Observers;

use App\Subscription;
use App\User;

class UserObserver
{
    /**
     * Thread created.
     *
     * @param User $user
     */
    public function created(User $user)
    {
        // We can not create user folders here, as user is not connected to mailboxes yet
        
        // Add default subscriptions
        Subscription::addDefaultSubscriptions($user->id);
    }

    /**
     * On user delete.
     *
     * @param User $user
     */
    public function deleting(User $user)
    {
        $user->folders()->delete();
    }
}
