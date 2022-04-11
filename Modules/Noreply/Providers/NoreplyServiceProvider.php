<?php

namespace Modules\Noreply\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('NOREPLY_MODULE', 'noreply');

class NoreplyServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(NOREPLY_MODULE).'/js/module.js';
            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view') || \Route::is('conversations.create')) {
                $emails = self::getEmails();
                if (count($emails)) {
                    echo 'noreplyInitConv(["'.implode('","', $emails).'"], '.__('"Sending to :email email. Are you sure?"').');';
                } else {
                    echo 'noreplyInitConv([], '.__('"Sending to :email email. Are you sure?"').');';
                }
            }
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[NOREPLY_MODULE] = ['title' => __('Noreply Emails'), 'icon' => 'remove-circle', 'order' => 355];

            return $sections;
        }, 18); 

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != NOREPLY_MODULE) {
                return $settings;
            }
           
            $settings['noreply.emails_custom'] = json_decode(config('noreply.emails_custom'), true);
            $settings['noreply.emails_default'] = config('noreply.emails_default');

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != NOREPLY_MODULE) {
                return $params;
            }

            $params = [
                'settings' => [
                    'noreply.emails_custom' => [
                        'env' => 'NOREPLY_EMAILS_CUSTOM',
                        //'env_encode' => true,
                    ]
                ]
            ];

            return $params;
        }, 20, 2);

        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != NOREPLY_MODULE) {
                return $view;
            } else {
                return 'noreply::settings';
            }
        }, 20, 2);

        // On settings save
        \Eventy::addFilter('settings.before_save', function($request, $section, $settings) {
            if ($section != NOREPLY_MODULE) {
                return $request;
            }
            
            $emails_custom = [];

            if (!empty($request->settings['noreply.emails_custom'])) {
                $emails_custom = explode("\n", str_replace("\r", "", $request->settings['noreply.emails_custom']));
            }

            $emails_custom_array = [];
            foreach ($emails_custom as $emails_item) {
                $emails_item = filter_var($emails_item, FILTER_SANITIZE_EMAIL);
                if ($emails_item) {
                    $emails_custom_array[] = strtolower($emails_item);
                }
            }

            $request->merge([
                'settings' => [
                    'noreply.emails_custom' => $emails_custom_array
                ]
            ]);

            return $request;
        }, 20, 4);

        \Eventy::addFilter('autoreply.should_send', function($should_send, $conversation) {
            if (!$should_send) {
                return $should_send;
            }
            
            // Check customer email
            if (self::isNoreplyEmail($conversation->customer_email)) {
                return false;
            }

            return $should_send;
        }, 20, 2);
    }

    public static function isNoreplyEmail($email)
    {
        $emails = self::getEmails();

        foreach ($emails as $noreply_email) {
            $noreply_email = str_replace('-', '\-?', $noreply_email);
            $regex = '/.*'.$noreply_email.'*./i';
            if (preg_match($regex, $email)) {
                return true;
            }
        }

        return false;
    }

    public static function getEmails()
    {
        $emails_custom = json_decode(config('noreply.emails_custom'), true);
        $emails_default = config('noreply.emails_default');

        $list_raw = array_unique(array_merge($emails_default, $emails_custom));

        $result = [];
        foreach ($list_raw as $email) {
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            if ($email) {
                $result[] = $email;
            }
        }

        return $result;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('noreply.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'noreply'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/noreply');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/noreply';
        }, \Config::get('view.paths')), [$sourcePath]), 'noreply');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
