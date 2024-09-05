<?php namespace Devfactory\Minify\Contracts;

interface MinifyInterface {

    /**
     * @return mixed
     */
    public function minify();

    /**
     * @param  string $file
     * @param  array  $attributes
     * @return mixed
     */
    public function tag($file, array $attributes);
}
