<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:fetch-monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check emails fetching and send alert if fething is not working or recovered';

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
        $now = time();

        $options = \Option::getOptions([
            'fetch_schedule',
            'alert_fetch_period',
            'fetch_emails_last_successful_run',
        ]);

        $last_successful_run = $options['fetch_emails_last_successful_run'];
        if ($last_successful_run && $last_successful_run < $now - (($options['fetch_schedule'] * 60) + ($options['alert_fetch_period'] * 60))) {
            $mins_ago = floor(($now - $last_successful_run) / 60);

            $text = 'There are some problems fetching emails: last time emails were successfully fetched <strong>'.$mins_ago.' minutes ago</strong>. Please check <a href="'.route('logs', ['name' => 'fetch_errors']).'">fetching logs</a> and <a href="'.route('system').'#cron">make sure</a> that the following cron task is running: <code>php artisan schedule:run</code>';

            if (\Option::get('alert_fetch') && !\Option::get('alert_fetch_sent')) {
                // We send alert only once
                \Option::set('alert_fetch_sent', true);
                \MailHelper::sendAlertMail($text, 'Fetching Problems');
            }

            $this->error('['.date('Y-m-d H:i:s').'] '.$text);
        } elseif (!$last_successful_run) {
            $this->line('['.date('Y-m-d H:i:s').'] Fetching has not been configured yet');
        } else {
            if (\Option::get('alert_fetch_sent')) {
                $text = 'Previously there were some problems fetching emails. Fetching recovered and functioning now!';

                \MailHelper::sendAlertMail($text, 'Fetching Recovered');
            }
            \Option::set('alert_fetch_sent', false);

            $this->info('['.date('Y-m-d H:i:s').'] Fetching is working');
        }
    }
}
