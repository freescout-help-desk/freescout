<?php

$key = env('APP_KEY', null);
$key_file = env("APP_KEY_FILE", null);
if (empty($key) && !empty($key_file)) {
    $key = trim(file_get_contents($key_file));
}

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

    'version' => '1.8.194',

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

    'locale'          => env('APP_LOCALE', 'en'),
    'locales'         => ['en', 'ar', 'zh-CN', 'hr', 'cs', 'da', 'nl', 'fi', 'fr', 'de', 'he', 'hu', 'it', 'ja', 'kz', 'ko', 'no', 'fa', 'pl', 'pt-PT', 'pt-BR', 'ro', 'ru', 'es', 'sk', 'sv', 'tr', 'uk'],
    'locales_rtl'     => ['ar', 'fa', 'he'],
    'default_locale'  => 'en',

    /*
    | app()->setLocale() in Localize middleware also changes config('app.locale'),
    | so we are keeping real app locale in real_locale parameter.
    */
   'real_locale' => env('APP_LOCALE', 'en'),

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

    'key' => $key,

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
    'freescout_alt_api' => 'https://cdn.freescout.net/wp-json/',

    /*
    |--------------------------------------------------------------------------
    | FreeScout repository
    |-------------------------------------------------------------------------
    */
    'freescout_repo' => 'https://freescout.net/github',

    /*
    |--------------------------------------------------------------------------
    | FreeScout email
    |-------------------------------------------------------------------------
    */
    'freescout_email' => 'support@freescout.net',

    /*
    |--------------------------------------------------------------------------
    | Parameters used to run queued jobs processing.
    | Checks for new jobs every --sleep seconds.
    | If --tries is set and job fails it is being processed right away without any delay.
    | --delay parameter does not work to set delays between retry attempts.
    | --timeout parameter sets job timeout and is used to avoid queue:work freezing.
    |
    | Jobs sending emails are retried manually in handle().
    | Number of retries is set in each job class.
    |-------------------------------------------------------------------------
    */
    'queue_work_params' => ['--queue' => 'emails,default', '--sleep' => '5', '--tries' => '1', '--timeout' => '1800'],

    /*
    |--------------------------------------------------------------------------
    | PHP extensions required by the app
    | Replaced with installer.requirements.php
    |-------------------------------------------------------------------------
    */
    //'required_extensions' => ['mysql / mysqli', 'mbstring', 'xml', 'imap', /*'mcrypt' mcrypt is deprecated*/ 'json', 'gd', 'fileinfo', 'openssl', 'zip', 'tokenizer', 'curl', 'iconv'/*, 'dom', 'xmlwriter', 'libxml', 'phar'*/],

    /*
    |--------------------------------------------------------------------------
    | Logs monitoring parameters.
    | These settings must be stored to avoid DB query in Kenel.php
    |-------------------------------------------------------------------------
    */
    'alert_logs'        => env('APP_ALERT_LOGS', false),
    'alert_logs_period' => env('APP_ALERT_LOGS_PERIOD', ''),

    /*
    |--------------------------------------------------------------------------
    | Fetch Mail Schedule.
    |-------------------------------------------------------------------------
    */
    'fetch_schedule'    => env('APP_FETCH_SCHEDULE', 1),
    'fetch_unseen'      => env('APP_FETCH_UNSEEN', 1),

    /*
    |--------------------------------------------------------------------------
    | App colors.
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'main_light'    => '#0078d7',
        'main_dark'     => '#005a9e',
        'note'          => '#ffc646',
        'text_note'     => '#e6b216',
        'text_customer' => '#8d959b',
        'text_user'     => '#8d959b',
        'bg_user_reply' => '#f4f8fd',
        'bg_note'       => '#fffbf1',
    ],

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
        'subscription_defaults' => ['default' => []],
    ],

    /*
    |--------------------------------------------------------------------------
    | php - attachments are downloaded via PHP.
    |
    | apache - attachments are downloaded via Apache's mod_xsendfile.
    |
    | nginx - attachments are downloaded via nginx's X-Accel-Redirect.
    |-------------------------------------------------------------------------
    */
    'download_attachments_via'    => env('APP_DOWNLOAD_ATTACHMENTS_VIA', 'php'),

    /*
    |--------------------------------------------------------------------------
    | File types which should be viewed in the browser instead of downloading.
    | SVG images are not viewable to avid XSS.
    | The list should be in sync with /storage/app/public/uploads/.htaccess and nginx config.
    |-------------------------------------------------------------------------
    */
    'viewable_attachments'    => env('APP_VIEWABLE_ATTACHMENTS') 
                                ? explode(',', env('APP_VIEWABLE_ATTACHMENTS'))
                                : ['jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp', 'apng', 'bmp', 'gif', 'ico', 'cur', 'png', 'tif', 'tiff', 'webp', 'pdf', 'txt', 'diff', 'patch', 'json', 'mp3', 'wav', 'ogg', 'wma'],

    // Additional restriction by mime type.
    // If HTML file is renamed into .txt for example it will be shown by the browser as HTML.
    // Regular expressions (#...#)
    'viewable_mime_types'    => env('APP_VIEWABLE_MIME_TYPES', ['image/.*', 'application/pdf', 'text/plain', 'text/x-diff', 'application/json', 'audio/.*']),

    /*
    |--------------------------------------------------------------------------
    | Case insensitive regular expression, containing a list of
    | mail server error responses, returned when a mail server can not deliver an email
    | to one or more recipients. If FreeScout receives one of the listed
    | error responses from the mail server, it does not try to resend the email
    | to avoid sending multiple duplicate emails to other recipients.
    |
    | https://github.com/freescout-helpdesk/freescout/issues/870#issuecomment-786477909
    |
    |-------------------------------------------------------------------------
    */
    'no_retry_mail_errors'    => env('APP_NO_RETRY_MAIL_ERRORS', '(no valid recipients|does not comply with RFC|message file too big|malformed address)'),

    /*
    |--------------------------------------------------------------------------
    | none - send to the customer only agent's reply in the email.
    |
    | last - send to the customer the last message in the email.
    |
    | full - send to the customer full conversation history in the email.
    |
    |-------------------------------------------------------------------------
    */
    'email_conv_history'    => env('APP_EMAIL_CONV_HISTORY', 'none'),

    /*
    |--------------------------------------------------------------------------
    | Maximum size of the message which can be sent to the customer (MB).
    |
    |-------------------------------------------------------------------------
    */
    'max_message_size'    => env('APP_MAX_MESSAGE_SIZE', '20'),

    /*
    |--------------------------------------------------------------------------
    | none - send to the user only agent's reply in the email.
    |
    | last - send to the user the last message in the email.
    |
    | full - send to the user full conversation history in the email.
    |
    |-------------------------------------------------------------------------
    */
    'email_user_history'    => env('APP_EMAIL_USER_HISTORY', 'full'),

    /*
    |--------------------------------------------------------------------------
    | JSON containing user permissions.
    |
    |-------------------------------------------------------------------------
    */
    'user_permissions'    => env('APP_USER_PERMISSIONS', ''),

    /*
    |--------------------------------------------------------------------------
    | Use date from mail header on fetching.
    |
    |-------------------------------------------------------------------------
    */
    'use_mail_date_on_fetching'    => env('APP_USE_MAIL_DATE_ON_FETCHING', false),

    /*
    |--------------------------------------------------------------------------
    | Don't add quotes around date in the SINCE IMAP instruction on fetching.
    | https://github.com/freescout-help-desk/freescout/issues/4175
    |
    |-------------------------------------------------------------------------
    */
    'since_without_quotes_on_fetching'    => env('APP_SINCE_WITHOUT_QUOTES_ON_FETCHING', false),

    /*
    |--------------------------------------------------------------------------
    | Emails are fetched in bunches. The larger the bunch's size the more chances
    | to face "Allowed memory size exhausted" error. The smaller its size the more
    | connections are made to the mail server and the more time fetching takes.
    | https://github.com/freescout-help-desk/freescout/issues/4343
    |
    |-------------------------------------------------------------------------
    */
    'fetching_bunch_size'    => env('APP_FETCHING_BUNCH_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | Use new POP3 library which does not use PHP IMAP extantion.
    |
    |-------------------------------------------------------------------------
    */
    'use_new_pop3_lib'    => env('APP_USE_NEW_POP3_LIB', false),

     /*
    |--------------------------------------------------------------------------
    | Dashboard path.
    |
    |-------------------------------------------------------------------------
    */
    'dashboard_path'    => env('APP_DASHBOARD_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | Dashboard path.
    |
    |-------------------------------------------------------------------------
    */
    'login_path'    => env('APP_LOGIN_PATH', 'login'),

    /*
    |--------------------------------------------------------------------------
    | Home page controller.
    |
    |-------------------------------------------------------------------------
    */
    'home_controller'    => env('APP_HOMEPAGE_CONTROLLER', 'SecureController@dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Disable update checker
    |--------------------------------------------------------------------------
    */
    'disable_updating'    => env('APP_DISABLE_UPDATING', false),

    /*
    |--------------------------------------------------------------------------
    | Use custom conversation numbers instead of conversation ID.
    |--------------------------------------------------------------------------
    */
    'custom_number' => env('APP_CUSTOM_NUMBER', false),

    /*
    |--------------------------------------------------------------------------
    | Enter your proxy address in .env file if freescout.net is not available from your server
    | (access to freescout.net is required to obtain official modules)
    |--------------------------------------------------------------------------
    */
    'proxy' => env('APP_PROXY', ''),

    /*
    |--------------------------------------------------------------------------
    | Custom headers to add to all outgoing emails.
    | https://github.com/freescout-helpdesk/freescout/issues/2546#issuecomment-1380414908
    |--------------------------------------------------------------------------
    */
    'custom_mail_headers' => env('APP_CUSTOM_MAIL_HEADERS', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeout for curl and GuzzleHttp.
    |-------------------------------------------------------------------------
    */
    // Should be set for curl and Guzzle.
    'curl_timeout'         => env('APP_CURL_TIMEOUT', 40),
    // Should be set for Guzzle. Curl has default CURLOPT_CONNECTTIMEOUT=30 sec.
    'curl_connect_timeout' => env('APP_CURL_CONNECTION_TIMEOUT', 30),
    // CloudFlare may block requests without user agent.
    // Need to be set for curl. Guzzle sends it's own user agent: GuzzleHttp/6.3.3 curl/7.58.0 PHP/8.2.5
    'curl_user_agent'      => env('APP_CURL_USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 7_1_4) AppleWebKit/603.26 (KHTML, like Gecko) Chrome/55.0.3544.220 Safari/534'),
    // Should be set for curl and Guzzle.
    'curl_ssl_verifypeer'  => env('APP_CURL_SSL_VERIFYPEER', false),

    /*
    |--------------------------------------------------------------------------
    | Customer photo size (px).
    | https://github.com/freescout-helpdesk/freescout/issues/2919
    |-------------------------------------------------------------------------
    */
    'customer_photo_size'    => env('APP_CUSTOMER_PHOTO_SIZE', 64),


    /*
    |--------------------------------------------------------------------------
    | User photo size (px).
    |-------------------------------------------------------------------------
    */
    'user_photo_size'    => env('APP_USER_PHOTO_SIZE', 50),

    /*
    |--------------------------------------------------------------------------
    | Use this option if you have many folders and you are experiencing
    | performance issues. When this option is enabled sometimes it may take 
    | several seconds for folders counters to update in the interface.
    | 
    | https://github.com/freescout-helpdesk/freescout/pull/2982
    |-------------------------------------------------------------------------
    */
    'update_folder_counters_in_background'    => env('APP_UPDATE_FOLDER_COUNTERS_IN_BACKGROUND', false),

    /*
    |--------------------------------------------------------------------------
    | Experimental feature allowing to specify users who can see only conversations 
    | assigned to themselves. For such users only Mine folder shows actual number of conversations.
    | This option does not affect admin users.
    |
    | The option should be specified as a comma separated list of user IDs which
    | can be found in the their profile URL (/users/profile/7).
    |
    | Example: 7,5,31
    |-------------------------------------------------------------------------
    */
    'show_only_assigned_conversations'    => env('APP_SHOW_ONLY_ASSIGNED_CONVERSATIONS', ''),

    /*
    |--------------------------------------------------------------------------
    | Limit non-admin users to only see customers with conversations
    | in mailboxes they are assigned to. This option does not affect admin users.
    |-------------------------------------------------------------------------
    */
    'limit_user_customer_visibility'    => env('APP_LIMIT_USER_CUSTOMER_VISIBILITY', false),

    /*
    |--------------------------------------------------------------------------
    | By default X-Frame-Options header is enabled and set to SAMEORIGIN.
    | Via this option you can disable it (APP_X_FRAME_OPTIONS=false) or set custom value:
    | - DENY
    | - ALLOW-FROM example.org
    |-------------------------------------------------------------------------
    */
    'x_frame_options'    => env('APP_X_FRAME_OPTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy meta tag parameters.
    |-------------------------------------------------------------------------
    */
    //'csp_enabled'    => env('APP_CSP_ENABLED', true),
    'csp_script_src' => env('APP_CSP_SCRIPT_SRC', ''),
    'csp_custom'     => env('APP_CSP_CUSTOM', ''),

    /*
    |--------------------------------------------------------------------------
    | Let the application know that CloudFlare is used (for proper client IP detection).
    |-------------------------------------------------------------------------
    */
    'cloudflare_is_used'    => env('APP_CLOUDFLARE_IS_USED', false),

    /*
    |--------------------------------------------------------------------------
    | When this option is enabled you may see an extra text below customer's replies, for example:
    |     On Thu, Jan 4, 2024 at 8:41 AM John Doe | Demo <test@example.org> wrote:
    |
    | But overall reply separation in this case is more reliable.
    |-------------------------------------------------------------------------
    */
    'alternative_reply_separation'    => env('APP_ALTERNATIVE_REPLY_SEPARATION', false),

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
        // Debugbar is enabled only if APP_DEBUG=true
        //Barryvdh\Debugbar\ServiceProvider::class,

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
        // We can freely add or remove providers from this file.
        // Updating will work without problems.

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
        'ModuleHelper' => App\Module::class,
        'WpApi'        => App\Misc\WpApi::class,
        'Option'       => App\Option::class,
        'Str'          => Illuminate\Support\Str::class,
        // Autodiscovery did not work for this one, becasuse it's composer.json
        // does not have a `extra` section.
        'Updater'      => Codedge\Updater\UpdaterFacade::class,
    ],

];