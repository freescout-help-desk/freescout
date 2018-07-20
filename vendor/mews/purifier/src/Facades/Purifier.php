<?php

namespace Mews\Purifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mews\Purifier
 */
class Purifier extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'purifier';
    }
}
