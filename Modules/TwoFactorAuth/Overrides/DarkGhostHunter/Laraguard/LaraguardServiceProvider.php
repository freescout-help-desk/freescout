<?php

namespace DarkGhostHunter\Laraguard;

use Illuminate\Routing\Router;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\Factory;

class LaraguardServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../Config/laraguard.php', 'laraguard');
    }

    /**
     * Bootstrap the application services.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @param  \Illuminate\Contracts\Validation\Factory  $validator
     * @return void
     */
    public function boot(Repository $config, Router $router, Dispatcher $dispatcher, Factory $validator)
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laraguard');
        //$this->loadFactoriesFrom(__DIR__ . '/../database/factories');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'laraguard');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerListener($config, $dispatcher);
        $this->registerMiddleware($router);
        $this->registerRules($validator);
        $this->registerRoutes($config, $router);

        if ($this->app->runningInConsole()) {
            $this->publishFiles();
        }
    }

    /**
     * Register a listeners to tackle authentication.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    protected function registerListener(Repository $config, Dispatcher $dispatcher)
    {
        if (! $listener = $config['laraguard.listener']) {
            return;
        }

        $this->app->singleton(Contracts\TwoFactorListener::class, function ($app) use ($listener) {
            return new $listener($app['config'], $app['request']);
        });

        $dispatcher->listen(Attempting::class, Contracts\TwoFactorListener::class . '@saveCredentials');
        $dispatcher->listen(Validated::class, Contracts\TwoFactorListener::class . '@checkTwoFactor');
    }

    /**
     * Register the middleware.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerMiddleware(Router $router)
    {
        $router->aliasMiddleware('2fa.require', Http\Middleware\RequireTwoFactorEnabled::class);
        $router->aliasMiddleware('2fa.confirm', Http\Middleware\ConfirmTwoFactorCode::class);
    }

    /**
     * Register custom validation rules.
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $validator
     * @return void
     */
    protected function registerRules(Factory $validator)
    {
        $validator->extendImplicit('totp_code', Rules\TotpCodeRule::class, trans('laraguard::validation.totp_code'));
    }

    /**
     * Register the routes for 2FA Code confirmation.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerRoutes(Repository $config, Router $router)
    {
        if ($view = $config->get('laraguard.confirm.view')) {
            $router->get('2fa/confirm', $view)
                ->middleware('web')
                ->name('2fa.confirm');
        }

        if ($action = $config->get('laraguard.confirm.action')) {
            $router->post('2fa/confirm', $action)
                ->middleware('web');
        }
    }

    /**
     * Publish config, view and migrations files.
     *
     * @return void
     */
    protected function publishFiles()
    {
        $this->publishes([
            __DIR__ . '/../config/laraguard.php' => config_path('laraguard.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laraguard'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/laraguard'),
        ], 'translations');

        // We will allow the publishing for the Two Factor Authentication migration that
        // holds the TOTP data, only if it wasn't published before, avoiding multiple
        // copies for the same migration, which can throw errors when re-migrating.
        if (! class_exists('CreateTwoFactorAuthenticationsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/2020_04_02_000000_create_two_factor_authentications_table.php' => database_path('migrations/' . now()->format('Y_m_d_His') . '_create_two_factor_authentications_table.php'),
            ], 'migrations');
        }
    }
}
