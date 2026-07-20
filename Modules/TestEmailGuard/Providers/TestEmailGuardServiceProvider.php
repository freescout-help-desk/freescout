<?php

namespace Modules\TestEmailGuard\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TestEmailGuard\Services\EmailAnonymizer;

/**
 * Test-environment outbound email guard (ARMS-16).
 *
 * Core fires the mail.process_swift_message Eventy filter for every outbound
 * message via the Illuminate MessageSending event (EventServiceProvider →
 * ProcessSwiftMessage listener), regardless of which Mailable built it —
 * customer replies, auto-replies, workflow emails, user notifications,
 * alerts. Hooking that single filter therefore guards every send path
 * without touching core, and unlike a Swift Mailer plugin it survives
 * core's per-mailbox rebuilding of the swift.mailer instance.
 *
 * Safety model:
 *  - Enabling = activating the module. There is no "on" flag that can be
 *    forgotten — if the module is active outside production, the guard runs.
 *  - Hard environment gate: when app.env is production the guard refuses to
 *    rewrite anything, even if the module is activated there by mistake.
 */
class TestEmailGuardServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Fail loudly, not silently: an operator who activates this module
        // believing it protects the environment must find out that the
        // production gate has switched it off (Forge-style deploys default
        // to APP_ENV=production — test/demo instances must override it).
        // Throttled to once an hour so the alarm doesn't flood the logs
        // on every request/queue/cron boot.
        if (!self::guardActive() && \Cache::add('testemailguard_disabled_warning', true, 60)) {
            \Log::error('TestEmailGuard is activated but disabled: app.env is "production". Outbound email is NOT being rewritten. Set APP_ENV (e.g. to "demo") in this environment\'s .env if this is not a real production instance.');
        }

        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Eventy::addFilter('mail.process_swift_message', function ($proceed, $message) {
            if (!$proceed || !self::guardActive()) {
                return $proceed;
            }

            self::rewriteMessageRecipients($message);

            return $proceed;
        }, 20, 2);
    }

    /**
     * The guard must never rewrite (or be relied upon) in production.
     */
    public static function guardActive()
    {
        return config('app.env') !== 'production';
    }

    /**
     * Rewrite non-allow-listed To/Cc/Bcc addresses on the outgoing
     * Swift_Message.
     *
     * In plain sink mode every rewritten recipient collapses into the one
     * bare sink address, so the original addresses are preserved where
     * they cannot affect delivery: in the recipient display name (visible
     * in the sink's message list) and in an X-TestEmailGuard-Original-To header. In plus
     * mode the sink address itself carries the original, so display names
     * pass through untouched.
     */
    public static function rewriteMessageRecipients($message)
    {
        $plain = EmailAnonymizer::sink() && EmailAnonymizer::sinkMode() === 'plain';
        $originals = [];

        foreach (['To', 'Cc', 'Bcc'] as $field) {
            $getter = 'get'.$field;
            $setter = 'set'.$field;

            $recipients = $message->$getter();
            if (!$recipients) {
                continue;
            }

            $rewritten = [];
            $changed = false;
            foreach ($recipients as $email => $name) {
                $target = EmailAnonymizer::isAllowed($email)
                    ? $email
                    : EmailAnonymizer::rewriteRecipient($email);

                if ($target === $email) {
                    $rewritten[$email] = $name;
                    continue;
                }

                $changed = true;
                $originals[] = $email;

                if ($plain) {
                    // Collapsed recipients share the sink address; the
                    // display name lists every original this copy stands
                    // in for.
                    $label = ($name && strcasecmp($name, $email) !== 0)
                        ? $name.' ('.$email.')'
                        : $email;
                    $rewritten[$target] = isset($rewritten[$target])
                        ? $rewritten[$target].', '.$label
                        : $label;
                } else {
                    $rewritten[$target] = $name;
                }
            }

            if ($changed) {
                $message->$setter($rewritten);
            }
        }

        if ($originals) {
            // Module-branded header name: the standard X-Original-To is
            // set by delivery agents (e.g. Postfix, Exchange) and could
            // collide or confuse once the message lands in the sink.
            $message->getHeaders()->addTextHeader(
                'X-TestEmailGuard-Original-To',
                implode(', ', array_unique($originals))
            );
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // env() calls live in the module config file, per Laravel convention;
        // everything else reads config('testemailguard.*').
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'testemailguard');

        $this->commands([
            \Modules\TestEmailGuard\Console\AnonymizeStoredEmails::class,
            \Modules\TestEmailGuard\Console\GuardStatus::class,
        ]);
    }
}
