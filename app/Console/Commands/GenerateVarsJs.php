<?php
/**
 * Comman generates vars.js file with variables and translated strings.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GenerateVarsJs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:generate-vars-js';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates vars.js file with variables and translated string';

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
        try {
            $params = [
                'locales' => config('app.locales'),
            ];

            $filesystem = new Filesystem();

            $file_path = public_path('js/vars.js');

            $compiled = view('js/vars', $params)->render();

            $filesystem->put($file_path, $compiled);

            $this->info("Created: {$file_path}");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
