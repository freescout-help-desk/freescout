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
    protected $description = 'Remove from /tmp folder files older than 1 week to avoid "No space left on device"';

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
        shell_exec('find /tmp -mtime +7 -type f -exec rm -r -f {} \;');

        $this->comment("Done");
    }
}
