<?php

namespace App\Minify\Providers;

use Wikimedia\Minify\CSSMin;

class StyleSheet extends BaseProvider
{
    const EXTENSION = '.css';

    /**
     * Minify and write the stylesheet bundle.
     *
     * @return string
     */
    public function minify()
    {
        return $this->put(CSSMin::minify($this->appended));
    }

    /**
     * Render a stylesheet tag.
     *
     * @param string $file
     * @param array  $attributes
     *
     * @return string
     */
    public function tag($file, array $attributes = [])
    {
        $attributes = ['href' => $file, 'rel' => 'stylesheet'] + $attributes;

        return '<link '.$this->attributes($attributes).'>'.PHP_EOL;
    }
}
