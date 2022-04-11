<?php

namespace BotMan\Drivers\Facebook\Providers;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Facebook\Commands\AddGreetingText;
use BotMan\Drivers\Facebook\Commands\AddPersistentMenu;
use BotMan\Drivers\Facebook\Commands\AddStartButtonPayload;
use BotMan\Drivers\Facebook\Commands\Nlp;
use BotMan\Drivers\Facebook\Commands\WhitelistDomains;
use BotMan\Drivers\Facebook\FacebookAudioDriver;
use BotMan\Drivers\Facebook\FacebookDriver;
use BotMan\Drivers\Facebook\FacebookFileDriver;
use BotMan\Drivers\Facebook\FacebookImageDriver;
use BotMan\Drivers\Facebook\FacebookLocationDriver;
use BotMan\Drivers\Facebook\FacebookVideoDriver;
use BotMan\Studio\Providers\StudioServiceProvider;
use Illuminate\Support\ServiceProvider;

class FacebookServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__.'/../../stubs/facebook.php' => config_path('botman/facebook.php'),
            ]);

            $this->mergeConfigFrom(__DIR__.'/../../stubs/facebook.php', 'botman.facebook');

            if ($this->app->runningInConsole()) {
                $this->commands([
                    Nlp::class,
                    AddGreetingText::class,
                    AddPersistentMenu::class,
                    AddStartButtonPayload::class,
                    WhitelistDomains::class,
                ]);
            }
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(FacebookDriver::class);
        DriverManager::loadDriver(FacebookAudioDriver::class);
        DriverManager::loadDriver(FacebookFileDriver::class);
        DriverManager::loadDriver(FacebookImageDriver::class);
        DriverManager::loadDriver(FacebookLocationDriver::class);
        DriverManager::loadDriver(FacebookVideoDriver::class);
    }

    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
