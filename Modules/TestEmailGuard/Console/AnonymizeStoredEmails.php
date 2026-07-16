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
 */
class AnonymizeStoredEmails extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'test-email-guard:anonymize-stored {--force : Skip the confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymise stored customer email addresses (test environments only)';

    const CHUNK = 500;

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

        $this->line('Allow-listed domains (left untouched): '.implode(', ', EmailAnonymizer::allowedDomains()));

        $this->anonymizeEmails();
        $this->anonymizeConversations();
        $this->anonymizeThreads();

        $this->info('Done. Re-running this command is safe (the transform is idempotent).');

        return 0;
    }

    protected function anonymizeEmails()
    {
        $updated = 0;

        \DB::table('emails')->select('id', 'email')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use (&$updated) {
            foreach ($rows as $row) {
                $anonymized = EmailAnonymizer::anonymize($row->email);
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
                $anonymized = EmailAnonymizer::anonymize($row->customer_email);
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
                    $anonymized = EmailAnonymizer::anonymize($row->from);
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
                        return EmailAnonymizer::anonymize($email);
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
