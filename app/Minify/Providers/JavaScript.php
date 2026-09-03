<?php

namespace App\Minify\Providers;

use JShrink\Minifier;

class JavaScript extends BaseProvider
{
    const EXTENSION = '.js';

    /**
     * Minify and write the JavaScript bundle.
     *
     * @return string
     */
    public function minify()
    {
        return $this->put(Minifier::minify($this->appended));
    }

    /**
     * Render a JavaScript tag.
     *
     * @param string $file
     * @param array  $attributes
     *
     * @return string
     */
    public function tag($file, array $attributes = [])
    {
        $attributes = ['src' => $file] + $attributes;

        return '<script '.$this->attributes($attributes).'></script>'.PHP_EOL;
    }
}
