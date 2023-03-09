<?php

namespace App\Console\Commands;

use App\Job;
use Illuminate\Console\Command;

class SendMonitor extends Command
{
    const CHECK_PERIOD = 12 * 3600; // 12 hours

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:send-monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check emails sending and show an alert in the web interface if sending is not working';

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
        // Get SendReplyToCustomer jobs.
        $pending_jobs = \App\Job::where('queue', 'emails')
            ->where('payload', 'like', '{"displayName":"App\\\\\\\\Jobs\\\\\\\\SendReplyToCustomer"%')
            ->where('created_at', '<', time() - self::CHECK_PERIOD)
            ->exists();

        // Check failed_jobs.
        // No need - it can be done via Manage > Alerts > Logs Monitoring
        // if (!$pending_jobs) {
        //     $pending_jobs = \App\FailedJob::where('queue', 'emails')
        //         ->where('payload', 'like', '{"displayName":"App\\\\\\\\Jobs\\\\\\\\SendReplyToCustomer"%')
        //         ->where('created_at', '<', time() - self::CHECK_PERIOD)
        //         ->exists();
        // }

        if ($pending_jobs) {
            \Option::set('send_emails_problem', '1');
            $this->error('['.date('Y-m-d H:i:s').'] There are problems with emails queue processing');
        } else {
            \Option::remove('send_emails_problem');
            $this->info('['.date('Y-m-d H:i:s').'] Sending is working');
        }
    }
}
