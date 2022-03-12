<?php

namespace RachidLaasri\LaravelInstaller\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class FinalInstallManager
{
    /**
     * Run final commands.
     *
     * @return collection
     */
    public function runFinal()
    {
        // After BufferedOutput it is impossible to get sessions values
        $user_data = [
            'email'      => old('admin_email'),
            'password'   => old('admin_password'),
            'role'       => \App\User::ROLE_ADMIN,
            'first_name' => old('admin_first_name'),
            'last_name'  => old('admin_last_name'),
        ];
        // Remove admin data from sessions
        // We need it to display on the page
        //session(['_old_input' => []]);

        $outputLog = new BufferedOutput();

        $this->runCommands($outputLog);
        //$this->generateKey($outputLog);
        //$this->publishVendorAssets($outputLog);

        // Check if admin already exists
        if (\App\User::where('role', \App\User::ROLE_ADMIN)->count() == 0) {
            \App\User::create($user_data);
        }

        return $outputLog->fetch();
    }

    private static function runCommands($outputLog)
    {
        try {
            Artisan::call('freescout:clear-cache', [], $outputLog);
            Artisan::call('storage:link', [], $outputLog);
        } catch (Exception $e) {
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Generate New Application Key.
     *
     * @param collection $outputLog
     *
     * @return collection
     */
    private static function generateKey($outputLog)
    {
        try {
            if (config('installer.final.key')) {
                Artisan::call('key:generate', ['--force'=> true], $outputLog);
            }
        } catch (Exception $e) {
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Publish vendor assets.
     *
     * @param collection $outputLog
     *
     * @return collection
     */
    private static function publishVendorAssets($outputLog)
    {
        try {
            if (config('installer.final.publish')) {
                Artisan::call('vendor:publish', ['--all' => true], $outputLog);
            }
        } catch (Exception $e) {
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Return a formatted error messages.
     *
     * @param $message
     * @param collection $outputLog
     *
     * @return array
     */
    private static function response($message, $outputLog)
    {
        return [
            'status'      => 'error',
            'message'     => $message,
            'dbOutputLog' => $outputLog->fetch(),
        ];
    }
}
