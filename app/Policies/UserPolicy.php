<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\User $user
     * @param \App\User $model
     *
     * @return mixed
     */
    public function view(User $user, User $model)
    {
        if ($user->isAdmin() || $user->id == $model->id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can create models.
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
     * Determine whether the user can update the model.
     *
     * @param \App\User $user
     * @param \App\User $model
     *
     * @return mixed
     */
    public function update(User $user, User $model)
    {
        if ($user->isAdmin() || $user->id == $model->id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\User $user
     * @param \App\User $model
     *
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        if ($user->isAdmin() || $user->id == $model->id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can change role of the user.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function changeRole(User $user, User $model)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can view mailboxes menu.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function viewMailboxMenu(User $user)
    {
        if ($user->isAdmin() || \Eventy::filter('user.can_view_mailbox_menu', false, $user)) {
            return true;
        } else {
            return false;
        }
    }
}
