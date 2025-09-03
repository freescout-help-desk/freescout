<?php

namespace Codedge\Updater;

use Illuminate\Support\ServiceProvider;
use Codedge\Updater\Commands\CheckForUpdate;
use Illuminate\Contracts\Container\Container;

/**
 * UpdaterServiceProvider.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class UpdaterServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/self-update.php' => config_path('self-update.php'),
        ], 'config');

        $this->loadViews();
    }

    /**
     * Set up views.
     */
    protected function loadViews()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'self-update');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/self-update'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/self-update.php', 'self-update');

        $this->registerCommands();
        $this->registerManager();
    }

    /**
     * Register the package its commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            CheckForUpdate::class,
        ]);

        // Register custom commands from config
        collect(config('self-update.artisan_commands.pre_update'))->each(function ($command) {
            $this->commands([$command['class']]);
        });
        collect(config('self-update.artisan_commands.post_update'))->each(function ($command) {
            $this->commands([$command['class']]);
        });
    }

    /**
     * Register the manager class.
     */
    protected function registerManager()
    {
        $this->app->singleton('updater', function (Container $app) {
            return new UpdaterManager($app);
        });
        $this->app->alias('updater', UpdaterManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'updater',
        ];
    }
}
