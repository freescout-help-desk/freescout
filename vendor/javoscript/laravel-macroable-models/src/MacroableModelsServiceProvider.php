<?php

namespace Javoscript\MacroableModels;

use Illuminate\Support\ServiceProvider;

class MacroableModelsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('macroable-models', function() {
            return new MacroableModels();
        });
    }
}
