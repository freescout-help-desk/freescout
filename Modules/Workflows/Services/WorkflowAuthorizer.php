<?php

namespace Modules\Workflows\Services;

class WorkflowAuthorizer
{
    /**
     * Admins, the allow-non-admins flag, or permission 1001.
     * The flag is an argument so this method never reads Option.
     * A missing hasPermission method is denial, not an error.
     *
     * @param mixed $user
     * @param bool  $optionOn
     * @return bool
     */
    public static function canManage($user, bool $optionOn): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if ($optionOn) {
            return true;
        }

        if (is_object($user) && method_exists($user, 'hasPermission') && $user->hasPermission(1001)) {
            return true;
        }

        return false;
    }

    /**
     * Admins return before the option is read.
     *
     * @param mixed $user
     * @return bool
     */
    public static function allows($user): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        return self::canManage($user, self::optionOn());
    }

    /**
     * @return string
     */
    public static function permissionName()
    {
        return 'Manage workflows';
    }

    /**
     * @param mixed $user
     * @return bool
     */
    private static function isAdmin($user)
    {
        return is_object($user) && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    /**
     * On only for true, integer 1, and string "1".
     *
     * @param mixed $stored
     * @return bool
     */
    public static function storedOptionOn($stored): bool
    {
        return $stored === true || $stored === 1 || $stored === '1';
    }

    /**
     * @return bool
     */
    private static function optionOn(): bool
    {
        return self::storedOptionOn(\Option::get('workflows.allow_non_admins'));
    }
}
