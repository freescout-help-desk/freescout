<?php

namespace Spatie\String\Integrations;

use Spatie\String\Str;

class Underscore
{
    protected $underscoreMethods =
        [
            //name, firstArgumentIsString, returnsAString
            'accord'        => [false, true],
            'random'        => [false, true],
            'quickRandom'   => [false, true],
            'randomStrings' => [false, true],
            'endsWith'      => [true, false],
            'isIp'          => [true, false],
            'isEmail'       => [true, false],
            'isUrl'         => [true, false],
            'startsWith'    => [true, false],
            'find'          => [true, false],
            'slice'         => [true, false],
            'sliceFrom'     => [true, true],
            'sliceTo'       => [true, true],
            'baseClass'     => [true, true],
            'prepend'       => [true, true],
            'append'        => [true, true],
            'limit'         => [true, true],
            'remove'        => [true, true],
            'replace'       => [true, true],
            'toggle'        => [true, true],
            'slugify'       => [true, true],
            'explode'       => [true, false],
            'lower'         => [true, true],
            'plural'        => [true, true],
            'singular'      => [true, true],
            'upper'         => [true, true],
            'title'         => [true, true],
            'words'         => [true, true],
            'toPascalCase'  => [true, true],
            'toSnakeCase'   => [true, true],
            'toCamelCase'   => [true, true],
        ];

    /**
     * @param \Spatie\String\Str $string
     * @param string             $method
     * @param array              $args
     *
     * @return mixed|\Spatie\String\Str
     */
    public function call($string, $method, $args)
    {
        if ($this->methodUsesStringAsFirstArgument($method)) {
            array_unshift($args, (string) $string);
        }

        $underscoreResult = call_user_func_array(['Underscore\Types\Strings', $method], $args);

        if ($this->methodReturnsAString($method)) {
            return new Str($underscoreResult);
        }

        return $underscoreResult;
    }

    /**
     * Determine if the given method is supported.
     *
     * @param $method
     *
     * @return bool
     */
    public function isSupportedMethod($method)
    {
        return array_key_exists($method, $this->underscoreMethods);
    }

    /**
     * Determine if the given method uses the string as it's first argument.
     *
     * @param $method
     *
     * @return bool
     */
    public function methodUsesStringAsFirstArgument($method)
    {
        return $this->isSupportedMethod($method) ? $this->underscoreMethods[$method][0] : false;
    }

    /**
     * Determine if the given method returns a string.
     *
     * @param $method
     *
     * @return bool
     */
    public function methodReturnsAString($method)
    {
        return $this->isSupportedMethod($method) ? $this->underscoreMethods[$method][1] : false;
    }
}
