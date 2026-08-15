<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// When processing attachments FreeScout may create files in /tmp folder.
// So it's good to clean this folder periodically.
class CleanTmp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:clean-tmp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove from system temp folder FreeScout files older than 1 week to avoid "No space left on device"';

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
        \Helper::shellExec('find '.\Helper::getTempDir().' -mtime +7 -type f -name '.\Helper::getTempFilePrefix().'* -exec rm -r -f {} \;');

        // Remove temporary SwiftMailer files:
        // Example: /tmp/a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6/body
        // https://github.com/freescout-help-desk/freescout/issues/5558
        \Helper::shellExec('find '.\Helper::getTempDir().' -mtime +7 -type d -regextype posix-extended -regex ".*/[a-f0-9]{32}" -exec rm -r -f {} \;');

        $this->comment("Done");
    }
}
