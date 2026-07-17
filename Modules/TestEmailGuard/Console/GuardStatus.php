<?php

namespace Modules\TestEmailGuard\Console;

use Illuminate\Console\Command;
use Modules\TestEmailGuard\Providers\TestEmailGuardServiceProvider;
use Modules\TestEmailGuard\Services\EmailAnonymizer;

/**
 * Prints the effective state of the outbound email guard (ARMS-16), so the
 * environment can be verified before handing it over for bulk testing.
 */
class GuardStatus extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'test-email-guard:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show whether the outbound email guard is active and how it is configured';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $env = config('app.env');
        $active = TestEmailGuardServiceProvider::guardActive();
        $sink = EmailAnonymizer::sink();

        $this->line('app.env: '.$env);
        $this->line('Allow-listed domains: '.implode(', ', EmailAnonymizer::allowedDomains()));
        if ($sink) {
            $this->line('Rewrite target: sink mailbox '.$sink.(EmailAnonymizer::sinkMode() === 'plus'
                ? ' (plus-addressed; requires the tenant to accept plus addressing)'
                : ' (plain recipient; originals kept in display names and X-Original-To)'));
        } else {
            $this->line('Rewrite target: '.EmailAnonymizer::SAFE_DOMAIN.' (mail will bounce)');
        }
        $this->line('Sample: customer@gmail.com → '.EmailAnonymizer::rewriteRecipient('customer@gmail.com'));

        if ($active) {
            $this->info('GUARD ACTIVE (while this module is activated) — non-allow-listed outbound recipients are rewritten.');
        } else {
            $this->error('GUARD DISABLED — app.env is "production", so outbound email flows normally. If this is a test/demo instance, set APP_ENV (e.g. "demo") in .env.');
        }

        // Note: this command reports config, not module activation — if the
        // module is deactivated in Modules, nothing is rewritten regardless.
        $this->line('Reminder: the guard only runs while the TestEmailGuard module is Active under Manage → Modules.');

        return $active ? 0 : 1;
    }
}
