<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run commands building application assets';

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
        $this->call('freescout:generate-vars-js');
        $this->call('laroute:generate');
    }
}
