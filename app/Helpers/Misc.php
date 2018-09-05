<?php

/**
 * Class for defining common app functions.
 */
namespace App\Helpers;

class Misc
{
    // Default query cache time in seconds for remember() function.
    const QUERY_CACHE_TIME = 1000;

    /**
     * Cache time of the DB query.
     */
    public static function cacheTime($enabled = true)
    {
        if ($enabled) {
            return self::QUERY_CACHE_TIME;
        } else {
            return 0;
        }
    }
}
