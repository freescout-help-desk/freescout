<?php

namespace App\Policies;

use App\Mailbox;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailboxPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create mailboxes.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can view mailbox conversations.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function view(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($mailbox->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can view mailbox conversations.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function viewCached(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            // Use cached users for Realtime events
            if ($mailbox->users_cached->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can admin the mailbox.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function admin(User $user, Mailbox $mailbox)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the mailbox.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function update(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || $user->canManageMailbox($mailbox->id)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can update the mailbox auto reply.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function updateAutoReply(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, Mailbox::ACCESS_PERM_AUTO_REPLIES)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can update the mailbox Permissions.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function updatePermissions(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, Mailbox::ACCESS_PERM_PERMISSIONS)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can update the mailbox Permissions.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function updateSettings(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, Mailbox::ACCESS_PERM_EDIT)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can update the mailbox Email Signature.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function updateEmailSignature(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, Mailbox::ACCESS_PERM_SIGNATURE)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the mailbox.
     *
     * @param \App\User    $user
     * @param \App\Mailbox $mailbox
     *
     * @return mixed
     */
    public function delete(User $user, Mailbox $mailbox)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            return false;
        }
    }
}
