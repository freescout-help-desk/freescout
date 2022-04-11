<?php

namespace Modules\TwoFactorAuth\Entities;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class TwoFactorAuthentication extends Model
{
    protected $casts = [
        'recovery_codes' => 'array',
        'safe_devices' => 'array',
    ];
}