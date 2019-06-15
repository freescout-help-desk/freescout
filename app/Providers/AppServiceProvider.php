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

        // If APP_KEY is not set, redirect to /install.php
        if (!\Config::get('app.key') && !app()->runningInConsole() && !file_exists(storage_path('.installed'))) {
            // Not defined here yet
            //\Artisan::call("freescout:clear-cache");
            redirect(\Helper::getSubdirectory().'/install.php')->send();
        }

        // Process module registration error - disable module and show error to admin
        \Eventy::addFilter('modules.register_error', function ($exception, $module) {

            // request() does is empty at this stage
            if (!empty($_POST['action']) && $_POST['action'] == 'activate') {

                // During module activation in case of any error we have to deactivate module.
                \App\Module::deactiveModule($module->getAlias());

                // if (\App::runningInConsole()) {
                //     echo __('The plugin :module_name has been deactivated due to an error: :error_message', ['module_name' => $module->getName(), 'error_message' => $exception->getMessage()]);
                // } else {
                \Session::flash('flashes_floating', [[
                    'text' => __('The plugin :module_name has been deactivated due to an error: :error_message', ['module_name' => $module->getName(), 'error_message' => $exception->getMessage()]),
                    'type' => 'danger',
                    'role' => \App\User::ROLE_ADMIN,
                ]]);

                return;
            } elseif (empty($_POST)) {

                // failed to open stream: No such file or directory
                if (strstr($exception->getMessage(), 'No such file or directory')) {
                    \App\Module::deactiveModule($module->getAlias());

                    \Session::flash('flashes_floating', [[
                        'text' => __('The plugin :module_name has been deactivated due to an error: :error_message', ['module_name' => $module->getName(), 'error_message' => $exception->getMessage()]),
                        'type' => 'danger',
                        'role' => \App\User::ROLE_ADMIN,
                    ]]);
                }

                return;
            }

            return $exception;
        }, 10, 2);
    }
}
