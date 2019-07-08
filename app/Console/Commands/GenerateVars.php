<?php
/**
 * Comman generates vars.js file with variables and translated strings.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GenerateVars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:generate-vars';

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

            //$filesystem = new Filesystem();

            //$file_path = public_path('js/vars.js');
            $file_path = storage_path('app/public/js/vars.js');

            $content = view('js/vars', $params)->render();

            //$filesystem->put($file_path, $content);
            // Save vars only if content changed
            $old_content = \Storage::get('js/vars.js');
            if ($content != $old_content) {
                \Storage::put('js/vars.js', $content);
            }

            $this->info("Created: ".substr($file_path, strlen(base_path())+1));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
