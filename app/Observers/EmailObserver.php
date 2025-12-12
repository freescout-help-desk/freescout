<?php

namespace App\Observers;

use App\Email;

class EmailObserver
{
    /**
     * On create before saving.
     *
     * @param Email $email
     */
    public function creating(Email $email)
    {
        // https://github.com/freescout-help-desk/freescout/issues/5106
        $this->email = Email::sanitizeLength($this->email);
    }

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
