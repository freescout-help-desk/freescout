<?php

namespace RachidLaasri\LaravelInstaller\Helpers;

use Exception;
use Illuminate\Http\Request;

class EnvironmentManager
{
    /**
     * @var string
     */
    private $envPath;

    /**
     * @var string
     */
    private $envExamplePath;

    /**
     * Set the .env and .env.example paths.
     */
    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');
    }

    /**
     * Get the content of the .env file.
     *
     * @return string
     */
    public function getEnvContent()
    {
        if (!file_exists($this->envPath)) {
            if (file_exists($this->envExamplePath)) {
                copy($this->envExamplePath, $this->envPath);
            } else {
                touch($this->envPath);
            }
        }

        return file_get_contents($this->envPath);
    }

    /**
     * Get the the .env file path.
     *
     * @return string
     */
    public function getEnvPath()
    {
        return $this->envPath;
    }

    /**
     * Get the the .env.example file path.
     *
     * @return string
     */
    public function getEnvExamplePath()
    {
        return $this->envExamplePath;
    }

    /**
     * Save the edited content to the .env file.
     *
     * @param Request $input
     *
     * @return string
     */
    public function saveFileClassic(Request $input)
    {
        $message = trans('installer_messages.environment.success');

        try {
            file_put_contents($this->envPath, $input->get('envConfig'));
        } catch (Exception $e) {
            $message = trans('installer_messages.environment.errors');
        }

        return $message;
    }

    /**
     * Save the form content to the .env file.
     *
     * @param Request $request
     *
     * @return string
     */
    public function saveFileWizard(Request $request)
    {
        $results = trans('installer_messages.environment.success');

        $envFileData =
        // 'APP_NAME=\'' . $request->app_name . "'\n" .
        // 'APP_ENV=' . $request->environment . "\n" .
        //'APP_KEY=' . 'base64:bODi8VtmENqnjklBmNJzQcTTSC8jNjBysfnjQN59btE=' . "\n" .
        // 'APP_DEBUG=' . $request->app_debug . "\n" .
        // 'APP_LOG_LEVEL=' . $request->app_log_level . "\n" .
        '# Every time you are making changes in .env file, in order changes to take an effect you need to run:'."\n".
        '# php artisan freescout:clear-cache'."\n\n".
        '# Application URL'."\n".
        'APP_URL='.$request->app_url."\n\n".
        '# Use HTTP protocol and redirect to HTTPS'."\n".
        'APP_FORCE_HTTPS='.$request->app_force_https."\n\n".
        '# Improve security'."\n".
        'SESSION_SECURE_COOKIE='.$request->app_force_https."\n\n".
        '# Timezones: https://github.com/freescout-helpdesk/freescout/wiki/PHP-Timezones'."\n".
        '# Comment it to use default timezone from php.ini'."\n".
        'APP_TIMEZONE='.$request->app_timezone."\n\n".
        '# Default language'."\n".
        'APP_LOCALE='.$request->app_locale."\n\n".
        '# Database settings'."\n".
        'DB_CONNECTION='.$request->database_connection."\n".
        'DB_HOST='.$request->database_hostname."\n".
        'DB_PORT='.$request->database_port."\n".
        'DB_DATABASE='.$request->database_name."\n".
        'DB_USERNAME='.$request->database_username."\n".
        'DB_PASSWORD='.$request->database_password."\n".
        (!empty($request->database_charset) ? 'DB_CHARSET='.$request->database_charset."\n" : '').
        (!empty($request->database_collation) ? 'DB_COLLATION='.$request->database_collation."\n" : '').
        "\n".
        '# Run the following console command to generate the key: php artisan key:generate'."\n".
        '# Otherwise application will show the following error: "Whoops, looks like something went wrong"'."\n".
        'APP_KEY='.\Config::get('app.key')."\n\n".
        '# Uncomment to see errors in your browser, don\'t forget to comment it back when debugging finished'."\n".
        '#APP_DEBUG='.'true'."\n";
        // 'BROADCAST_DRIVER=' . $request->broadcast_driver . "\n" .
        // 'CACHE_DRIVER=' . $request->cache_driver . "\n" .
        // 'SESSION_DRIVER=' . $request->session_driver . "\n" .
        // 'QUEUE_DRIVER=' . $request->queue_driver . "\n\n" .
        // 'REDIS_HOST=' . $request->redis_hostname . "\n" .
        // 'REDIS_PASSWORD=' . $request->redis_password . "\n" .
        // 'REDIS_PORT=' . $request->redis_port . "\n\n" .
        // 'MAIL_DRIVER=' . $request->mail_driver . "\n" .
        // 'MAIL_HOST=' . $request->mail_host . "\n" .
        // 'MAIL_PORT=' . $request->mail_port . "\n" .
        // 'MAIL_USERNAME=' . $request->mail_username . "\n" .
        // 'MAIL_PASSWORD=' . $request->mail_password . "\n" .
        // 'MAIL_ENCRYPTION=' . $request->mail_encryption . "\n\n" .
        // 'PUSHER_APP_ID=' . $request->pusher_app_id . "\n" .
        // 'PUSHER_APP_KEY=' . $request->pusher_app_key . "\n" .
        // 'PUSHER_APP_SECRET=' . $request->pusher_app_secret;

        try {
            file_put_contents($this->envPath, $envFileData);
        } catch (Exception $e) {
            $results = trans('installer_messages.environment.errors');
        }

        // Clear and update cache
        // If we cache config here, it caches env data from memory
        //\Artisan::call('freescout:clear-cache');
        \Artisan::call('freescout:clear-cache', ['--doNotCacheConfig' => true]);

        return $results;
    }
}
