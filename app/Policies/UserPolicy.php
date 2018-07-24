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
}
