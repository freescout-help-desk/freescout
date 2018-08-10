<?php

namespace App\Observers;

use App\Mailbox;
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
        // We can not create folders for regular users here, as user is not connected to mailboxes yet
        // But we can create admin personal folders
        Mailbox::createAdminPersonalFoldersAllMailboxes();

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
