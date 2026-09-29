<?php

namespace Modules\Workflows\Services;

use App\User;
use Illuminate\Support\Facades\Hash;

class WorkflowUser
{
    /**
     * @var string
     */
    private const EMAIL = 'workflow@localhost';

    /**
     * Missing and empty WORKFLOWS_USER_FULL_NAME both mean Workflow.
     * env() substitutes the default only when the variable is absent.
     * A set value is cut to 20 characters. é counts as one character.
     *
     * @return string
     */
    public static function displayName(): string
    {
        $name = env('WORKFLOWS_USER_FULL_NAME', 'Workflow');
        if (!is_string($name) || $name === '') {
            $name = 'Workflow';
        }

        return mb_substr($name, 0, 20, 'UTF-8');
    }

    /**
     * Hidden from assignee lists by STATUS_DELETED.
     * first_name is the truncated display name. Role is not included.
     *
     * @return array
     */
    public static function attributes(): array
    {
        return [
            'status' => User::STATUS_DELETED,
            'first_name' => self::displayName(),
            'email' => self::EMAIL,
        ];
    }

    /**
     * Return workflow@localhost, creating it when missing.
     * This lookup includes deleted users. Password is a random hash.
     * last_name is empty because the column is not nullable. Role is left unset.
     *
     * @return User
     */
    public static function findOrCreate(): User
    {
        $user = User::where('email', self::EMAIL)->first();
        if ($user !== null) {
            return $user;
        }

        $user = new User(self::attributes());
        $user->password = Hash::make(str_random(32));
        $user->last_name = '';
        $user->save();

        return $user;
    }
}
