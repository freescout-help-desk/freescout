<?php

namespace Modules\Workflows\Listeners;

use App\Conversation;

class RunWorkflows
{
    /**
     * Event names that start an automatic workflow.
     *
     * @var array
     */
    private static $triggerNames = [
        'conversation.created_by_customer' => 'new',
        'conversation.customer_replied' => 'customer_reply',
        'conversation.user_replied' => 'user_reply',
        'conversation.note_added' => 'user_note',
        'conversation.moved' => 'moved',
        'conversation.status_changed' => 'updated',
        'conversation.user_changed' => 'updated',
        'conversation.subject_changed' => 'updated',
        'conversation.state_changed' => 'updated',
    ];

    /**
     * Drafts never run. A missing state is not a draft.
     * State uses == because PDO may return it as a string.
     *
     * @param string $eventName
     * @param mixed  $conversation
     * @param mixed  $thread
     * @param array  $extra
     * @return array|null
     */
    public static function triggerFor($eventName, $conversation, $thread = null, $extra = []): ?array
    {
        if (self::state($conversation) == Conversation::STATE_DRAFT) {
            return null;
        }

        if (!is_string($eventName) || !array_key_exists($eventName, self::$triggerNames)) {
            return null;
        }

        return ['name' => self::$triggerNames[$eventName]];
    }

    /**
     * @param mixed $conversation
     * @return mixed
     */
    private static function state($conversation)
    {
        if (is_array($conversation)) {
            if (!array_key_exists('state', $conversation)) {
                return null;
            }

            return $conversation['state'];
        }

        if (!is_object($conversation)) {
            return null;
        }

        if (method_exists($conversation, 'getAttribute')) {
            return $conversation->getAttribute('state');
        }

        if (property_exists($conversation, 'state')) {
            return $conversation->state;
        }

        return null;
    }
}
