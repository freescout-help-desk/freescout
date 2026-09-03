<?php

namespace App\Minify\Facades;

use Illuminate\Support\Facades\Facade;

class MinifyFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'minify';
    }
}
