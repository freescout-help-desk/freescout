<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateFolderCounters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:update-folder-counters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update counters for all folders';

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
        foreach (\App\Folder::get() as $folder) {
            $folder->updateCounters();
            $this->line('Updated counters for folder: '.$folder->id);
        }
        $this->info('Updating finished');
    }
}
