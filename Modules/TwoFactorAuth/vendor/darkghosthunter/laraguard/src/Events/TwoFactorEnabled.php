<?php

namespace DarkGhostHunter\Laraguard\Events;

use DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable;

class TwoFactorEnabled
{
    /**
     * The User using Two Factor Authentication.
     *
     * @var \DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable  $user
     * @return void
     */
    public function __construct(TwoFactorAuthenticatable $user)
    {
        $this->user = $user;
    }
}
