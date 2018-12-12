<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LogsMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:logs-monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send new log records by email';

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
        $now = \Carbon\Carbon::now();

        $options = \Option::getOptions([
            'alert_logs_names',
            'alert_logs_period',
        ], [
            'alert_logs_period' => config('app.alert_logs_period')
        ]);

        if (!$options['alert_logs_names']) {
            $this->error('['.date('Y-m-d H:i:s').'] No logs to monitor selected');
            return;
        }
        if (!$options['alert_logs_period']) {
            $this->error('['.date('Y-m-d H:i:s').'] No logs monitoring period set');
            return;
        }

        $logs = \App\ActivityLog::whereIn('log_name', $options['alert_logs_names'])
            ->where('created_at', '>=', \Carbon\Carbon::now()->modify('-1 '.$options['alert_logs_period'])->toDateTimeString())
            ->where('created_at', '<', $now->toDateTimeString())
            ->get();

        if (!count($logs)) {
            $this->line('['.date('Y-m-d H:i:s').'] No new log records found for the last '.$options['alert_logs_period']);
            return;
        }

        $names = $logs->pluck('log_name')->unique()->toArray();
        $text = 'Logs having new records for the last '.$options['alert_logs_period'].':<ul>';
        foreach ($names as $name) {
            $text .= '<li>';
            $text .= '<strong>'.\App\ActivityLog::getLogTitle($name).'</strong>';
            $text .= '</li>';
        }
        $text .= '</ul>';

        foreach ($names as $name) {
            $text .= '<br/><strong>'.\App\ActivityLog::getLogTitle($name).'</strong><br/>';
            foreach ($logs as $log) {
                if ($log->log_name != $name) {
                    continue;
                }
                $text .= '● ['.$log->created_at.'] '.$log->getEventDescription().' <code>'.$log->properties.'</code><br/>';
            }
        }
        // Send alert.
        \MailHelper::sendAlertMail($text, 'Logs Monitoring');

        $this->line($text);

        $this->info('['.date('Y-m-d H:i:s').'] Monitoring finished');
    }
}
