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
     * Translate the given message and prepare it for using in HTML.
     *
     */
    function __h($key, $replace = [], $locale = null)
    {
        $text = __($key, $replace, $locale);

        return htmlspecialchars($text);
    }
}