<?php

namespace Mews\Purifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed clean($dirty, $config = null, \Closure $postCreateConfigHook = null)
 * @see \Mews\Purifier
 */
class Purifier extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'purifier';
    }
}
