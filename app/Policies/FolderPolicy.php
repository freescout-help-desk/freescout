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
     * Checks access to the mailbox also.
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
        }

        // Mailbox access and folder accessibility must be checked before
        // anything else: a (historical) folder owner must not keep access
        // to the folder after the user has been removed from the mailbox
        // or the mailbox has been deactivated.
        $mailbox = $folder->mailbox;

        if (!$mailbox
            || !$mailbox->userHasAccess(null, $user)
            || ($folder->id && !in_array($folder->id, $mailbox->getAssesibleFolders($user)->pluck('id')->toArray()))
        ) {
            return false;
        }

        return true;
    }
}
