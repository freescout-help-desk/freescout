<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Settingssla;
class AutoReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canidesk:auto-reporting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command is uesd to send email automatically';

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

        $settings=Settingssla::orderBy('id', 'desc')->first();
        $this->info($settings);
    }
}
