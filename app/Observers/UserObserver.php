<?php

namespace App\Observers;

use App\Follower;
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

    public function creating(User $user)
    {
        // This is a hack for backward compatibility.
        if ($user->type == 0 && preg_match("#^fs.*@example\.org$#", $user->email)
        ) {
            $user->type = User::TYPE_ROBOT;
        }
    }

    /**
     * On user delete.
     *
     * @param User $user
     */
    public function deleting(User $user)
    {
        $user->folders()->delete();
        Follower::whereIn('user_id', $user->id)->delete();
    }
}
