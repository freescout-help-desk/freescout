<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // This has to be done here to avoid "Driver [polycast] is not supported" error
        $this->app[\Illuminate\Broadcasting\BroadcastManager::class]->extend('polycast', function ($app, array $config) {
            return new \App\Broadcasting\Broadcasters\PolycastBroadcaster();
        });

        // This is not needed as we define routes in PolyastServiceProvider
        //Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
