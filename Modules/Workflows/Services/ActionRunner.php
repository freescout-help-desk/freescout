<?php

namespace Modules\Workflows\Services;

use App\Conversation;
use App\Thread;

class ActionRunner
{
    /**
     * Status slugs change_status accepts. Any other value is left unchanged.
     *
     * @var array
     */
    private static $statusSlugs = [
        'active' => Conversation::STATUS_ACTIVE,
        'pending' => Conversation::STATUS_PENDING,
        'closed' => Conversation::STATUS_CLOSED,
        'spam' => Conversation::STATUS_SPAM,
    ];

    /**
     * Perform one action. Returns stop only for the stop action.
     * Unknown types are offered to workflow.perform_action and still return done.
     *
     * @param string $type
     * @param mixed  $value
     * @param object $context
     * @return string
     */
    public static function perform($type, $value, $context): string
    {
        if ($type === 'stop') {
            return 'stop';
        }

        $conversation = self::read($context, 'conversation');
        $workflowUser = self::read($context, 'workflowUser');

        if ($type === 'change_status') {
            self::changeStatus($conversation, $value, $workflowUser);

            return 'done';
        }

        if ($type === 'assign') {
            self::assign($conversation, $value, $workflowUser);

            return 'done';
        }

        if ($type === 'add_note') {
            if (is_object($conversation)) {
                $conversation->createUserThread($workflowUser, $value, ['type' => Thread::TYPE_NOTE]);
            }

            return 'done';
        }

        if ($type === 'move_deleted') {
            if (is_object($conversation)) {
                $conversation->deleteToFolder($workflowUser);
            }

            return 'done';
        }

        if ($type === 'delete_forever') {
            if (is_object($conversation)) {
                // Not deleteForever(): this action's contract is delete().
                $conversation->delete();
            }

            return 'done';
        }

        if ($type === 'move_mailbox') {
            if (is_object($conversation)) {
                $conversation->moveToMailbox($value, $workflowUser);
            }

            return 'done';
        }

        \Eventy::filter(
            'workflow.perform_action',
            false,
            $type,
            null,
            $value,
            $conversation,
            self::read($context, 'workflow')
        );

        return 'done';
    }

    /**
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @return void
     */
    private static function changeStatus($conversation, $value, $workflowUser)
    {
        if (!is_object($conversation) || !is_string($value) || !isset(self::$statusSlugs[$value])) {
            return;
        }

        $conversation->changeStatus(self::$statusSlugs[$value], $workflowUser, true);
    }

    /**
     * only_if_available limits the assign to true, 1, and "1".
     * Other values, including a missing key, assign without asking the filter.
     *
     * @param object|null $conversation
     * @param mixed       $value
     * @param mixed       $workflowUser
     * @return void
     */
    private static function assign($conversation, $value, $workflowUser)
    {
        if (!is_object($conversation) || !is_array($value) || !array_key_exists('user_id', $value)) {
            return;
        }

        $userId = $value['user_id'];

        if (self::onlyIfAvailable($value)) {
            $available = \Eventy::filter('user.is_user_available', true, $userId);
            if (!$available) {
                return;
            }
        }

        $conversation->changeUser($userId, $workflowUser, true);
    }

    /**
     * @param array $value
     * @return bool
     */
    private static function onlyIfAvailable(array $value): bool
    {
        if (!array_key_exists('only_if_available', $value)) {
            return false;
        }

        $flag = $value['only_if_available'];

        return $flag === true || $flag === 1 || $flag === '1';
    }

    /**
     * Missing properties are null. Undeclared properties are not read.
     *
     * @param mixed  $context
     * @param string $name
     * @return mixed
     */
    private static function read($context, $name)
    {
        if (is_object($context) && property_exists($context, $name)) {
            return $context->{$name};
        }

        return null;
    }
}
