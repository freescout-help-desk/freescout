<?php

namespace Modules\Customization\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('CUST_MODULE', 'customization');

class CustomizationServiceProvider extends ServiceProvider
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
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(CUST_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            //$javascripts[] = \Module::getPublicPath(CUST_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(CUST_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['customization'] = ['title' => __('Customization'), 'icon' => 'adjust', 'order' => 200];

            return $sections;
        }, 15);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != 'customization') {
                return $settings;
            }
           
            $settings['customization_logo'] = config('customization.customization_logo');
            $settings['customization_banner'] = config('customization.customization_banner');
            $settings['customization_favicon'] = config('customization.customization_favicon');
            $settings['customization_primary_color'] = config('customization.customization_primary_color');
            $settings['customization_footer'] = config('customization.customization_footer');
            $settings['customization_css'] = base64_decode(config('customization.customization_css'));

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'customization') {
                return $params;
            }

            $params['settings'] = [
                'customization_logo' => [
                    'env' => 'CUSTOMIZATION_LOGO',
                ],
                'customization_banner' => [
                    'env' => 'CUSTOMIZATION_BANNER',
                ],
                'customization_favicon' => [
                    'env' => 'CUSTOMIZATION_FAVICON',
                ],
                'customization_primary_color' => [
                    'env' => 'CUSTOMIZATION_PRIMARY_COLOR',
                ],
                'customization_footer' => [
                    'env' => 'CUSTOMIZATION_FOOTER',
                ],
                'customization_css' => [
                    'env' => 'CUSTOMIZATION_CSS',
                    'env_encode' => true,
                ],
            ];

            return $params;
        }, 20, 2);


        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'customization') {
                return $view;
            } else {
                return 'customization::settings';
            }
        }, 20, 2);

        // Before saving settings
        \Eventy::addFilter('settings.before_save', function($request, $section, $settings) {
            if ($section != 'customization') {
                return $request;
            }

            $imgs = [
                'customization_logo',
                'customization_banner',
                'customization_favicon',
            ];

            $new_settings = [];

            try {
                foreach ($imgs as $img) {
                    $file_name = config('customization.'.$img);
                    // Remove.
                    if ($request->has($img.'_remove') && (int)$request->get($img.'_remove')) {
                        if ($file_name) {
                            \Helper::uploadedFileRemove($file_name);
                        }
                        $file_name = '';
                    }
                    $file_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                    if ($img == 'customization_favicon') {
                        $file_types = ['ico'];
                    }

                    $new_settings[$img] = $this->uploadFile($img, $file_name, $request, $file_types);
                }
            } catch (\Exception $e) {
                \Helper::addSessionError(__('Error occured').': '.$e->getMessage(), $img);
            }

            $request->merge(['settings' => array_merge($request->settings ?? [], $new_settings)]);

            return $request;
        }, 20, 3);

        \Eventy::addFilter('layout.header_logo', function($value) {
            $custom_logo = config('customization.customization_logo');
            if ($custom_logo) {
                return \Helper::uploadedFileUrl($custom_logo);
            }
            return $value;
        });

        \Eventy::addFilter('login.banner', function($value) {
            $custom_banner = config('customization.customization_banner');
            if ($custom_banner) {
                return \Helper::uploadedFileUrl($custom_banner);
            }
            return $value;
        });

        \Eventy::addAction('layout.head', function() {
            $custom_favicon = config('customization.customization_favicon');
            if ($custom_favicon) {
                echo '<link rel="shortcut icon" type="image/x-icon" href="'.\Helper::uploadedFileUrl($custom_favicon).'" />';
            }
        });

        \Eventy::addFilter('footer.text', function($value) {
            $footer_text = config('customization.customization_footer');

            if (strip_tags($footer_text)) {
                return $footer_text;
            }
            return $value;
        });

        \Eventy::addAction('layout.body_bottom', function() {
            $css = trim(strip_tags(base64_decode(config('customization.customization_css'))));

            if ($css) {
                ?><style type="text/css"><?php echo $css ?></style><?php
            }
        });
    }

    public function uploadFile($name, $value, $request, $file_types)
    {
        if (!$request->has($name)) {
            return $value;
        }

        $file = $request->file($name);

        $file_path = \Helper::uploadFile($file, $file_types);

        if ($file_path) {
            // Remove current file.
            if (!empty($value)) {
                \Storage::delete('uploads/'.$value);
            }

            return basename($file_path);
        } else {
            return $value;
        }
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
            __DIR__.'/../Config/config.php' => config_path('customization.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'customization'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/customization');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/customization';
        }, \Config::get('view.paths')), [$sourcePath]), 'customization');
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
