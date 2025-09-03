<?php namespace Devfactory\Minify\Providers;

use Devfactory\Minify\Contracts\MinifyInterface;
use JShrink\Minifier;

class JavaScript extends BaseProvider implements MinifyInterface
{
    /**
     *  The extension of the outputted file.
     */
    const EXTENSION = '.js';

    /**
     * @return string
     */
    public function minify()
    {
        $minified = Minifier::minify($this->appended);

        return $this->put($minified);
    }

    /**
     * @param $file
     * @param array $attributes
     * @return string
     */
    public function tag($file, array $attributes)
    {
        $attributes = array('src' => $file) + $attributes;

        return "<script {$this->attributes($attributes)}></script>" . PHP_EOL;
    }
}
