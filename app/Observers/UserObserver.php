<?php

namespace App\Observers;

use App\User;

class UserObserver
{
    /**
     * On user delete.
     *
     * @param Conversation $mailbox
     *
     * @return [type] [description]
     */
    public function deleting(User $user)
    {
        $user->folders()->delete();
    }
}
