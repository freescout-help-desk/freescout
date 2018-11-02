<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModuleInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-install {module_alias?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install module or all modules (if module_alias is empty)';

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
        $install_all = false;
        $modules = [];

        // Create a symlink for the module (or all modules)
        $module_alias = $this->argument('module_alias');
        if (!$module_alias) {
            $modules = \Module::all();

            $modules_aliases = [];
            foreach ($modules as $module) {
                $modules_aliases[] = $module->name;
            }
            if (!$modules_aliases) {
                $this->error('No modules found');
                return;
            }
            $install_all = $this->confirm('You have not specified a module alias, would you like to install all available modules ('.implode(', ', $modules_aliases).')?');
            if (!$install_all) {
                return;
            }
        }

        if ($install_all) {
            $this->call('module:migrate');
            foreach ($modules as $module) {
                $this->createModulePublicSymlink($module);
            }
        } else {
            $module = \Module::findByAlias($module_alias);
            if (!$module) {
                $this->error('Module with the specified alias not found: '.$module_alias);
                return;
            }
            $this->call('module:migrate "'.$module->getName().'"');
            $this->createModulePublicSymlink($module);
        }
        $this->call('freescout:clear-cache');
    }

    public function createModulePublicSymlink($module)
    {
        $from = public_path('modules').DIRECTORY_SEPARATOR.$module->alias;
        $to = $module->getExtraPath('Public');

        if (file_exists($from)) {
            return $this->info('Public symlink already exists');
        }
        
        try {
            symlink($to, $from);
        } catch (\Exception $e) {
            $this->error('Error occured creating ['.$from.'] symlink: '.$e->getMessage());
        }

        $this->info('The ['.$from.'] symlink has been created');
    }
}
