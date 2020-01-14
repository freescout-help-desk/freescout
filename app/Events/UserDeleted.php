<?php

namespace App\Events;

use App\User;

class UserDeleted
{
    public $by_user;
    public $deleted_user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $deleted_user, User $by_user)
    {
        $this->by_user = $by_user;
        $this->deleted_user = $deleted_user;

        \Eventy::action('user.deleted', $deleted_user, $by_user);
    }
}
