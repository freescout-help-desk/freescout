<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | This value is the version of your application. This value is used when
    | the framework needs to place the application's version in a notification
    | or any other location as required by the application or its packages.
    */

    'version' => '1.0.4',

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => 'FreeScout',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', date_default_timezone_get()),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    | locales: available locales
    */

    'locale'  => 'en',
    'locales' => ['en'],

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Settings: "single", "daily", "syslog", "errorlog"
    |
    */

    'log' => env('APP_LOG', 'daily'), // by default logs for 5 days are kept

    'log_level' => env('APP_LOG_LEVEL', 'error'),

    /*
    |--------------------------------------------------------------------------
    | FreeScout website
    |-------------------------------------------------------------------------
    */
    'freescout_url' => 'https://freescout.net',

    /*
    |--------------------------------------------------------------------------
    | FreeScout API
    |-------------------------------------------------------------------------
    */
    'freescout_api' => 'https://freescout.net/wp-json/',

    /*
    |--------------------------------------------------------------------------
    | FreeScout eepository
    |-------------------------------------------------------------------------
    */
    'freescout_repo' => 'https://github.com/freescout-helpdesk/freescout',

    /*
    |--------------------------------------------------------------------------
    | FreeScout email
    |-------------------------------------------------------------------------
    */
    'freescout_email' => 'support@freescout.net',

    /*
    |--------------------------------------------------------------------------
    | Parameters used to run queued jobs processing.
    | Checks for new jobs every 5 seconds.
    | Do not set more than 1 retry, as it may lead to sending repeated emails if one recipient fails
    | and another succeeds.
    |-------------------------------------------------------------------------
    */
    'queue_work_params' => ['--queue' => 'emails', '--sleep' => '5', '--tries' => '1'],

    /*
    |--------------------------------------------------------------------------
    | PHP extensions required by the app
    |-------------------------------------------------------------------------
    */
    'required_extensions' => ['mysql / mysqli', 'mbstring', 'xml', 'imap', /*'mcrypt' mcrypt is deprecated*/ 'json', 'gd', 'fileinfo', 'openssl', 'zip', 'tokenizer'/*, 'dom', 'xmlwriter', 'libxml', 'phar'*/],

    /*
    |--------------------------------------------------------------------------
    | Enable if using CloudFlare "Flexible SSL":
    | https://support.cloudflare.com/hc/en-us/articles/200170416-What-do-the-SSL-options-mean-
    |-------------------------------------------------------------------------
    */
    'force_https' => env('APP_FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Default options values for \Option::get()
    |--------------------------------------------------------------------------
    */
    'options' => [
        'alert_fetch'        => ['default' => false],
        'alert_fetch_period' => ['default' => 15], // min
        'email_branding'     => ['default' => true],
        'open_tracking'      => ['default' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Devfactory\Minify\MinifyServiceProvider::class,
        Barryvdh\Debugbar\ServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\PolycastServiceProvider::class,

        /*
         * Custom Service Providers...
         */
        // We need to be careful when deleting service providers
        // as Laravel config is cached with `config:cache`.
        // If we remove some service provider file from here and from disk,
        // when updating the app, users will receive "Class '...' not found" error,
        // because their cached config still has this service provider listed.

        // Autodiscovery did not work for this one, becasuse it's composer.json
        // does not have a `extra` section.
        Codedge\Updater\UpdaterServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App'          => Illuminate\Support\Facades\App::class,
        'Artisan'      => Illuminate\Support\Facades\Artisan::class,
        'Auth'         => Illuminate\Support\Facades\Auth::class,
        'Blade'        => Illuminate\Support\Facades\Blade::class,
        'Broadcast'    => Illuminate\Support\Facades\Broadcast::class,
        'Bus'          => Illuminate\Support\Facades\Bus::class,
        'Cache'        => Illuminate\Support\Facades\Cache::class,
        'Config'       => Illuminate\Support\Facades\Config::class,
        'Cookie'       => Illuminate\Support\Facades\Cookie::class,
        'Crypt'        => Illuminate\Support\Facades\Crypt::class,
        'DB'           => Illuminate\Support\Facades\DB::class,
        'Eloquent'     => Illuminate\Database\Eloquent\Model::class,
        'Event'        => Illuminate\Support\Facades\Event::class,
        'File'         => Illuminate\Support\Facades\File::class,
        'Gate'         => Illuminate\Support\Facades\Gate::class,
        'Hash'         => Illuminate\Support\Facades\Hash::class,
        'Lang'         => Illuminate\Support\Facades\Lang::class,
        'Log'          => Illuminate\Support\Facades\Log::class,
        'Mail'         => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password'     => Illuminate\Support\Facades\Password::class,
        'Queue'        => Illuminate\Support\Facades\Queue::class,
        'Redirect'     => Illuminate\Support\Facades\Redirect::class,
        'Redis'        => Illuminate\Support\Facades\Redis::class,
        'Request'      => Illuminate\Support\Facades\Request::class,
        'Response'     => Illuminate\Support\Facades\Response::class,
        'Route'        => Illuminate\Support\Facades\Route::class,
        'Schema'       => Illuminate\Support\Facades\Schema::class,
        'Session'      => Illuminate\Support\Facades\Session::class,
        'Storage'      => Illuminate\Support\Facades\Storage::class,
        'URL'          => Illuminate\Support\Facades\URL::class,
        'Validator'    => Illuminate\Support\Facades\Validator::class,
        'View'         => Illuminate\Support\Facades\View::class,
        'Minify'       => Devfactory\Minify\Facades\MinifyFacade::class,

        // Custom
        'Helper'       => App\Misc\Helper::class,
        'MailHelper'   => App\Misc\Mail::class,
        'Option'       => App\Option::class,
        // Autodiscovery did not work for this one, becasuse it's composer.json
        // does not have a `extra` section.
        'Updater'      => Codedge\Updater\UpdaterFacade::class,
    ],

];
