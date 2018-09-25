<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // To avoid MySQL error in packages:
        // "SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes"
        Schema::defaultStringLength(191);

        // Models observers
        \App\Mailbox::observe(\App\Observers\MailboxObserver::class);
        // Eloquent events for this table are not called automatically, so need to be called manually.
        //\App\MailboxUser::observe(\App\Observers\MailboxUserObserver::class);
        \App\User::observe(\App\Observers\UserObserver::class);
        \App\Conversation::observe(\App\Observers\ConversationObserver::class);
        \App\Thread::observe(\App\Observers\ThreadObserver::class);
        \Illuminate\Notifications\DatabaseNotification::observe(\App\Observers\DatabaseNotificationObserver::class);

        // Module functions
        $this->registerModuleFunctions();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Forse HTTPS if using CloudFlare "Flexible SSL"
        // https://support.cloudflare.com/hc/en-us/articles/200170416-What-do-the-SSL-options-mean-
        if (\Config::get('app.force_https') == 'true') {
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['SERVER_PORT'] = '443';
            $this->app['url']->forceScheme('https');
        }
    }

    /**
     * Register functions allowing modules to get/set their options.
     */
    public function registerModuleFunctions()
    {
        // At this stage class Module may be not defined yet, especially during upgrading
        // Without this check, `php artisan cache:clear` command may fail:
        //      In AppServiceProvider.php line XX:
        //      Class 'Module' not found

        if (!class_exists('Module')) {
            return;
        }

        \Module::macro('getOption', function($module_alias, $option_name, $default = false) {
            // If not passed, get default value from config 
            if (func_num_args() == 2) {
                $options = \Config::get(strtolower($module_alias).'.options');

                if (isset($options[$option_name]) && isset($options[$option_name]['default'])) {
                    $default = $options[$option_name]['default'];
                }
            }

            return \Option::get($module_alias.'.'.$option_name, $default);
        });
        \Module::macro('setOption', function($module_alias, $option_name, $option_value) {
            return \Option::set(strtolower($module_alias).'.'.$option_name, $option_value);
        });
    }
}
