<?php

namespace DarkGhostHunter\Laraguard\Contracts;

use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\Events\Attempting;

interface TwoFactorListener
{
    /**
     * Saves the credentials temporarily into the class instance.
     *
     * @param  \Illuminate\Auth\Events\Attempting  $event
     * @return void
     */
    public function saveCredentials(Attempting $event);

    /**
     * Checks if the user should use Two Factor Auth.
     *
     * @param  \Illuminate\Auth\Events\Validated  $event
     * @return void
     */
    public function checkTwoFactor(Validated $event);
}
