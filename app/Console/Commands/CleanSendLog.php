<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanSendLog extends Command
{
    const PERIOD = '-6 months';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:clean-send-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old records from send log.';

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
        $logs = \App\SendLog::where('created_at', '<', \Carbon\Carbon::now()->modify(self::PERIOD))->delete();

        $this->info('['.date('Y-m-d H:i:s').'] Deleted send logs: '.self::PERIOD);
    }
}
