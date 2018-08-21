<?php

namespace App;

use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    const NAME_USER = 'users';
    const NAME_EMAILS_SENDING = 'emails_sending';
    const NAME_EMAILS_FETCHING = 'emails_fetching';

    const DESCRIPTION_USER_LOGIN = 'login';
    const DESCRIPTION_USER_LOGOUT = 'logout';
    const DESCRIPTION_USER_REGISTER = 'register';
    const DESCRIPTION_USER_LOCKED = 'locked';
    const DESCRIPTION_USER_LOGIN_FAILED = 'login_failed';
    const DESCRIPTION_USER_PASSWORD_RESET = 'password_reset';
    const DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER = 'error_sending_email_to_customer';
    const DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER = 'error_sending_email_to_user';
    const DESCRIPTION_EMAILS_FETCHING_ERROR = 'error_fetching_email';

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
            case self::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER:
                return __('Error sending email to customer');
            case self::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER:
                return __('Error sending email to user');
            case self::DESCRIPTION_EMAILS_FETCHING_ERROR:
                return __('Error fetching email');
            default:
                return $this->description;
                break;
        }
    }

    /**
     * Get title for the log record.
     */
    public static function getLogTitle($log_name)
    {
        switch ($log_name) {
            case self::NAME_USER:
                return __('Users');
            case self::NAME_EMAILS_SENDING:
                return __('Emails Sending');
            case self::NAME_EMAILS_FETCHING:
                return __('Emails Fetching');
            default:
                return ucfirst($log_name);
        }
    }
}
