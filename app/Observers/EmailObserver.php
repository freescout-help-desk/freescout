<?php

namespace App\Observers;

use App\Email;

class EmailObserver
{
    /**
     * Email created.
     *
     * @param User $user
     */
    public function created(Email $email)
    {
        \Eventy::action('email.created', $email);
    }
}
