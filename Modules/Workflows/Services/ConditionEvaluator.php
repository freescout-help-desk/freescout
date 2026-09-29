<?php

namespace Modules\Workflows\Services;

class ConditionEvaluator
{
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
}
