<?php

namespace App\Console\Commands;

use App\Misc\WpApi;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ModuleCheckLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-check-licenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check licenses for modules';

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
        // Get active official modules and check validity of their licenses
        $modules = \Module::getActive();
        
        $this->info('Active modules found: '.count($modules));

        foreach ($modules as $module) {
            $license = $module->getLicense();
            
            if (!$module->isOfficial() || !$license) {
                continue;
            }
            $params = [
                'license'      => $license,
                'module_alias' => $module->getAlias(),
                'url'          => \App\Module::getAppUrl()
            ];
            $result = WpApi::checkLicense($params);
            if (!empty($result['status']) && $result['status'] != 'valid') {

                $msg = 'Module '.$module->getName().' has been deactivated due to invalid license: '.json_encode($result);

                $this->error($module->getName().': '.$msg);

                // Deactive module
                \App\Module::deactiveModule($module->getAlias(), true);

                // Inform admin
                \Log::error($msg);
                activity()
                    ->withProperties([
                        'error'    => $msg,
                     ])
                    ->useLog(\App\ActivityLog::NAME_SYSTEM)
                    ->log(\App\ActivityLog::DESCRIPTION_SYSTEM_ERROR);
            } else {
                $this->info($module->getName().': OK');
            }
        }
        $this->info('Checking licenses finished');
    }
}
