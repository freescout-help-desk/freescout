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
     * Default minimum identical-error occurrences required for
     * `fetch_errors` entries to be included in the digest. Used when
     * the `alert_logs_fetch_min_occurrences` option is not set. The
     * default is 1, which matches upstream behaviour (no filtering);
     * raise it via the alerts settings page to suppress transient
     * single-shot fetch errors.
     */
    const FETCH_ERRORS_MIN_OCCURRENCES_DEFAULT = 1;

    /**
     * Substrings that mark a `fetch_errors` row as transient. Rows
     * matching any of these are dropped regardless of count, unless
     * they appear at least the configured min-occurrences times.
     */
    const FETCH_ERRORS_TRANSIENT_PATTERNS = [
        'connection setup failed',
        'failed to fetch messages',
        'failed to authenticate',
        'Connection closed',
        'Connection reset',
        'Connection timed out',
        'Connection refused',
        'stream_socket_client',
        'SSL operation failed',
        'Temporary failure',
        'no response',
    ];

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
            'alert_logs_period' => config('app.alert_logs_period'),
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

        $logs = $this->filterTransientFetchErrors($logs);

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

    /**
     * Drop transient single-shot fetch errors. A fetch_errors row is
     * considered transient when its error message matches one of the
     * known transient patterns AND the same message appears fewer
     * than the configured min-occurrences threshold within the window.
     */
    protected function filterTransientFetchErrors($logs)
    {
        $threshold = (int) \Option::get(
            'alert_logs_fetch_min_occurrences',
            self::FETCH_ERRORS_MIN_OCCURRENCES_DEFAULT
        );

        if ($threshold <= 1) {
            return $logs;
        }

        $fetchLogName = \App\ActivityLog::NAME_EMAILS_FETCHING;

        $counts = [];
        foreach ($logs as $log) {
            if ($log->log_name !== $fetchLogName) {
                continue;
            }
            $key = $this->fetchErrorFingerprint($log);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $kept = [];
        $dropped = 0;
        foreach ($logs as $log) {
            if ($log->log_name === $fetchLogName) {
                $message = $this->fetchErrorMessage($log);
                if ($this->isTransientFetchError($message)) {
                    $key = $this->fetchErrorFingerprint($log);
                    if (($counts[$key] ?? 0) < $threshold) {
                        $dropped++;
                        continue;
                    }
                }
            }
            $kept[] = $log;
        }

        if ($dropped > 0) {
            $this->line('['.date('Y-m-d H:i:s').'] Suppressed '.$dropped.' transient fetch_errors entries (threshold '.$threshold.')');
        }

        return collect($kept);
    }

    protected function fetchErrorMessage($log): string
    {
        $props = $log->properties;
        if (is_object($props) && method_exists($props, 'toArray')) {
            $props = $props->toArray();
        } elseif (is_string($props)) {
            $decoded = json_decode($props, true);
            $props = is_array($decoded) ? $decoded : [];
        }
        return (string) ($props['error'] ?? '');
    }

    protected function fetchErrorFingerprint($log): string
    {
        $message = $this->fetchErrorMessage($log);
        // Strip the trailing "; File: /path (line)" so the same error
        // from different stack frames still groups together.
        $message = preg_replace('/;\s*File:.*$/s', '', $message);
        return trim($message);
    }

    protected function isTransientFetchError(string $message): bool
    {
        $lower = strtolower($message);
        foreach (self::FETCH_ERRORS_TRANSIENT_PATTERNS as $needle) {
            if (strpos($lower, strtolower($needle)) !== false) {
                return true;
            }
        }
        return false;
    }
}
