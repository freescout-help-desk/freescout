<?php

namespace DarkGhostHunter\Laraguard\Contracts;

use Illuminate\Contracts\Support\Renderable;

interface TwoFactorTotp extends Renderable
{
    /**
     * Validates a given code, optionally for a given timestamp and future window.
     *
     * @param  string  $code
     * @param  int|string|\Illuminate\Support\Carbon|\Datetime  $at
     * @param  int  $window
     * @return bool
     */
    public function validateCode(string $code, $at = 'now', int $window = null) : bool;

    /**
     * Creates a Code for a given timestamp, optionally by a given period offset.
     *
     * @param  string  $at
     * @param  int  $offset
     * @return string
     */
    public function makeCode($at = 'now', int $offset = 0) : string;

    /**
     * Returns the Shared Secret as a QR Code.
     *
     * @return string
     */
    public function toQr() : string;

    /**
     * Returns the Shared Secret as a string.
     *
     * @return string
     */
    public function toString() : string;

    /**
     * Returns the Shared Secret as an URI.
     *
     * @return string
     */
    public function toUri() : string;
}
