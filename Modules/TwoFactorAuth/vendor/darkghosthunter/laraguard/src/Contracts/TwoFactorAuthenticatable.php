<?php

namespace DarkGhostHunter\Laraguard\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface TwoFactorAuthenticatable
{
    /**
     * Determines if the User has Two Factor Authentication enabled or not.
     *
     * @return bool
     */
    public function hasTwoFactorEnabled() : bool;

    /**
     * Enables Two Factor Authentication for the given user.
     *
     * @return void
     */
    public function enableTwoFactorAuth() : void;

    /**
     * Disables Two Factor Authentication for the given user.
     *
     * @return void
     */
    public function disableTwoFactorAuth() : void;

    /**
     * Recreates the Two Factor Authentication from the ground up, and returns a new Shared Secret.
     *
     * @return \DarkGhostHunter\Laraguard\Contracts\TwoFactorTotp
     */
    public function createTwoFactorAuth() : TwoFactorTotp;

    /**
     * Confirms the Shared Secret and fully enables the Two Factor Authentication.
     *
     * @param  string  $code
     * @return bool
     */
    public function confirmTwoFactorAuth(string $code) : bool;

    /**
     * Validates the TOTP Code or Recovery Code.
     *
     * @param  string  $code
     * @return bool
     */
    public function validateTwoFactorCode(?string $code = null) : bool;

    /**
     * Makes a Two Factor Code for a given time, and period offset.
     *
     * @param  int|string|\Illuminate\Support\Carbon|\Datetime  $at
     * @param  int  $offset
     * @return string
     */
    public function makeTwoFactorCode($at = 'now', int $offset = 0) : string;

    /**
     * Return the current set of Recovery Codes.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRecoveryCodes() : Collection;

    /**
     * Generates a new set of Recovery Codes.
     *
     * @return \Illuminate\Support\Collection
     */
    public function generateRecoveryCodes() : Collection;

    /**
     * Return all the Safe Devices that bypass Two Factor Authentication.
     *
     * @return \Illuminate\Support\Collection
     */
    public function safeDevices() : Collection;

    /**
     * Adds a "safe" Device from the Request, and returns the token used to identify it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function addSafeDevice(Request $request) : string;

    /**
     * Determines if the Request has been made through a previously used "safe" device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function isSafeDevice(Request $request) : bool;
}
