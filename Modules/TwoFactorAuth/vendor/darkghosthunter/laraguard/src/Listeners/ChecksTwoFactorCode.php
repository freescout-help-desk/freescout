<?php

namespace DarkGhostHunter\Laraguard\Listeners;

use DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable;

trait ChecksTwoFactorCode
{
    /**
     * Returns if the login attempt should enforce Two Factor Authentication.
     *
     * @param  null|\DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable|\Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    protected function shouldUseTwoFactorAuth($user = null)
    {
        if (! $user instanceof TwoFactorAuthenticatable) {
            return false;
        }

        $shouldUse = $user->hasTwoFactorEnabled();

        if ($this->config['laraguard.safe_devices.enabled']) {
            return $shouldUse && ! $user->isSafeDevice($this->request);
        }

        return $shouldUse;
    }

    /**
     * Returns if the Request is from a Safe Device.
     *
     * @param  \DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable  $user
     * @return bool
     */
    protected function isSafeDevice(TwoFactorAuthenticatable $user)
    {
        return $this->config['laraguard.safe_devices.enabled'] && $user->isSafeDevice($this->request);
    }

    /**
     * Returns if the Request has the Two Factor Code.
     *
     * @return bool
     */
    protected function hasCode()
    {
        return $this->request->has($this->input);
    }

    /**
     * Checks if the Request has a Two Factor Code and is correct (even if is invalid).
     *
     * @param  \DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable  $user
     * @return bool
     */
    protected function hasValidCode(TwoFactorAuthenticatable $user)
    {
        return ! validator($this->request->only($this->input), [$this->input => 'alphanum'])->fails()
            && $user->validateTwoFactorCode($this->request->input($this->input));
    }

    /**
     * Adds a safe device to Two Factor Authentication data.
     *
     * @param  \DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable  $user
     * @return void
     */
    protected function addSafeDevice(TwoFactorAuthenticatable $user)
    {
        if ($this->config['laraguard.safe_devices.enabled']) {
            $user->addSafeDevice($this->request);
        }
    }
}
