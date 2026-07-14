<?php

namespace Modules\ArmsReports\Services;

/**
 * Small statistical helpers shared by the report services.
 */
class Stats
{
    /**
     * Median of an array of numbers. Returns null for an empty set.
     */
    public static function median(array $values)
    {
        if (!count($values)) {
            return null;
        }
        sort($values);
        $count = count($values);
        $middle = (int) floor($count / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Humanize a duration in seconds: "3h 12m", "2d 4h", "45m", "—" for null.
     */
    public static function duration($seconds)
    {
        if ($seconds === null) {
            return '—';
        }
        $seconds = (int) round($seconds);
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $days.'d '.$hours.'h';
        }
        if ($hours > 0) {
            return $hours.'h '.$minutes.'m';
        }

        return $minutes.'m';
    }

    /**
     * Bucket a reply count into the agreed brackets.
     */
    public static function replyBracket($count)
    {
        if ($count <= 1) {
            return '1';
        }
        if ($count <= 3) {
            return '2–3';
        }
        if ($count <= 6) {
            return '4–6';
        }

        return '7+';
    }

    public static function bracketLabels()
    {
        return ['1', '2–3', '4–6', '7+'];
    }
}
