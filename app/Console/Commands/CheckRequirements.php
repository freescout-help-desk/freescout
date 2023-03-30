<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRequirements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:check-requriements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check console version of PHP';

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
        // PHP extensions.
        $php_extensions = \Helper::checkRequiredExtensions();

        $this->comment("PHP Version");
        $this->line(' '.str_pad(phpversion(), 30, '.'). ' '.(version_compare(phpversion(), config('installer.core.minPhpVersion'), '>=') ? '<fg=green>OK</>' : '<fg=red>NOT FOUND</>'), false);

        $this->comment("PHP Extensions");
        $this->output($php_extensions);

        // Functions.
        $functions = \Helper::checkRequiredFunctions();

        $this->comment("Functions");
        $this->output($functions);
        $this->line('');
    }

    public function output($items)
    {
        foreach ($items as $item => $status) {
            $this->line(' '.str_pad($item, 30, '.'). ' '.($status ? '<fg=green>OK</>' : '<fg=red>NOT FOUND</>'), false);
        }
    }
}
