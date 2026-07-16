<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Covers the TestEmailGuard module (ARMS-16): the anonymisation transform,
 * the allow-list, the sink-mailbox mode and the send-time guard hooked to
 * core's mail.process_swift_message filter.
 */
class TestEmailGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive — load directly.
        require_once __DIR__.'/../../Modules/TestEmailGuard/Services/EmailAnonymizer.php';
        require_once __DIR__.'/../../Modules/TestEmailGuard/Providers/TestEmailGuardServiceProvider.php';
    }

    protected function tearDown(): void
    {
        // Clear env overrides set via putenv() in individual tests.
        putenv('TEST_EMAIL_GUARD_SINK');
        putenv('TEST_EMAIL_GUARD_ALLOW_DOMAINS');

        parent::tearDown();
    }

    protected function bootModule()
    {
        (new \Modules\TestEmailGuard\Providers\TestEmailGuardServiceProvider(app()))->boot();
    }

    protected function anonymizer()
    {
        return \Modules\TestEmailGuard\Services\EmailAnonymizer::class;
    }

    public function test_folds_domain_into_local_part()
    {
        $this->assertSame(
            'tanti.omar+gmail.com@example.com',
            $this->anonymizer()::anonymize('tanti.omar@gmail.com')
        );
    }

    public function test_distinct_addresses_stay_distinct()
    {
        $this->assertNotSame(
            $this->anonymizer()::anonymize('john@gmail.com'),
            $this->anonymizer()::anonymize('john@yahoo.com')
        );
    }

    public function test_transform_is_idempotent()
    {
        $once = $this->anonymizer()::anonymize('tanti.omar@gmail.com');

        $this->assertSame($once, $this->anonymizer()::anonymize($once));
    }

    public function test_lowercases_consistently()
    {
        $this->assertSame(
            'john.doe+gmail.com@example.com',
            $this->anonymizer()::anonymize('John.DOE@Gmail.COM')
        );
    }

    public function test_allow_listed_domains_pass_through()
    {
        $this->assertSame('anthea@arms.com.mt', $this->anonymizer()::anonymize('anthea@arms.com.mt'));
        $this->assertSame('omar@threls.com', $this->anonymizer()::anonymize('Omar@Threls.com'));
    }

    /**
     * The allow-list matches exact domains only — neither a subdomain of an
     * allowed domain nor a lookalike suffix may receive real mail.
     */
    public function test_allow_list_is_exact_domain_match()
    {
        $this->assertStringEndsWith('@example.com', $this->anonymizer()::anonymize('user@sub.arms.com.mt'));
        $this->assertStringEndsWith('@example.com', $this->anonymizer()::anonymize('user@arms.com.mt.attacker.net'));
    }

    public function test_allow_list_is_env_overridable()
    {
        putenv('TEST_EMAIL_GUARD_ALLOW_DOMAINS=arms.com.mt, threls.com, threls.onmicrosoft.com');

        $this->assertSame(
            'customercare@threls.onmicrosoft.com',
            $this->anonymizer()::anonymize('customercare@threls.onmicrosoft.com')
        );
    }

    public function test_long_local_parts_fall_back_to_hash_within_rfc_limit()
    {
        $email_a = str_repeat('a', 60).'@a-very-long-corporate-subdomain.example-company.co.uk';
        $email_b = str_repeat('a', 60).'@another-long-corporate-subdomain.example-company.co.uk';

        $result_a = $this->anonymizer()::anonymize($email_a);
        $result_b = $this->anonymizer()::anonymize($email_b);

        foreach ([$result_a, $result_b] as $result) {
            $local = substr($result, 0, strrpos($result, '@'));
            $this->assertLessThanOrEqual(64, strlen($local));
            $this->assertStringEndsWith('@example.com', $result);
        }

        // Same truncated local prefix, but the hash keeps them distinct.
        $this->assertNotSame($result_a, $result_b);
    }

    public function test_anonymized_addresses_reverse_to_the_original()
    {
        $originals = [
            'tanti.omar@gmail.com',
            'anna+work@gmail.com',        // "+" already in the local part
            'john.doe@sub.example.co.uk', // multi-label domain
        ];

        foreach ($originals as $original) {
            $this->assertSame(
                $original,
                $this->anonymizer()::reverse($this->anonymizer()::anonymize($original))
            );
            $this->assertTrue($this->anonymizer()::isReversible($original));
        }

        // Case is normalised, so recovery is the lowercased original.
        $this->assertSame(
            'john.doe@gmail.com',
            $this->anonymizer()::reverse($this->anonymizer()::anonymize('John.DOE@Gmail.COM'))
        );
    }

    public function test_reverse_rejects_non_anonymized_and_hash_fallback_addresses()
    {
        // Not an anonymised address.
        $this->assertNull($this->anonymizer()::reverse('someone@gmail.com'));

        // Hash fallback (original longer than 64 chars) is flagged as
        // irreversible and reverse() refuses to guess.
        $long = str_repeat('a', 60).'@a-very-long-corporate-subdomain.example-company.co.uk';
        $this->assertFalse($this->anonymizer()::isReversible($long));
        $this->assertNull($this->anonymizer()::reverse($this->anonymizer()::anonymize($long)));
    }

    public function test_invalid_input_passes_through_unchanged()
    {
        $this->assertSame('', $this->anonymizer()::anonymize(''));
        $this->assertSame('not-an-email', $this->anonymizer()::anonymize('not-an-email'));
    }

    public function test_sink_mode_plus_addresses_into_the_sink_mailbox()
    {
        putenv('TEST_EMAIL_GUARD_SINK=armssink@threls.onmicrosoft.com');

        $this->assertSame(
            'armssink+tanti.omar+gmail.com@threls.onmicrosoft.com',
            $this->anonymizer()::rewriteRecipient('tanti.omar@gmail.com')
        );

        // Allow-listed recipients are still delivered normally.
        $this->assertSame('anthea@arms.com.mt', $this->anonymizer()::rewriteRecipient('anthea@arms.com.mt'));

        // A stored, already-anonymised address folds into the sink without
        // dragging example.com along, and the rewrite is idempotent.
        $sunk = $this->anonymizer()::rewriteRecipient('tanti.omar+gmail.com@example.com');
        $this->assertSame('armssink+tanti.omar+gmail.com@threls.onmicrosoft.com', $sunk);
        $this->assertSame($sunk, $this->anonymizer()::rewriteRecipient($sunk));
    }

    public function test_without_sink_rewrite_targets_example_com()
    {
        $this->assertSame(
            'tanti.omar+gmail.com@example.com',
            $this->anonymizer()::rewriteRecipient('tanti.omar@gmail.com')
        );
    }

    public function test_guard_rewrites_swift_message_recipients()
    {
        $this->bootModule();

        $message = new \Swift_Message('Test');
        $message->setTo(['customer@gmail.com' => 'Some Customer', 'omar@threls.com' => 'Omar']);
        $message->setCc(['other@yahoo.com' => null]);

        $proceed = \Eventy::filter('mail.process_swift_message', true, $message);

        $this->assertTrue($proceed);
        $this->assertSame(
            ['customer+gmail.com@example.com' => 'Some Customer', 'omar@threls.com' => 'Omar'],
            $message->getTo()
        );
        $this->assertSame(['other+yahoo.com@example.com' => null], $message->getCc());
    }

    /**
     * Hard environment gate: even with the module active, production
     * messages must pass through untouched.
     */
    public function test_guard_does_nothing_in_production()
    {
        $this->bootModule();

        $env_backup = config('app.env');
        config(['app.env' => 'production']);

        try {
            $message = new \Swift_Message('Test');
            $message->setTo(['customer@gmail.com' => 'Some Customer']);

            $proceed = \Eventy::filter('mail.process_swift_message', true, $message);

            $this->assertTrue($proceed);
            $this->assertSame(['customer@gmail.com' => 'Some Customer'], $message->getTo());
        } finally {
            config(['app.env' => $env_backup]);
        }
    }

    /**
     * The guard must not overturn another listener's decision to cancel
     * a send.
     */
    public function test_guard_preserves_cancelled_sends()
    {
        $this->bootModule();

        $message = new \Swift_Message('Test');
        $message->setTo(['customer@gmail.com' => null]);

        $this->assertFalse(\Eventy::filter('mail.process_swift_message', false, $message));
        $this->assertSame(['customer@gmail.com' => null], $message->getTo());
    }
}
