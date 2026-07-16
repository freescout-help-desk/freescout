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
 * Covered: emails.email, conversations.customer_email, the conversations
 * and threads to/cc/bcc recipient lists, threads.from, send_logs.email,
 * and address-bearing lines inside threads.headers. Deliberately not
 * covered: the users table (agents are staff, not customers), free text
 * inside email bodies — the outbound guard is the protection there, as
 * text in a body cannot cause a send — and Message-ID / References /
 * In-Reply-To header lines plus threads.message_id, which are threading
 * identifiers that merely look like addresses; rewriting them would
 * corrupt reply threading.
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
     * Count of rows that could not be updated (e.g. a unique-key collision
     * on emails.email) — reported at the end instead of aborting mid-run.
     */
    protected $failed = 0;

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
        $this->anonymizeSendLogs();

        if ($this->map_handle) {
            fclose($this->map_handle);
            $this->warn('Mapping written to '.$this->option('map').' — it contains real customer addresses. Move it off this server and delete it here.');
        }

        if ($this->irreversible > 0) {
            $this->warn($this->irreversible.' address(es) were longer than 64 characters and used the hash fallback — not recoverable by parsing'.($this->map_handle ? ' (recoverable via the map file)' : ' (re-run with --map if you need them recoverable)').'.');
        } else {
            $this->line('Every anonymised address is recoverable by parsing (no hash fallbacks were needed).');
        }

        if ($this->failed > 0) {
            $this->error($this->failed.' row(s) could not be updated and still hold real addresses — fix the reported conflicts and re-run (re-running is safe, the transform is idempotent).');

            return 1;
        }

        $this->info('Done. Re-running this command is safe (the transform is idempotent).');

        return 0;
    }

    /**
     * Update one row, absorbing per-row failures (e.g. a unique-key
     * collision on emails.email) so one bad row cannot abort the scrub
     * half-done: the remaining rows are still anonymised and the failure
     * is reported in the summary with a non-zero exit code.
     */
    protected function updateRow($table, $id, array $changes)
    {
        try {
            \DB::table($table)->where('id', $id)->update($changes);

            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            $this->failed++;
            $this->error($table.' row '.$id.' could not be updated: '.$e->getMessage());

            return false;
        }
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

    /**
     * Anonymise a JSON-encoded recipient list; returns null when nothing
     * changed.
     */
    protected function transformJsonList($json)
    {
        $emails = \App\Misc\Helper::jsonToArray($json);
        if (!$emails) {
            return null;
        }

        $anonymized_list = array_values(array_unique(array_map(function ($email) {
            return $this->transform($email);
        }, $emails)));

        if ($anonymized_list === array_values($emails)) {
            return null;
        }

        return \App\Misc\Helper::jsonEncodeUtf8($anonymized_list);
    }

    protected function anonymizeEmails()
    {
        $updated = 0;

        \DB::table('emails')->select('id', 'email')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                $anonymized = $this->transform($row->email);
                if ($anonymized !== $row->email && $this->updateRow('emails', $row->id, ['email' => $anonymized])) {
                    $updated++;
                }
            }
        });

        $this->line('emails.email: '.$updated.' updated');
    }

    protected function anonymizeConversations()
    {
        $updated = 0;

        \DB::table('conversations')->select('id', 'customer_email', 'cc', 'bcc')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                $changes = [];

                if ($row->customer_email) {
                    $anonymized = $this->transform($row->customer_email);
                    if ($anonymized !== $row->customer_email) {
                        $changes['customer_email'] = $anonymized;
                    }
                }

                foreach (['cc', 'bcc'] as $field) {
                    if ($row->$field && ($json = $this->transformJsonList($row->$field)) !== null) {
                        $changes[$field] = $json;
                    }
                }

                if ($changes && $this->updateRow('conversations', $row->id, $changes)) {
                    $updated++;
                }
            }
        });

        $this->line('conversations (customer_email/cc/bcc): '.$updated.' updated');
    }

    protected function anonymizeThreads()
    {
        $updated = 0;

        \DB::table('threads')->select('id', 'from', 'to', 'cc', 'bcc', 'headers')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                $changes = [];

                if ($row->from) {
                    $anonymized = $this->transform($row->from);
                    if ($anonymized !== $row->from) {
                        $changes['from'] = $anonymized;
                    }
                }

                foreach (['to', 'cc', 'bcc'] as $field) {
                    if ($row->$field && ($json = $this->transformJsonList($row->$field)) !== null) {
                        $changes[$field] = $json;
                    }
                }

                if ($row->headers) {
                    $scrubbed = $this->scrubHeaders($row->headers);
                    if ($scrubbed !== $row->headers) {
                        $changes['headers'] = $scrubbed;
                    }
                }

                if ($changes && $this->updateRow('threads', $row->id, $changes)) {
                    $updated++;
                }
            }
        });

        $this->line('threads (from/to/cc/bcc/headers): '.$updated.' updated');
    }

    protected function anonymizeSendLogs()
    {
        $updated = 0;

        \DB::table('send_logs')->select('id', 'email')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                if (!$row->email) {
                    continue;
                }
                $anonymized = $this->transform($row->email);
                if ($anonymized !== $row->email && $this->updateRow('send_logs', $row->id, ['email' => $anonymized])) {
                    $updated++;
                }
            }
        });

        $this->line('send_logs.email: '.$updated.' updated');
    }

    /**
     * Rewrite address tokens inside raw stored email headers (threads
     * fetched from real mailboxes keep the original From/To/Cc/Received/…
     * lines). Message-ID, References and In-Reply-To lines are left intact:
     * their values are threading identifiers that merely look like
     * addresses, and rewriting them would corrupt reply threading — core
     * also matches them against threads.message_id. A folded continuation
     * line (leading whitespace, RFC 5322) inherits the protection of the
     * header it continues. Line endings are preserved.
     */
    protected function scrubHeaders($headers)
    {
        $protected = false;

        // Split keeping the delimiters, so CRLF vs LF survives untouched:
        // even indexes are lines, odd indexes are the line breaks.
        $parts = preg_split('/(\r?\n)/', $headers, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $line) {
            if ($i % 2 || $line === '') {
                continue;
            }

            if (!preg_match('/^\s/', $line)) {
                $protected = (bool) preg_match('/^(message-id|references|in-reply-to)\s*:/i', $line);
            }

            if (!$protected) {
                $parts[$i] = preg_replace_callback('/[a-z0-9._%+=\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', function ($m) {
                    return $this->transform($m[0]);
                }, $line);
            }
        }

        return implode('', $parts);
    }
}
