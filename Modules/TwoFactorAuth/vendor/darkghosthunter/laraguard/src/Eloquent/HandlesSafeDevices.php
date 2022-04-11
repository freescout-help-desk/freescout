<?php

namespace DarkGhostHunter\Laraguard\Eloquent;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

trait HandlesSafeDevices
{
    /**
     * Returns the timestamp of the Safe Device.
     *
     * @param  null|string  $token
     * @return null|\Illuminate\Support\Carbon
     */
    public function getSafeDeviceTimestamp(string $token = null)
    {
        if ($token && $device = collect($this->safe_devices)->firstWhere('2fa_remember', $token)) {
            return Carbon::createFromTimestamp($device['added_at']);
        }

        return null;
    }

    /**
     * Generates a Device token to bypass Two Factor Authentication.
     *
     * @return string
     */
    public static function generateDefaultTwoFactorRemember()
    {
        return Str::random(100);
    }
}
