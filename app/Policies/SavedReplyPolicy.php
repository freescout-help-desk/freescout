<?php

namespace App\Policies;

use App\SavedReply;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SavedReplyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the saved reply.
     *
     * @param \App\User         $user
     * @param \App\SavedReply $savedReply
     *
     * @return bool
     */
    public function view(User $user, SavedReply $savedReply)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($savedReply->mailbox->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can create the saved reply.
     *
     * @param \App\User         $user
     * @param \App\SavedReply $savedReply
     *
     * @return bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the saved reply.
     *
     * @param \App\User         $user
     * @param \App\SavedReply $savedReply
     *
     * @return bool
     */
    public function update(User $user, SavedReply $savedReply)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($savedReply->mailbox->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Check if user can delete saved reply.
     */
    public function delete(User $user, SavedReply $savedReply)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($savedReply->mailbox->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
