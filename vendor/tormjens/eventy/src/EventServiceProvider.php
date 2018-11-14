<?php

namespace TorMorten\Eventy;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Registers the eventy singleton.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('eventy', function ($app) {
            return new Events();
        });
    }
}
