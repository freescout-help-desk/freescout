<?php namespace Devfactory\Minify\Facades;

use Illuminate\Support\Facades\Facade;

class MinifyFacade extends Facade
{
    /**
     * Name of the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'minify';
    }
}
