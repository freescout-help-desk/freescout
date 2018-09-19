<?php

namespace App\Events;

use App\User;

class UserDeleted
{
    public $user;
    public $deleted_user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, User $deleted_user)
    {
        $this->user = $user;
        $this->deleted_user = $deleted_user;
    }
}
