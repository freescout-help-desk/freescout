<?php

namespace Javoscript\MacroableModels\Facades;

use Illuminate\Support\Facades\Facade;

class MacroableModels extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'macroable-models';
    }
}
