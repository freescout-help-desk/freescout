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
                'locales' => \Helper::getAllLocales(),
            ];

            //$filesystem = new Filesystem();

            //$file_path = public_path('js/vars.js');
            //$file_path = '/public/js/builds/vars.js';
            $relative_path = 'js/builds/vars.js';
            $file_path = public_path($relative_path);

            $content = view('js/vars', $params)->render();

            // Escape quotes in json values.
            // https://github.com/freescout-help-desk/freescout/issues/4369
            $content = preg_replace_callback(
                "#(:[ ]*\")(.*)(\"[,\r\n])#",
                function ($v) {
                    return $v[1].str_replace('"', '\"', $v[2]).$v[3];
                },
                $content
            );

            try {
                if (!\File::exists(dirname($file_path))) {
                    \File::makeDirectory(dirname($file_path), \Helper::DIR_PERMISSIONS);
                }

                // Save vars only if content has changed.
                if (\File::exists($file_path)) {
                    $old_content = \File::get($file_path);
                    if ($content != $old_content) {
                        \File::put($file_path, $content);
                    }
                } else {
                    \File::put($file_path, $content);
                }

                // Backward compatibility.
                // Before vars.js was stored in /storage/app/public/js/vars.js
                \Storage::put('js/vars.js', $content);

                $this->info("Created: /".$relative_path);
            } catch (\Exception $e) {
                $msg = "Error occurred saving $file_path. ".\Helper::formatException($e);
                \Log::error($msg);
                $this->error($msg);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
