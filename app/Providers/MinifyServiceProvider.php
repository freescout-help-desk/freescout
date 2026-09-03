<?php

namespace App\Providers;

use App\Minify\Minify;
use Illuminate\Support\ServiceProvider;

class MinifyServiceProvider extends ServiceProvider
{
    /**
     * Register the minify service.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('minify', function ($app) {
            return new Minify(config('minify.config'), $app->environment());
        });
    }
}
