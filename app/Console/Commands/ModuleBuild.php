<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ModuleBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-build {module_alias?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build module or all modules (if module_alias is empty)';

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
        $all = false;
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
            $all = true;
            // $all = $this->confirm('You have not specified a module alias, would you like to build all available modules ('.implode(', ', $modules_aliases).')?');
            // if (!$all) {
            //     return;
            // }
        }

        if ($all) {
            foreach ($modules as $module) {
                $this->buildModule($module);
                $this->call('freescout:module-laroute', ['module_alias' => $module->getAlias()]);
            }
        } else {
            $module = \Module::findByAlias($module_alias);
            if (!$module) {
                $this->error('Module with the specified alias not found: '.$module_alias);
                return;
            }
            $this->buildModule($module);
            $this->call('freescout:module-laroute');
        }
    }

    public function buildModule($module)
    {
        $this->line('Module: '.$module->getName());

        $public_symlink = public_path('modules').DIRECTORY_SEPARATOR.$module->alias;
        if (!file_exists($public_symlink)) {
            $this->error('Public symlink ['.$public_symlink.'] not found. Run module installation command first: php artisan freescout:module-install');
            return;
        }

        $this->buildVars($module);
    }

    public function buildVars($module)
    {
        try {
            $params = [
                'locales' => config('app.locales'),
            ];

            $filesystem = new Filesystem();

            $file_path = public_path('modules/'.$module->alias.'/js/vars.js');

            $compiled = view($module->alias.'::js/vars', $params)->render();

            if ($compiled) {
                $filesystem->put($file_path, $compiled);
            }

            $this->info("Created: {$file_path}");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
