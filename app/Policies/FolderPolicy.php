<?php

namespace App\Policies;

use App\Folder;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the folder.
     *
     * @param \App\User   $user
     * @param \App\Folder $folder
     *
     * @return bool
     */
    public function view(User $user, Folder $folder)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            if ($folder->user_id == $user->id || $user->mailboxesSettings()->pluck('mailbox_id')->contains($folder->mailbox_id)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
