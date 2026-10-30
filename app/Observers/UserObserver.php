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
     * Revoke non-used password reset tokens when the email address changes.
     * Reset tokens are stored by email address, so without this a token issued
     * for an address would stay valid for whichever account takes it over
     * (admin email change, invitation setup, user deletion).
     * Runs on the same connection as the save, so it is part of the
     * surrounding transaction if there is one.
     *
     * @param User $user
     */
    public function updating(User $user)
    {
        if (!$user->isDirty('email')) {
            return;
        }

        $emails = array_filter(array_unique([
            (string) $user->getOriginal('email'),
            (string) $user->email,
        ]), 'strlen');

        if ($emails) {
            \DB::table(config('auth.passwords.users.table', 'password_resets'))
                ->whereIn('email', $emails)
                ->delete();
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
