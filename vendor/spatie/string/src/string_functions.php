<?php

use Spatie\String\Str;

/**
 * @param string $string
 *
 * @return \Spatie\String\Str
 */
function string($string = '')
{
    return new Str($string);
}
