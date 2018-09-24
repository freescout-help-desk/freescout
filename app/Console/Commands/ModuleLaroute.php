<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Axn\Laroute\Routes\Collection as Routes;

class ModuleLaroute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-laroute {module_alias?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a laravel routes JS-file for a module or all modules (if module_alias is empty)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $app = app();

        $this->config     = $app['config'];
        $this->generator  = $app->make('Lord\Laroute\Generators\GeneratorInterface');

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
            // $all = $this->confirm('You have not specified a module alias, would you like to generate routes for all available modules ('.implode(', ', $modules_aliases).')?');
            // if (!$all) {
            //     return;
            // }
        }

        if ($all) {
            foreach ($modules as $module) {
                $this->generateModuleRoutes($module);
            }
        } else {
            $module = \Module::findByAlias($module_alias);
            if (!$module) {
                $this->error('Module with the specified alias not found: '.$module_alias);
                return;
            }
            $this->generateModuleRoutes($module);
        }
    }

    public function generateModuleRoutes($module)
    {
        $this->line('Module: '.$module->getName());

        $public_symlink = public_path('modules').DIRECTORY_SEPARATOR.$module->getAlias();
        if (!file_exists($public_symlink)) {
            $this->error('Public symlink ['.$public_symlink.'] not found. Run module installation command first: php artisan freescout:module-install');
            return;
        }

        $this->routes = new Routes(app()['router']->getRoutes(), $this->config->get('laroute.filter', 'all'), $this->config->get('laroute.action_namespace', ''), $module->getAlias());

        try {
            $filePath = $this->generator->compile(
                $this->getTemplatePath(),
                $this->getTemplateData(),
                $this->getFileGenerationPath($module->getAlias())
            );

            $this->info("Created: {$filePath}");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Get path to the template file.
     *
     * @return string
     */
    protected function getTemplatePath()
    {
        return 'resources/assets/js/laroute_module.js';
    }

    /**
     * Get the data for the template.
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $namespace  = $this->getOptionOrConfig('namespace');
        $routes     = $this->routes->toJSON();
        $absolute   = $this->config->get('laroute.absolute', false);
        $rootUrl    = $this->config->get('app.url', '');
        $prefix     = $this->config->get('laroute.prefix', '');

        return compact('namespace', 'routes', 'absolute', 'rootUrl', 'prefix');
    }


    /**
     * Get the path where the file will be generated.
     *
     * @return string
     */
    protected function getFileGenerationPath($module_alias)
    {
        $path     = 'public/modules/'.$module_alias.'/js';
        $filename = 'laroute'; //$this->getOptionOrConfig('filename');

        return "{$path}/{$filename}.js";
    }

    /**
     * Get an option value either from console input, or the config files.
     *
     * @param $key
     *
     * @return array|mixed|string
     */
    protected function getOptionOrConfig($key)
    {
        // if ($option = $this->option($key)) {
        //     return $option;
        // }

        return $this->config->get("laroute.{$key}");
    }
}
