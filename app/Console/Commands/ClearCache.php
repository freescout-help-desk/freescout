<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:clear-cache {--doNotCacheConfig} {--doNotGenerateVars}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application cache and cache config';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('clear-compiled');
        $this->call('cache:clear');

        // Remove files from /bootstrap/cache folder.
        // https://github.com/freescout-help-desk/freescout/issues/4536
        $files = new \Illuminate\Filesystem\Filesystem;
        $files->delete($this->laravel->getCachedServicesPath());
        $files->delete($this->laravel->getCachedPackagesPath());

        $this->call('view:clear');
        if ($this->option('doNotCacheConfig')) {
            $this->call('config:clear');
        } else {
            $this->call('config:cache');
            // Laravel users `require` function to include config.php
            // If opcache is being used for few seconds config.php is being cached.
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate(app()->getCachedConfigPath());
            }
        }

        // In FreeScout routes caching does not increase performance
        // but it increases memory consumption and may lead to problems
        // during application updating or installing/updating modules.
        // try {
        //     $this->call('route:cache');
        // } catch (\Exception $e) {
        //     // Do nothing.
        // }

        // Regenerate vars to get new data from .env
        if (!$this->option('doNotGenerateVars')) {
            $this->call('freescout:generate-vars');
        }
        // This should not be done during installation.
        if (\Helper::isInstalled()) {
            \Helper::queueWorkerRestart();
        }
    }
}
