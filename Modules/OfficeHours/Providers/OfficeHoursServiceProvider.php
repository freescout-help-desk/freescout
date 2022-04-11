<?php

namespace Modules\OfficeHours\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('OFFICEHOURS_MODULE', 'officehours');

class OfficeHoursServiceProvider extends ServiceProvider
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
        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[OFFICEHOURS_MODULE] = ['title' => __('Office Hours'), 'icon' => 'calendar', 'order' => 150];

            return $sections;
        }, 13); 

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != OFFICEHOURS_MODULE) {
                return $settings;
            }
           
            $settings['officehours.schedule'] = json_decode(config('officehours.schedule'), true);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != OFFICEHOURS_MODULE) {
                return $params;
            }

            $params = [
                'settings' => [
                    'officehours.schedule' => [
                        'env' => 'OFFICEHOURS_SCHEDULE',
                        //'env_encode' => true,
                    ],
                ]
            ];

            return $params;
        }, 20, 2);

        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != OFFICEHOURS_MODULE) {
                return $view;
            } else {
                return 'officehours::settings';
            }
        }, 20, 2);

        // On settings save
        \Eventy::addFilter('settings.before_save', function($request, $section, $settings) {
            if ($section != OFFICEHOURS_MODULE) {
                return $request;
            }

            $settings = $request->settings['officehours.schedule'];

            foreach ($settings as $day => $rows) {
                foreach ($rows as $i => $row) {
                    if (!empty($row['from']) && $row['from'] == 'off') {
                        $row['to'] = 'off';
                    }
                    if (!empty($row['to']) && $row['to'] == 'off') {
                        $row['from'] = 'off';
                    }

                    if (empty($row['from']) || empty($row['to'])) {
                        $row['from'] = null;
                        $row['to'] = null;
                    }

                    $settings[$day][$i] = $row;
                }
            }

            $request->merge([
                'settings' => [
                    'officehours.schedule' => $settings
                ]
            ]);

            return $request;
        }, 20, 4);

        \Eventy::addFilter('autoreply.should_send', function($should_send, $conversation) {
            if (!$should_send) {
                return $should_send;
            }
            $schedule = json_decode(config('officehours.schedule'), true);

            if (!$schedule) {
                return $should_send;
            }

            $now = now();
            $day = $now->dayOfWeek;

            if (!empty($schedule[$day]) && !empty($schedule[$day][0])) {
                if ($schedule[$day][0]['from'] == 'off' || $schedule[$day][0]['to'] == 'off') {
                    return true;
                }

                if (!empty($schedule[$day][0]['from']) && !empty($schedule[$day][0]['to'])) {
                    try {
                        $from_time = Carbon::parse($now->format('Y-m-d').' '.$schedule[$day][0]['from'].':00');
                        $to_time = Carbon::parse($now->format('Y-m-d').' '.$schedule[$day][0]['to'].':00');
                    } catch (\Exception $e) {
                        return true;
                    }

                    if ($now->greaterThanOrEqualTo($from_time) && $now->lessThanOrEqualTo($to_time)) {
                        // Working hours.
                        return false;
                    } else {
                        return true;
                    }
                }
            }

            return $should_send;
        }, 20, 2);
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
            __DIR__.'/../Config/config.php' => config_path('officehours.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'officehours'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/officehours');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/officehours';
        }, \Config::get('view.paths')), [$sourcePath]), 'officehours');
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
