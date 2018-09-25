<?php namespace Barryvdh\TranslationManager;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ManagerServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        // Register the config publish path
        $configPath = __DIR__ . '/../config/translation-manager.php';
        $this->mergeConfigFrom($configPath, 'translation-manager');
        $this->publishes([$configPath => config_path('translation-manager.php')], 'config');

        $this->app->singleton('translation-manager', function ($app) {
            $manager = $app->make('Barryvdh\TranslationManager\Manager');
            return $manager;
        });

        $this->app->singleton('command.translation-manager.reset', function ($app) {
            return new Console\ResetCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.reset');

        $this->app->singleton('command.translation-manager.import', function ($app) {
            return new Console\ImportCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.import');

        $this->app->singleton('command.translation-manager.find', function ($app) {
            return new Console\FindCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.find');

        $this->app->singleton('command.translation-manager.export', function ($app) {
            return new Console\ExportCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.export');

        $this->app->singleton('command.translation-manager.clean', function ($app) {
            return new Console\CleanCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.clean');
	}

    /**
	 * Bootstrap the application events.
	 *
     * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function boot(Router $router)
	{
        $viewPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewPath, 'translation-manager');
        $this->publishes([
            $viewPath => base_path('resources/views/vendor/translation-manager'),
        ], 'views');

        $migrationPath = __DIR__.'/../database/migrations';
        $this->publishes([
            $migrationPath => base_path('database/migrations'),
        ], 'migrations');

        $config = $this->app['config']->get('translation-manager.route', []);
        $config['namespace'] = 'Barryvdh\TranslationManager';

        $router->group($config, function($router)
        {
            $router->get('view/{groupKey?}', 'Controller@getView')->where('groupKey', '.*');
            $router->get('/{groupKey?}', 'Controller@getIndex')->where('groupKey', '.*');
            $router->post('/add/{groupKey}', 'Controller@postAdd')->where('groupKey', '.*');
            $router->post('/edit/{groupKey}', 'Controller@postEdit')->where('groupKey', '.*');
            $router->post('/groups/add', 'Controller@postAddGroup');
            $router->post('/delete/{groupKey}/{translationKey}', 'Controller@postDelete')->where('groupKey', '.*');
            $router->post('/import', 'Controller@postImport');
            $router->post('/find', 'Controller@postFind');
            $router->post('/locales/add', 'Controller@postAddLocale');
            $router->post('/locales/remove', 'Controller@postRemoveLocale');
            $router->post('/publish/{groupKey}', 'Controller@postPublish')->where('groupKey', '.*');
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('translation-manager',
            'command.translation-manager.reset',
            'command.translation-manager.import',
            'command.translation-manager.find',
            'command.translation-manager.export',
            'command.translation-manager.clean'
        );
	}

}
