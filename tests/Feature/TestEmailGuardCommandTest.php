<?php

namespace Tests\Feature;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers the test-email-guard:anonymize-stored console command (ARMS-16):
 * the scrub across emails / conversations / threads / send_logs, header
 * scrubbing (with Message-ID and References left intact), the --map CSV,
 * per-row failure handling and the production refusal. Every test runs
 * inside a transaction and rolls back, so the shared test DB is untouched.
 */
class TestEmailGuardCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive — load directly and
        // register the provider so the command is available to Artisan.
        require_once __DIR__.'/../../Modules/TestEmailGuard/Services/EmailAnonymizer.php';
        require_once __DIR__.'/../../Modules/TestEmailGuard/Console/AnonymizeStoredEmails.php';
        require_once __DIR__.'/../../Modules/TestEmailGuard/Console/GuardStatus.php';
        require_once __DIR__.'/../../Modules/TestEmailGuard/Providers/TestEmailGuardServiceProvider.php';

        $this->app->register(\Modules\TestEmailGuard\Providers\TestEmailGuardServiceProvider::class);
    }

    protected function seedEmailRow($email)
    {
        $customer = factory(Customer::class)->create();

        return \DB::table('emails')->insertGetId([
            'customer_id' => $customer->id,
            'email'       => $email,
        ]);
    }

    protected function emailAt($id)
    {
        return \DB::table('emails')->where('id', $id)->value('email');
    }

    public function test_refuses_to_run_in_production()
    {
        $id = $this->seedEmailRow('real.person@gmail.com');

        config(['app.env' => 'production']);

        $exit = \Artisan::call('test-email-guard:anonymize-stored', ['--force' => true]);

        $this->assertSame(1, $exit);
        $this->assertSame('real.person@gmail.com', $this->emailAt($id));
    }

    public function test_scrubs_stored_addresses_across_tables()
    {
        $real_id = $this->seedEmailRow('walter.white@gmail.com');
        $allowed_id = $this->seedEmailRow('anthea@arms.com.mt');

        $user = factory(User::class)->create();
        $mailbox = factory(Mailbox::class)->create();
        $customer = factory(Customer::class)->create();

        $conversation = factory(Conversation::class)->create([
            'mailbox_id'         => $mailbox->id,
            'customer_id'        => $customer->id,
            'customer_email'     => 'walter.white@gmail.com',
            'cc'                 => json_encode(['friend@yahoo.com']),
            'bcc'                => null,
            'created_by_user_id' => $user->id,
            'status'             => Conversation::STATUS_ACTIVE,
            'state'              => Conversation::STATE_PUBLISHED,
        ]);

        $headers = "Message-ID: <abc123@mail.gmail.com>\r\n"
            ."From: Walter White <walter.white@gmail.com>\r\n"
            ."References: <ref1@mail.gmail.com>\r\n"
            ." <ref2@mail.gmail.com>\r\n"
            ."To: support@arms.com.mt\r\n";

        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'customer_id'     => $customer->id,
            'from'            => 'walter.white@gmail.com',
            'to'              => json_encode(['walter.white@gmail.com']),
            'cc'              => json_encode(['friend@yahoo.com', 'omar@threls.com']),
            'bcc'             => null,
            'headers'         => $headers,
        ]);

        $send_log_id = \DB::table('send_logs')->insertGetId([
            'thread_id' => $thread->id,
            'email'     => 'walter.white@gmail.com',
            'mail_type' => 1,
            'status'    => 1,
        ]);

        $exit = \Artisan::call('test-email-guard:anonymize-stored', ['--force' => true]);
        $this->assertSame(0, $exit);

        // emails: real address rewritten, allow-listed untouched.
        $this->assertSame('walter.white+gmail.com@example.com', $this->emailAt($real_id));
        $this->assertSame('anthea@arms.com.mt', $this->emailAt($allowed_id));

        // conversations: customer_email and the cc list.
        $conv_row = \DB::table('conversations')->where('id', $conversation->id)->first();
        $this->assertSame('walter.white+gmail.com@example.com', $conv_row->customer_email);
        $this->assertSame(['friend+yahoo.com@example.com'], \App\Misc\Helper::jsonToArray($conv_row->cc));

        // threads: from, recipient lists (allow-listed entry kept), headers.
        $thread_row = \DB::table('threads')->where('id', $thread->id)->first();
        $this->assertSame('walter.white+gmail.com@example.com', $thread_row->from);
        $this->assertSame(['walter.white+gmail.com@example.com'], \App\Misc\Helper::jsonToArray($thread_row->to));
        $this->assertSame(['friend+yahoo.com@example.com', 'omar@threls.com'], \App\Misc\Helper::jsonToArray($thread_row->cc));

        // Headers: address-bearing lines rewritten (allow-listed kept),
        // threading identifiers and line endings untouched.
        $expected_headers = "Message-ID: <abc123@mail.gmail.com>\r\n"
            ."From: Walter White <walter.white+gmail.com@example.com>\r\n"
            ."References: <ref1@mail.gmail.com>\r\n"
            ." <ref2@mail.gmail.com>\r\n"
            ."To: support@arms.com.mt\r\n";
        $this->assertSame($expected_headers, $thread_row->headers);

        // send_logs.
        $this->assertSame(
            'walter.white+gmail.com@example.com',
            \DB::table('send_logs')->where('id', $send_log_id)->value('email')
        );
    }

    public function test_second_run_changes_nothing()
    {
        $id = $this->seedEmailRow('walter.white@gmail.com');

        \Artisan::call('test-email-guard:anonymize-stored', ['--force' => true]);
        $after_first = $this->emailAt($id);

        $exit = \Artisan::call('test-email-guard:anonymize-stored', ['--force' => true]);

        $this->assertSame(0, $exit);
        $this->assertSame($after_first, $this->emailAt($id));
    }

    /**
     * A unique-key collision on emails.email must not abort the scrub
     * half-done: the failure is reported (non-zero exit) and every other
     * row is still anonymised.
     */
    public function test_collision_is_reported_without_aborting_the_run()
    {
        $colliding_id = $this->seedEmailRow('john@gmail.com');
        // Its anonymised twin already exists as another row.
        $this->seedEmailRow('john+gmail.com@example.com');
        $other_id = $this->seedEmailRow('jane@gmail.com');

        $exit = \Artisan::call('test-email-guard:anonymize-stored', ['--force' => true]);

        $this->assertSame(1, $exit);
        // The colliding row could not be updated...
        $this->assertSame('john@gmail.com', $this->emailAt($colliding_id));
        // ...but the scrub carried on past it.
        $this->assertSame('jane+gmail.com@example.com', $this->emailAt($other_id));
    }

    public function test_map_file_contains_original_to_anonymized_pairs()
    {
        $this->seedEmailRow('jesse@yahoo.com');
        $path = tempnam(sys_get_temp_dir(), 'teg-map-');

        try {
            $exit = \Artisan::call('test-email-guard:anonymize-stored', [
                '--force' => true,
                '--map'   => $path,
            ]);

            $this->assertSame(0, $exit);
            $map = file_get_contents($path);
            $this->assertStringContainsString('original,anonymized', $map);
            $this->assertStringContainsString('jesse@yahoo.com,jesse+yahoo.com@example.com', $map);
        } finally {
            @unlink($path);
        }
    }
}
