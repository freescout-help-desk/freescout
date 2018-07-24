<?php

namespace App;

use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    const NAME_USER = 'user';

    const DESCRIPTION_USER_LOGIN = 'login';
    const DESCRIPTION_USER_LOGOUT = 'logout';
    const DESCRIPTION_USER_REGISTER = 'register';
    const DESCRIPTION_USER_LOCKED = 'locked';
    const DESCRIPTION_USER_LOGIN_FAILED = 'login_failed';
    const DESCRIPTION_USER_PASSWORD_RESET = 'password_reset';

    public function getEventDescription()
    {
        switch ($this->description) {
            case self::DESCRIPTION_USER_LOGIN:
                return __('Logged in');
            case self::DESCRIPTION_USER_LOGOUT:
                return __('Logged out');
            case self::DESCRIPTION_USER_REGISTER:
                return __('Registered');
            case self::DESCRIPTION_USER_LOCKED:
                return __('Locked out');
            case self::DESCRIPTION_USER_LOGIN_FAILED:
                return __('Failed login');
            case self::DESCRIPTION_USER_PASSWORD_RESET:
                return __('Reset password');
            default:
                return $this->description;
                break;
        }
    }
}
