<?php

namespace Modules\TwoFactorAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

require_once __DIR__.'/../vendor/autoload.php';

// Module alias
define('TFA_MODULE', 'twofactorauth');

class TwoFactorAuthServiceProvider extends ServiceProvider
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
        $this->macros();
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(TFA_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(TFA_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(TFA_MODULE).'/js/module.js';
            return $javascripts;
        });

        \Eventy::addAction('user.profile.menu.after_profile', function($user) {
            $auth_user = \Auth::user();
            if ($user->id != $auth_user->id && !$auth_user->isAdmin()) {
                return;
            }
            echo \View::make('twofactorauth::partials/profile_menu', ['user' => $user])->render();
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[TFA_MODULE] = ['title' => __('Two-Factor Auth'), 'icon' => 'lock', 'order' => 250];

            return $sections;
        }, 15);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != TFA_MODULE) {
                return $settings;
            }
           
            $settings['twofactorauth.required'] = config('twofactorauth.required');

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != TFA_MODULE) {
                return $params;
            }

            $params = [
                'template_vars' => [
                    
                ],
                'settings' => [
                    'twofactorauth.required' => [
                        'env' => 'TWOFACTORAUTH_REQUIRED',
                    ],
                ]
            ];

            return $params;
        }, 20, 2);


        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != TFA_MODULE) {
                return $view;
            } else {
                return 'twofactorauth::settings';
            }
        }, 20, 2);

        \Eventy::addFilter('middleware.web.custom_handle.response', function($response, $request) {

            if (!$request->isMethod('GET')) {
                return $response;
            }
            if (!(int)config('twofactorauth.required')) {
                return $response;
            }
            $user = $request->user();
            if (!$user) {
                return $response;
            }

            if (!$user->hasTwoFactorEnabled()) {
                $route_name = $request->route()->getName();

                if (in_array($route_name, ['twofactorauth.user_auth_settings', 'twofactorauth.user_auth_settings_confirm', 'settings'])) {
                    return $response;
                } else {
                    return redirect()->route('twofactorauth.user_auth_settings', ['id' => $user->id]);
                }
            }

            return $response;
        }, 20, 2);
    }

    /**
     * Module macros.
     */
    public function macros()
    {
        \MacroableModels::addMacro(\App\User::class, 'twoFactorAuth', function() {
            if ($this->twoFactorAuth) {
                return $this->twoFactorAuth;
            }

            $this->twoFactorAuth = \DarkGhostHunter\Laraguard\Eloquent\TwoFactorAuthentication::where('user_id', $this->id)
                ->firstOrNew([
                    'user_id' => $this->id
                ]);

            return $this->twoFactorAuth;
            // config('laraguard.totp')
            //return $two_factor_auth;
            // return $this->morphOne(config('laraguard.model'), 'user', 'User')
            //     ->withDefault(config('laraguard.totp'));
        });

        \MacroableModels::addMacro(\App\User::class, 'hasTwoFactorEnabled', function() {
            return $this->twoFactorAuth()->isEnabled();
        });

        \MacroableModels::addMacro(\App\User::class, 'enableTwoFactorAuth', function() {
            $this->twoFactorAuth = $this->twoFactorAuth();
            $this->twoFactorAuth->enabled_at = now();

            if (config('laraguard.recovery.enabled')) {
                //$this->generateRecoveryCodes();
            }

            $this->twoFactorAuth->save();

            //event(new \DarkGhostHunter\Laraguard\Events\TwoFactorEnabled($this));
        });

        \MacroableModels::addMacro(\App\User::class, 'disableTwoFactorAuth', function() {
            $this->twoFactorAuth = $this->twoFactorAuth();
            $this->twoFactorAuth->flushAuth()->save();

            //event(new Events\TwoFactorDisabled($this));
        });
        
        \MacroableModels::addMacro(\App\User::class, 'createTwoFactorAuth', function() {
            $this->twoFactorAuth = $this->twoFactorAuth();

            if ($this->twoFactorAuth->label != $this->twoFactorLabel()
                || $this->twoFactorAuth->recovery_codes
                || $this->twoFactorAuth->recovery_codes_generated_at
                || $this->twoFactorAuth->safe_devices
                || $this->twoFactorAuth->enabled_at
            ) {
                $this->twoFactorAuth
                    ->flushAuth()
                    ->setAttribute('label', $this->twoFactorLabel())
                    ->save();
            }

            return $this->twoFactorAuth;
        });   
             
        \MacroableModels::addMacro(\App\User::class, 'twoFactorLabel', function() {
            return $this->getAttribute('email');
        });        
             
        \MacroableModels::addMacro(\App\User::class, 'confirmTwoFactorAuth', function(string $code) {
            if ($this->hasTwoFactorEnabled()) {
                return true;
            }

            if ($this->validateCode($code)) {
                $this->enableTwoFactorAuth();
                return true;
            }

            return false;
        });        

        \MacroableModels::addMacro(\App\User::class, 'validateCode', function($code) {
            return $this->twoFactorAuth()->validateCode($code);
        });

        \MacroableModels::addMacro(\App\User::class, 'validateTwoFactorCode', function($code = null) {
            if (! $code || ! $this->hasTwoFactorEnabled()) {
                return false;
            }

            return $this->useRecoveryCode($code) || $this->validateCode($code);
        });
        
        \MacroableModels::addMacro(\App\User::class, 'makeTwoFactorCode', function($at = 'now', int $offset = 0) {
            return $this->twoFactorAuth->makeCode($at, $offset);
        });      
          
        \MacroableModels::addMacro(\App\User::class, 'hasRecoveryCodes', function() {
            return $this->twoFactorAuth()->containsUnusedRecoveryCodes();
        });          

        \MacroableModels::addMacro(\App\User::class, 'getRecoveryCodes', function() {
            return $this->twoFactorAuth()->recovery_codes ?? collect();
        });

        \MacroableModels::addMacro(\App\User::class, 'generateRecoveryCodes', function() {
            list($enabled, $amount, $length) = array_values(config('laraguard.recovery'));

            $this->twoFactorAuth = $this->twoFactorAuth();
            $this->twoFactorAuth->recovery_codes = config('laraguard.model')::generateRecoveryCodes($amount, $length);
            $this->twoFactorAuth->recovery_codes_generated_at = now();
            $this->twoFactorAuth->save();

            //event(new Events\TwoFactorRecoveryCodesGenerated($this));

            return $this->twoFactorAuth->recovery_codes;
        });

        \MacroableModels::addMacro(\App\User::class, 'useRecoveryCode', function(string $code) {
            $this->twoFactorAuth = $this->twoFactorAuth();
            
            if (! config('laraguard.recovery.enabled') || ! $this->twoFactorAuth->setRecoveryCodeAsUsed($code)) {
                return false;
            }

            $this->twoFactorAuth->save();

            // if (! $this->hasRecoveryCodes()) {
            //     event(new Events\TwoFactorRecoveryCodesDepleted($this));
            // }

            return true;
        });

        \MacroableModels::addMacro(\App\User::class, 'addSafeDevice', function(Request $request) {
            $this->twoFactorAuth = $this->twoFactorAuth();
            
            $devices = collect($this->twoFactorAuth->safe_devices)->push([
                '2fa_remember' => $token = $this->generateTwoFactorRemember(),
                'ip'           => $request->ip(),
                'added_at'     => now()->timestamp,
            ])->sortByDesc('added_at');

            if ($devices->count() > $max = config('laraguard.safe_devices.max_devices')) {
                $devices = $devices->slice(0, $max)->values();
            }

            $this->twoFactorAuth->safe_devices = $devices;

            $this->twoFactorAuth->save();

            cookie()->queue('2fa_remember', $token, config('laraguard.safe_devices.expiration_days', 0) * 1440);

            return $token;
        });
        
        \MacroableModels::addMacro(\App\User::class, 'generateTwoFactorRemember', function() {
            return config('laraguard.model')::generateDefaultTwoFactorRemember();
        });        
        
        \MacroableModels::addMacro(\App\User::class, 'flushSafeDevices', function() {
            return $this->twoFactorAuth->setAttribute('safe_devices', null)->save();
        });   
             
        \MacroableModels::addMacro(\App\User::class, 'safeDevices', function() {
            return $this->twoFactorAuth->safe_devices ?? collect();
        });   

        \MacroableModels::addMacro(\App\User::class, 'isSafeDevice', function(Request $request) {
            $timestamp = $this->twoFactorAuth()->getSafeDeviceTimestamp(
                $this->getTwoFactorRememberFromRequest($request)
            );

            if ($timestamp) {
                return $timestamp->addDays(config('laraguard.safe_devices.expiration_days'))->isFuture();
            }

            return false;
        });
        
        \MacroableModels::addMacro(\App\User::class, 'getTwoFactorRememberFromRequest', function(Request $request) {
            return $request->cookie('2fa_remember');
        });    
            
        \MacroableModels::addMacro(\App\User::class, 'isNotSafeDevice', function(Request $request) {
            return ! $this->isSafeDevice($request);
        });

        // $user = \App\User::find(1);
        
        // //echo (int)$user->twoFactorAuth()->isEnabled();
        // echo $user->enableTwoFactorAuth();
        // echo $user->disableTwoFactorAuth();
        // echo (int)$user->hasTwoFactorEnabled();
        // //echo (int)$user->twoFactorAuth()->digits;
        // exit();
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
            __DIR__.'/../Config/config.php' => config_path('twofactorauth.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'twofactorauth'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/twofactorauth');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/twofactorauth';
        }, \Config::get('view.paths')), [$sourcePath]), 'twofactorauth');
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
