<?php

namespace Modules\Workflows\Services;

use App\Conversation;

class ConditionEvaluator
{
    /**
     * @var array
     */
    private static $statusSlugs = [
        'active' => Conversation::STATUS_ACTIVE,
        'pending' => Conversation::STATUS_PENDING,
        'closed' => Conversation::STATUS_CLOSED,
        'spam' => Conversation::STATUS_SPAM,
    ];

    /**
     * @var array
     */
    private static $typeSlugs = [
        'email' => Conversation::TYPE_EMAIL,
        'phone' => Conversation::TYPE_PHONE,
    ];

    /**
     * Compare text. Null haystack and needle are empty strings.
     *
     * @param string|null $haystack
     * @param string      $operator
     * @param string|null $needle
     * @return bool
     */
    public static function text(?string $haystack, string $operator, ?string $needle): bool
    {
        $haystack = $haystack ?? '';
        $needle = $needle ?? '';

        if ($operator === 'regex') {
            return self::matchesRegex($haystack, $needle);
        }

        $haystack = mb_strtolower($haystack);
        $needle = mb_strtolower($needle);

        if ($operator === 'contains') {
            return mb_strpos($haystack, $needle) !== false;
        }

        if ($operator === 'not_contains') {
            return mb_strpos($haystack, $needle) === false;
        }

        if ($operator === 'equal') {
            return $haystack === $needle;
        }

        if ($operator === 'not_equal') {
            return $haystack !== $needle;
        }

        return false;
    }

    /**
     * Whether one condition row matches. Unknown types and operators are false.
     * A missing context property counts as null, false, or an empty list.
     *
     * @param string $type
     * @param string $operator
     * @param mixed  $value
     * @param object $context
     * @return bool
     */
    public static function matches(string $type, string $operator, $value, $context): bool
    {
        if ($type === 'status') {
            return self::matchesSlug(self::$statusSlugs, self::read($context, 'status'), $operator, $value);
        }

        if ($type === 'conversation_type') {
            return self::matchesSlug(self::$typeSlugs, self::read($context, 'type'), $operator, $value);
        }

        if ($type === 'assignee') {
            return self::matchesAssignee($operator, $value, self::read($context, 'user_id'));
        }

        if ($type === 'customer_viewed') {
            return self::matchesFlag($operator, (bool) self::read($context, 'customer_viewed', false));
        }

        if ($type === 'user_action') {
            return self::matchesUserAction($operator, self::read($context, 'trigger'));
        }

        if ($type === 'new_reply_moved') {
            if ($operator !== 'is') {
                return false;
            }

            return self::read($context, 'trigger') === $value;
        }

        if ($type === 'body') {
            if (!is_array($value)) {
                $value = [];
            }
            $chosenBody = self::chosenBody($value, $context);

            return self::text($chosenBody, $operator, (string) ($value['text'] ?? ''));
        }

        if ($type === 'attachment') {
            $hasAttachment = (bool) self::read($context, 'has_attachment', false);
            if ($operator === 'contains') {
                return $hasAttachment;
            }
            if ($operator === 'not_contains') {
                return !$hasAttachment;
            }

            return false;
        }

        return false;
    }

    /**
     * @ is required so a compile warning does not become an ErrorException.
     * Append i only when a closing delimiter is present and i is absent.
     * Unterminated patterns are left unchanged so they stay invalid.
     * The log line uses the caller's pattern, not the flag rewrite.
     *
     * @param string $haystack
     * @param string $pattern
     * @return bool
     */
    private static function matchesRegex(string $haystack, string $pattern): bool
    {
        $matched = @preg_match(self::withCaseInsensitiveFlag($pattern), $haystack);

        if ($matched === false) {
            \Log::error('Invalid Workflow conditions regex: '.$pattern);

            return false;
        }

        return $matched === 1;
    }

    /**
     * @param string $pattern
     * @return string
     */
    private static function withCaseInsensitiveFlag(string $pattern): string
    {
        $length = strlen($pattern);
        if ($length < 2) {
            return $pattern;
        }

        $split = $length;
        while ($split > 1 && self::isAsciiLetter($pattern[$split - 1])) {
            $split--;
        }

        // The opening delimiter is not a closer. An unterminated pattern stays unchanged.
        if ($split === 1 || $pattern[$split - 1] !== $pattern[0]) {
            return $pattern;
        }

        $flags = substr($pattern, $split);
        if (strpos($flags, 'i') !== false) {
            return $pattern;
        }

        return $pattern.'i';
    }

    /**
     * @param string $character
     * @return bool
     */
    private static function isAsciiLetter(string $character): bool
    {
        return strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $character) !== false;
    }

    /**
     * @param array  $slugs
     * @param mixed  $actual
     * @param string $operator
     * @param mixed  $value
     * @return bool
     */
    private static function matchesSlug(array $slugs, $actual, string $operator, $value): bool
    {
        if (!is_string($value) || !array_key_exists($value, $slugs)) {
            return false;
        }

        if ($operator === 'equal') {
            return $actual === $slugs[$value];
        }

        if ($operator === 'not_equal') {
            return $actual !== $slugs[$value];
        }

        return false;
    }

    /**
     * @param string $operator
     * @param mixed  $value
     * @param mixed  $userId
     * @return bool
     */
    private static function matchesAssignee(string $operator, $value, $userId): bool
    {
        if ($operator !== 'equal' && $operator !== 'not_equal') {
            return false;
        }

        if ($value === 'nobody') {
            $matches = $userId === null;
        } elseif ($value === 'anybody') {
            $matches = $userId !== null;
        } elseif (is_int($value) || is_string($value)) {
            $matches = $userId !== null && (string) $userId === (string) $value;
        } else {
            return false;
        }

        return $operator === 'equal' ? $matches : !$matches;
    }

    /**
     * @param string $operator
     * @param bool   $flag
     * @return bool
     */
    private static function matchesFlag(string $operator, bool $flag): bool
    {
        if ($operator === 'yes') {
            return $flag;
        }

        if ($operator === 'no') {
            return !$flag;
        }

        return false;
    }

    /**
     * @param string $operator
     * @param mixed  $trigger
     * @return bool
     */
    private static function matchesUserAction(string $operator, $trigger): bool
    {
        if ($operator === 'replied') {
            return $trigger === 'user_reply';
        }

        if ($operator === 'added_note') {
            return $trigger === 'user_note';
        }

        return false;
    }

    /**
     * The triggering body counts only when its source is the one the condition named.
     *
     * @param array  $value
     * @param object $context
     * @return string|null
     */
    private static function chosenBody(array $value, $context): ?string
    {
        $source = isset($value['source']) ? (string) $value['source'] : '';
        $triggerSource = self::read($context, 'trigger_source');

        if ($triggerSource !== null && (string) $triggerSource === $source) {
            $chosen = self::read($context, 'trigger_body');
        } else {
            $latest = self::read($context, 'latest_body_by_source', []);
            if (!is_array($latest)) {
                $latest = [];
            }
            $chosen = array_key_exists($source, $latest) ? $latest[$source] : null;
        }

        if (!is_string($chosen)) {
            return null;
        }

        return $chosen;
    }

    /**
     * @param object $context
     * @param string $name
     * @param mixed  $missing
     * @return mixed
     */
    private static function read($context, $name, $missing = null)
    {
        if (is_object($context) && property_exists($context, $name)) {
            return $context->{$name};
        }

        return $missing;
    }
}
