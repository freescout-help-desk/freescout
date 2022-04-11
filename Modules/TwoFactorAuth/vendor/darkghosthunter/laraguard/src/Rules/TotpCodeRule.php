<?php

namespace DarkGhostHunter\Laraguard\Rules;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Translation\Translator;
use DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable;

class TotpCodeRule
{
    /**
     * The auth user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|\DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable
     */
    protected $user;

    /**
     * Translator instance.
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * Create a new "totp code" rule instance.
     *
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    public function __construct(Translator $translator, Authenticatable $user = null)
    {
        $this->user = $user;
        $this->translator = $translator;
    }

    /**
     * Validate that an attribute is a valid Two Factor Authentication TOTP code.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validate($attribute, $value)
    {
        if (is_string($value) && $this->user instanceof TwoFactorAuthenticatable) {
            return $this->user->validateTwoFactorCode($value);
        }

        return false;
    }

}