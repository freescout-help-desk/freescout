<?php namespace Devfactory\Minify\Providers;

use Devfactory\Minify\Contracts\MinifyInterface;
use CssMinifier;

class StyleSheet extends BaseProvider implements MinifyInterface
{
    /**
     *  The extension of the outputted file.
     */
    const EXTENSION = '.css';

    /**
     * @return string
     */
    public function minify()
    {
        $minified = new CssMinifier($this->appended);

        return $this->put($minified->getMinified());
    }

    /**
     * @param $file
     * @param array $attributes
     * @return string
     */
    public function tag($file, array $attributes = array())
    {
        $attributes = array('href' => $file, 'rel' => 'stylesheet') + $attributes;

        return "<link {$this->attributes($attributes)}>" . PHP_EOL;
    }
}
