<?php

namespace Modules\TestEmailGuard\Console;

use Illuminate\Console\Command;
use Modules\TestEmailGuard\Services\EmailAnonymizer;

/**
 * Anonymises customer email addresses stored in the database (ARMS-16).
 *
 * Intended to run in the test environment right after real customer data
 * lands there — e.g. after a Zendesk demo migration — so the database
 * carries no real addresses. The transform is idempotent, so re-running
 * the command is safe.
 *
 * Covered: emails.email, conversations.customer_email, threads.from and
 * the threads to/cc/bcc recipient lists. Deliberately not covered: the
 * users table (agents are staff, not customers) and free text inside
 * email bodies — the outbound guard is the protection there, as text in
 * a body cannot cause a send.
 *
 * Reversibility: the transform is reversible by parsing
 * (EmailAnonymizer::reverse()) except for originals longer than 64
 * characters, which fall back to a hash suffix. Such cases are counted
 * and reported. For guaranteed recovery of every address — including
 * hash-fallback cases — pass --map to write an original→anonymised CSV;
 * that file contains the real addresses, so move it off this server and
 * delete it here as soon as it is safe.
 */
class AnonymizeStoredEmails extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'test-email-guard:anonymize-stored
        {--force : Skip the confirmation prompt}
        {--map= : Also write an original→anonymised CSV mapping to this path (contains real addresses — handle with care)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymise stored customer email addresses (test environments only)';

    const CHUNK = 500;

    /**
     * Open handle for the optional mapping CSV.
     */
    protected $map_handle = null;

    /**
     * Originals already written to the map (dedupe across tables).
     */
    protected $mapped = [];

    /**
     * Count of addresses whose anonymised form cannot be reverse-parsed
     * (hash fallback used).
     */
    protected $irreversible = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('app.env') === 'production') {
            $this->error('This command is destructive and refuses to run in production.');

            return 1;
        }

        if (!$this->option('force')
            && !$this->confirm('Irreversibly anonymise all non-allow-listed customer email addresses in this database?')
        ) {
            return 1;
        }

        if ($this->option('map')) {
            $this->map_handle = @fopen($this->option('map'), 'w');
            if (!$this->map_handle) {
                $this->error('Cannot open map file for writing: '.$this->option('map'));

                return 1;
            }
            fputcsv($this->map_handle, ['original', 'anonymized']);
        }

        $this->line('Allow-listed domains (left untouched): '.implode(', ', EmailAnonymizer::allowedDomains()));

        $this->anonymizeEmails();
        $this->anonymizeConversations();
        $this->anonymizeThreads();

        if ($this->map_handle) {
            fclose($this->map_handle);
            $this->warn('Mapping written to '.$this->option('map').' — it contains real customer addresses. Move it off this server and delete it here.');
        }

        if ($this->irreversible > 0) {
            $this->warn($this->irreversible.' address(es) were longer than 64 characters and used the hash fallback — not recoverable by parsing'.($this->map_handle ? ' (recoverable via the map file)' : ' (re-run with --map if you need them recoverable)').'.');
        } else {
            $this->line('Every anonymised address is recoverable by parsing (no hash fallbacks were needed).');
        }

        $this->info('Done. Re-running this command is safe (the transform is idempotent).');

        return 0;
    }

    /**
     * Anonymise one address, recording the mapping and reversibility.
     */
    protected function transform($email)
    {
        $anonymized = EmailAnonymizer::anonymize($email);

        if ($anonymized !== $email) {
            if (!EmailAnonymizer::isReversible($email)) {
                $this->irreversible++;
            }
            if ($this->map_handle && !isset($this->mapped[$email])) {
                fputcsv($this->map_handle, [$email, $anonymized]);
                $this->mapped[$email] = true;
            }
        }

        return $anonymized;
    }

    protected function anonymizeEmails()
    {
        $updated = 0;

        \DB::table('emails')->select('id', 'email')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                $anonymized = $this->transform($row->email);
                if ($anonymized !== $row->email) {
                    \DB::table('emails')->where('id', $row->id)->update(['email' => $anonymized]);
                    $updated++;
                }
            }
        });

        $this->line('emails.email: '.$updated.' updated');
    }

    protected function anonymizeConversations()
    {
        $updated = 0;

        \DB::table('conversations')->select('id', 'customer_email')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                if (!$row->customer_email) {
                    continue;
                }
                $anonymized = $this->transform($row->customer_email);
                if ($anonymized !== $row->customer_email) {
                    \DB::table('conversations')->where('id', $row->id)->update(['customer_email' => $anonymized]);
                    $updated++;
                }
            }
        });

        $this->line('conversations.customer_email: '.$updated.' updated');
    }

    protected function anonymizeThreads()
    {
        $updated = 0;

        \DB::table('threads')->select('id', 'from', 'to', 'cc', 'bcc')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                $changes = [];

                if ($row->from) {
                    $anonymized = $this->transform($row->from);
                    if ($anonymized !== $row->from) {
                        $changes['from'] = $anonymized;
                    }
                }

                foreach (['to', 'cc', 'bcc'] as $field) {
                    if (!$row->$field) {
                        continue;
                    }
                    $emails = \App\Misc\Helper::jsonToArray($row->$field);
                    if (!$emails) {
                        continue;
                    }
                    $anonymized_list = array_values(array_unique(array_map(function ($email) {
                        return $this->transform($email);
                    }, $emails)));
                    if ($anonymized_list !== array_values($emails)) {
                        $changes[$field] = \App\Misc\Helper::jsonEncodeUtf8($anonymized_list);
                    }
                }

                if ($changes) {
                    \DB::table('threads')->where('id', $row->id)->update($changes);
                    $updated++;
                }
            }
        });

        $this->line('threads (from/to/cc/bcc): '.$updated.' updated');
    }
}
