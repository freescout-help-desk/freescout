<?php

/**
 * Class for defining common app functions.
 */
namespace App\Misc;

class Helper
{
    /**
     * Default query cache time in seconds for remember() function.
     */
    const QUERY_CACHE_TIME = 1000;

    /**
     * Text preview max length.
     */
    const PREVIEW_MAXLENGTH = 255;

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

    /**
     * Get preview of the text in a plain form.
     */
    public static function textPreview($text, $length = self::PREVIEW_MAXLENGTH)
    {
        // Remove all kinds of spaces after tags
        // https://stackoverflow.com/questions/3230623/filter-all-types-of-whitespace-in-php
        $text = preg_replace("/^(.*)>[\r\n]*\s+/mu", '$1>', $text);

        $text = strip_tags($text);
        $text = preg_replace('/\s+/mu', ' ', $text);

        // Trim
        $text = trim($text);
        $text = preg_replace('/^\s+/mu', '', $text);

        // Causes "General error: 1366 Incorrect string value"
        // Remove "undetectable" whitespaces
        // $whitespaces = ['%81', '%7F', '%C5%8D', '%8D', '%8F', '%C2%90', '%C2', '%90', '%9D', '%C2%A0', '%A0', '%C2%AD', '%AD', '%08', '%09', '%0A', '%0D'];
        // $text = urlencode($text);
        // foreach ($whitespaces as $char) {
        //     $text = str_replace($char, ' ', $text);
        // }
        // $text = urldecode($text);

        $text = trim(preg_replace('/[ ]+/', ' ', $text));

        $text = mb_substr($text, 0, $length);

        return $text;
    }
}
