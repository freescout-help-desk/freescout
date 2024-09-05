<?php

namespace Lord\Laroute\Console\Commands;

use Lord\Laroute\Routes\Collection as Routes;
use Lord\Laroute\Generators\GeneratorInterface as Generator;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;

use Symfony\Component\Console\Input\InputOption;

class LarouteGeneratorCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laroute:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a laravel routes file';

    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * An array of all the registered routes.
     *
     * @var \Lord\Laroute\Routes\Collection
     */
    protected $routes;

    /**
     * The generator instance.
     *
     * @var \Lord\Laroute\Generators\GeneratorInterface
     */
    protected $generator;

    /**
     * Create a new command instance.
     *
     * @param Config $config
     * @param Routes $routes
     * @param Generator $generator
     */
    public function __construct(Config $config, Routes $routes, Generator $generator)
    {
        $this->config    = $config;
        $this->routes    = $routes;
        $this->generator = $generator;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $filePath = $this->generator->compile(
                $this->getTemplatePath(),
                $this->getTemplateData(),
                $this->getFileGenerationPath()
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
        return $this->config->get('laroute.template');
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
        $prefix		= $this->config->get('laroute.prefix', '');

        return compact('namespace', 'routes', 'absolute', 'rootUrl', 'prefix');
    }


    /**
     * Get the path where the file will be generated.
     *
     * @return string
     */
    protected function getFileGenerationPath()
    {
        $path     = $this->getOptionOrConfig('path');
        $filename = $this->getOptionOrConfig('filename');

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
        if ($option = $this->option($key)) {
            return $option;
        }

        return $this->config->get("laroute.{$key}");
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                sprintf('Path to the javscript assets directory (default: "%s")', $this->config->get('laroute.path'))
            ],
            [
                'filename',
                'f',
                InputOption::VALUE_OPTIONAL,
                sprintf('Filename of the javascript file (default: "%s")', $this->config->get('laroute.filename'))
            ],
            [
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL, sprintf('Javascript namespace for the functions (think _.js) (default: "%s")', $this->config->get('laroute.namespace'))
            ],
            [
                'prefix',
                'pr',
                InputOption::VALUE_OPTIONAL, sprintf('Prefix for the generated URLs (default: "%s")', $this->config->get('laroute.prefix'))
            ],
        ];
    }
}
