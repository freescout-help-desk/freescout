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
        $outputLog = new BufferedOutput;

        $this->generateKey($outputLog);
        $this->publishVendorAssets($outputLog);

        return $outputLog->fetch();
    }

    /**
     * Generate New Application Key.
     *
     * @param collection $outputLog
     * @return collection
     */
    private static function generateKey($outputLog)
    {
        try{
            if (config('installer.final.key')){
                Artisan::call('key:generate', ["--force"=> true], $outputLog);
            }
        }
        catch(Exception $e){
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Publish vendor assets.
     *
     * @param collection $outputLog
     * @return collection
     */
    private static function publishVendorAssets($outputLog)
    {
        try{
            if (config('installer.final.publish')){
                Artisan::call('vendor:publish', ['--all' => true], $outputLog);
            }
        }
        catch(Exception $e){
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }
    
    /**
     * Return a formatted error messages.
     *
     * @param $message
     * @param collection $outputLog
     * @return array
     */
    private static function response($message, $outputLog)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'dbOutputLog' => $outputLog->fetch()
        ];
    }
}
