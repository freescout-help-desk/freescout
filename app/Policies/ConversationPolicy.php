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
            if ($conversation->mailbox()->users->contains($user)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
