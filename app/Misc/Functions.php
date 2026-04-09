<?php

if (! function_exists('__j')) {
    /**
     * Translate the given message and prepare it for using in JS.
     */
    function __j($key, $replace = [], $locale = null)
    {
        $text = __($key, $replace, $locale);

        return strtr($text, [
            '"' => '&quot;',
            "'" => '&#039;',
        ]);
    }

    /**
     * Translates and escapes the text for using in HTML.
     */
    function __h($key, $replace = [], $locale = null, $escape_replacements = false)
    {
        if ($escape_replacements) {
            foreach ($replace as $key => $value) {
                $replace[$key] = htmlspecialchars($value);
            }
        }
        return __(htmlspecialchars($key), $replace, $locale);
    }

    /**
     * Strips unsafe tags.
     */
    function safe_raw_html($text, $allowed_tags = [])
    {
        return \Helper::stripDangerousTags($text ?? '', $allowed_tags);
    }

    /**
     * Translates and strips unsafe tags.
     */
    function __safe_raw_html($key, $replace = [], $locale = null, $allowed_tags = [])
    {
        $text = __($key ?? '', $replace, $locale);

        return \Helper::stripDangerousTags($text, $allowed_tags);
    }
}