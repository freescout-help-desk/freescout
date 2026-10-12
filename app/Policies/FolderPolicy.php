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
            if ($folder->user_id == $user->id || in_array($folder->mailbox_id, $user->mailboxesIdsCanView())) {
                return true;
            } else {
                return false;
            }
        }
    }
}
