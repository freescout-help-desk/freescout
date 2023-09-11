<?php
/**
 * php artisan freescout:module-install modulealias.
 */

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
    protected $description = 'Install module or all modules (if module_alias is empty): run migrations and create a symlink';

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

        // We have to clear modules cache first to update modules cache
        $this->call('cache:clear');

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
            foreach ($modules as $module) {
                $this->line('Module: '.$module->getName());
                $this->call('module:migrate', ['module' => $module->getName()]);
                $this->createModulePublicSymlink($module);
            }
        } else {
            $module = \Module::findByAlias($module_alias);
            if (!$module) {
                $this->error('Module with the specified alias not found: '.$module_alias);

                return;
            }
            $this->call('module:migrate', ['module' => $module->getName(), '--force' => true]);
            $this->createModulePublicSymlink($module);
        }
        $this->line('Clearing cache...');
        $this->call('freescout:clear-cache');
    }

    // There is similar function in \App\Module.
    public function createModulePublicSymlink($module)
    {
        $from = public_path('modules').DIRECTORY_SEPARATOR.$module->alias;
        $to = $module->getExtraPath('Public');

        // file_exists() may throw "open_basedir restriction in effect".
        try {
            // If module's Public is symlink.
            if (is_link($to)) {
                @unlink($to);
            }
            
            // Symlimk may exist but lead to the module folder in a wrong case.
            // So we need first try to remove it.
            if (!file_exists($from) || !is_link($from)) {
                if (is_dir($from)) {
                    @rename($from, $from.'_'.date('YmdHis'));
                } else {
                    @unlink($from);
                }
            }

            if (file_exists($from)) {
                return $this->info('Public symlink already exists');
            }

            // Check target.
            if (!file_exists($to)) {
                // Try to create Public folder.
                try {
                    \File::makeDirectory($to, \Helper::DIR_PERMISSIONS);
                } catch (\Exception $e) {
                    // If it's a broken symlink.
                    if (is_link($to)) {
                        @unlink($to);
                    }
                }
            }

            try {
                symlink($to, $from);
            } catch (\Exception $e) {
                $this->error('Error occurred creating ['.$from.' » '.$to.'] symlink: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            $this->error('Error occurred creating ['.$from.' » '.$to.'] symlink: '.$e->getMessage());
        }

        $this->info('The ['.$from.'] symlink has been created');
    }
}
