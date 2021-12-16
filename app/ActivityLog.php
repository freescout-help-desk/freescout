<?php

namespace App;

use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    const NAME_USER = 'users';
    const NAME_OUT_EMAILS = 'out_emails'; // used to display send_log in Logs
    const NAME_EMAILS_SENDING = 'send_errors';
    const NAME_EMAILS_FETCHING = 'fetch_errors';
    const NAME_SYSTEM = 'system';
    const NAME_APP_LOGS = 'app';

    public static $available_logs = [
        self::NAME_USER,
        self::NAME_OUT_EMAILS,
        self::NAME_EMAILS_SENDING,
        self::NAME_EMAILS_FETCHING,
        self::NAME_SYSTEM,
        self::NAME_APP_LOGS,
    ];

    const DESCRIPTION_USER_LOGIN = 'login';
    const DESCRIPTION_USER_LOGOUT = 'logout';
    const DESCRIPTION_USER_REGISTER = 'register';
    const DESCRIPTION_USER_LOCKED = 'locked';
    const DESCRIPTION_USER_LOGIN_FAILED = 'login_failed';
    const DESCRIPTION_USER_PASSWORD_RESET = 'password_reset';
    const DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER = 'error_sending_email_to_customer';
    const DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER = 'error_sending_email_to_user';
    const DESCRIPTION_EMAILS_SENDING_ERROR_INVITE = 'error_sending_invite_to_user';
    const DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED = 'error_sending_password_changed';
    const DESCRIPTION_EMAILS_SENDING_ERROR_ALERT = 'error_sending_alert';
    const DESCRIPTION_EMAILS_SENDING_WRONG_EMAIL = 'error_sending_wrong_email';
    const DESCRIPTION_EMAILS_FETCHING_ERROR = 'error_fetching_email';
    const DESCRIPTION_SYSTEM_ERROR = 'system_error';
    const DESCRIPTION_USER_DELETED = 'user_deleted';

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
            case self::DESCRIPTION_EMAILS_SENDING_ERROR_INVITE:
                return __('Error sending invitation email to user');
            case self::DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED:
                return __('Error sending password changed notification to user');
            case self::DESCRIPTION_EMAILS_SENDING_ERROR_ALERT:
                return __('Error sending alert');
            case self::DESCRIPTION_EMAILS_SENDING_WRONG_EMAIL:
                return __('Error sending email to the user who replied to notiication from wrong email');
            case self::DESCRIPTION_EMAILS_FETCHING_ERROR:
                return __('Error fetching email');
            case self::DESCRIPTION_SYSTEM_ERROR:
                return __('System error');
            case self::DESCRIPTION_USER_DELETED:
                return __('Deleted user');
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
            case self::NAME_OUT_EMAILS:
                return __('Outgoing Emails');
            case self::NAME_EMAILS_SENDING:
                return __('Send Errors');
            case self::NAME_EMAILS_FETCHING:
                return __('Fetch Errors');
            case self::NAME_SYSTEM:
                return __('System');
            case self::NAME_APP_LOGS:
                return __('App Logs');
            default:
                return ucwords(str_replace('_', ' ', $log_name));
        }
    }

    public static function formatColTitle($col)
    {
        $col = str_replace('_', ' ', $col);
        $col = ucfirst($col);

        return $col;
    }

    /**
     * Get log names.
     *
     * @return [type] [description]
     */
    public static function getLogNames()
    {
        return self::select('log_name')->distinct()->pluck('log_name')->toArray();
    }

    /**
     * Get available log names.
     *
     * @return [type] [description]
     */
    public static function getAvailableLogs($check_existing = true)
    {
        $available_logs = self::$available_logs;
        if ($check_existing) {
            $available_logs = array_merge($available_logs, self::getLogNames());
        }

        return array_unique(\Eventy::filter('activity_log.available_logs', self::$available_logs));
    }
}
