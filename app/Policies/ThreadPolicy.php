<?php

namespace App\Policies;

use App\Thread;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ThreadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can edit the thread.
     *
     * @param \App\User    $user
     * @param \App\Thread  $thread
     *
     * @return mixed
     */
    public function edit(User $user, Thread $thread)
    {
        if ((
                $thread->created_by_user_id 
                && in_array($thread->type, [Thread::TYPE_MESSAGE, Thread::TYPE_NOTE])
                && ($user->isAdmin() || ($user->hasPermission(User::PERM_EDIT_CONVERSATIONS) && $thread->created_by_user_id == $user->id))
            ) || (
                $thread->created_by_customer_id
                && in_array($thread->type, [Thread::TYPE_CUSTOMER])
                && $thread->conversation
                && ($user->isAdmin() || ($thread->conversation->userHasAccessToMailbox($user->id) && $this->checkIsOnlyAssigned($thread->conversation, $user)))
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function delete(User $user, Thread $thread)
    {
        if ($thread->created_by_user_id == $user->id) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIsOnlyAssigned($conversation, $user)
    {
        // Maybe user can see only assigned conversations.
        if (!\Eventy::filter('conversation.is_user_assignee', $conversation->user_id == $user->id, $conversation, $user->id)
            && $conversation->created_by_user_id != $user->id
            && $user->canSeeOnlyAssignedConversations()
        ) {
            return false;
        } else {
            return true;
        }
    }
}
