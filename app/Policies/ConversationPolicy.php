<?php

namespace App\Policies;

use App\Conversation;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the conversation.
     *
     * @param \App\User         $user
     * @param \App\Conversation $conversation
     *
     * @return bool
     */
    public function view(User $user, Conversation $conversation)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($conversation->mailbox->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can update the conversation.
     *
     * @param \App\User         $user
     * @param \App\Conversation $conversation
     *
     * @return bool
     */
    public function update(User $user, Conversation $conversation)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($conversation->mailbox->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Check if user can delete conversation.
     */
    public function delete(User $user, Conversation $conversation)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            return $user->hasPermission(User::USER_PERM_DELETE_CONVERSATIONS);
        }
    }
}
