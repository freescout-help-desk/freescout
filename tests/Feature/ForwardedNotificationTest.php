<?php

namespace Tests\Feature;

use App\Console\Commands\FetchEmails;
use App\Conversation;
use App\Customer;
use App\Folder;
use App\Jobs\SendReplyToCustomer as SendReplyToCustomerJob;
use App\Mailbox;
use App\Misc\Mail;
use App\Thread;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class ForwardedNotificationTest extends TestCase
{
    use DatabaseTransactions;

    private $agent;
    private $mailbox_a;
    private $mailbox_b;
    private $conversation;
    private $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = factory(User::class)->create([
            'email' => 'agent@example.org',
        ]);
        $this->mailbox_a = $this->createMailbox('mailbox-a@example.org');
        $this->mailbox_b = $this->createMailbox('mailbox-b@example.org');
        $customer = factory(Customer::class)->create();
        $customer->syncEmails(['customer@example.org']);

        $this->conversation = factory(Conversation::class)->create([
            'mailbox_id'     => $this->mailbox_a->id,
            'folder_id'      => $this->mailbox_a->getFolderByType(Folder::TYPE_UNASSIGNED)->id,
            'customer_id'    => $customer->id,
            'customer_email' => 'customer@example.org',
            'subject'        => 'Question about invoice 123',
            'status'         => Conversation::STATUS_ACTIVE,
        ]);
        $this->thread = factory(Thread::class)->create([
            'conversation_id' => $this->conversation->id,
            'customer_id'     => $customer->id,
            'message_id'      => 'customer-message@example.org',
            'to'              => 'customer@example.org',
        ]);
    }

    public function testForwardedNotificationWithSecondMailboxInCcCreatesNewConversation()
    {
        Queue::fake();
        $forwarded_subject = 'Fwd: [#'.$this->conversation->number.'] Question about invoice 123';
        $message = $this->notificationResponseMessage(
            $forwarded_subject,
            'Supervisor <supervisor@example.org>',
            'Mailbox B <'.$this->mailbox_b->email.'>'
        );
        $this->processMessage($message, $this->mailbox_b, 'forwarded-notification@example.org');

        $new_conversation = Conversation::where('mailbox_id', $this->mailbox_b->id)
            ->where('id', '<>', $this->conversation->id)
            ->first();

        self::assertNotNull($new_conversation);
        self::assertSame($forwarded_subject, $new_conversation->subject);
        self::assertSame('agent@example.org', $new_conversation->customer_email);
        self::assertContains('supervisor@example.org', $new_conversation->getCcArray());
        self::assertNotContains('customer@example.org', $new_conversation->getCcArray());
        self::assertSame(1, Conversation::where('mailbox_id', $this->mailbox_a->id)->count());
        self::assertSame(1, $this->conversation->threads()->count());
        Queue::assertNotPushed(SendReplyToCustomerJob::class);
    }

    public function testForwardedNotificationUsesHiddenMarkerWhenReplyHeadersAreRemoved()
    {
        Queue::fake();
        $message = $this->notificationResponseMessage(
            'Fwd: Question about invoice 123',
            'Supervisor <supervisor@example.org>',
            'Mailbox B <'.$this->mailbox_b->email.'>',
            'Internal note for the supervisor.',
            false
        );
        $this->processMessage($message, $this->mailbox_b, 'forwarded-notification-without-references@example.org');

        self::assertNotNull(Conversation::where('mailbox_id', $this->mailbox_b->id)->first());
        self::assertSame(1, $this->conversation->threads()->count());
        Queue::assertNotPushed(SendReplyToCustomerJob::class);
    }

    public function testNormalReplyStillUpdatesOriginalConversation()
    {
        Queue::fake();
        $message = $this->notificationResponseMessage(
            'Re: Question about invoice 123',
            'Mailbox A <'.$this->mailbox_a->email.'>',
            null,
            'Reply to the customer. Begin forwarded message: quoted history.',
            true,
            true
        );
        $this->processMessage($message, $this->mailbox_a, 'notification-reply@example.org');

        self::assertSame(1, Conversation::whereIn('mailbox_id', [$this->mailbox_a->id, $this->mailbox_b->id])->count());
        self::assertSame(2, $this->conversation->threads()->count());
        $reply = $this->conversation->threads()->orderBy('id', 'desc')->first();
        self::assertSame(Thread::TYPE_MESSAGE, $reply->type);
        self::assertSame($this->agent->id, $reply->created_by_user_id);
        Queue::assertPushed(SendReplyToCustomerJob::class, function ($job) {
            return $job->conversation->id == $this->conversation->id
                && $job->customer->getMainEmail() == 'customer@example.org';
        });
    }

    public function testReplyToCustomerMessageWhoseSubjectStartsWithForwardPrefix()
    {
        Queue::fake();
        $this->conversation->subject = 'Fwd: Test';
        $this->conversation->save();

        $message = $this->notificationResponseMessage(
            'Fwd: Test',
            'Mailbox A <'.$this->mailbox_a->email.'>',
            null,
            'Reply to the customer.',
            true,
            false
        );
        $this->processMessage($message, $this->mailbox_a, 'forward-subject-reply@example.org');

        self::assertSame(1, Conversation::whereIn('mailbox_id', [$this->mailbox_a->id, $this->mailbox_b->id])->count());
        self::assertSame(2, $this->conversation->threads()->count());
        Queue::assertPushed(SendReplyToCustomerJob::class, function ($job) {
            return $job->conversation->id == $this->conversation->id;
        });
    }

    public function testForwardOfCustomerMessageWhoseSubjectStartsWithForwardPrefix()
    {
        Queue::fake();
        $this->conversation->subject = 'Fwd: Test';
        $this->conversation->save();

        $message = $this->notificationResponseMessage(
            'Fwd: [#'.$this->conversation->number.'] Fwd: Test',
            'Supervisor <supervisor@example.org>',
            'Mailbox B <'.$this->mailbox_b->email.'>'
        );
        $this->processMessage($message, $this->mailbox_b, 'double-forward-subject@example.org');

        self::assertNotNull(Conversation::where('mailbox_id', $this->mailbox_b->id)->first());
        self::assertSame(1, $this->conversation->threads()->count());
        Queue::assertNotPushed(SendReplyToCustomerJob::class);
    }

    public function testReplyToNotificationStillWorksAfterConversationWasMoved()
    {
        Queue::fake();
        $this->conversation->mailbox_id = $this->mailbox_b->id;
        $this->conversation->folder_id = $this->mailbox_b->getFolderByType(Folder::TYPE_UNASSIGNED)->id;
        $this->conversation->save();

        $message = $this->notificationResponseMessage(
            'Re: Question about invoice 123',
            'Mailbox A <'.$this->mailbox_a->email.'>',
            null,
            'Reply to the customer.',
            true,
            true
        );
        $this->processMessage($message, $this->mailbox_a, 'moved-conversation-reply@example.org');

        self::assertSame(1, Conversation::whereIn('mailbox_id', [$this->mailbox_a->id, $this->mailbox_b->id])->count());
        self::assertSame($this->mailbox_b->id, $this->conversation->fresh()->mailbox_id);
        self::assertSame(2, $this->conversation->threads()->count());
        Queue::assertPushed(SendReplyToCustomerJob::class, function ($job) {
            return $job->conversation->id == $this->conversation->id;
        });
    }

    private function createMailbox($email)
    {
        $mailbox = factory(Mailbox::class)->create([
            'email' => $email,
        ]);
        $mailbox->createPublicFolders();

        return $mailbox;
    }

    private function notificationResponseMessage($subject, $to, $cc = null, $new_body = 'Internal note for the supervisor.', $include_references = true, $include_in_reply_to = false)
    {
        $notification_id = Mail::MESSAGE_ID_PREFIX_NOTIFICATION
            .'-'.$this->thread->id
            .'-'.$this->agent->id
            .'-'.Mail::getMessageIdHash($this->thread->id)
            .'@example.org';
        $marker = Mail::getMessageMarker($notification_id);
        $cc_header = $cc ? "Cc: {$cc}\r\n" : '';
        $references_header = $include_references ? "References: <{$notification_id}>\r\n" : '';
        $in_reply_to_header = $include_in_reply_to ? "In-Reply-To: <{$notification_id}>\r\n" : '';
        $eml = "From: Agent <{$this->agent->email}>\r\n"
            ."To: {$to}\r\n"
            .$cc_header
            ."Subject: {$subject}\r\n"
            ."Message-ID: <notification-response@example.org>\r\n"
            .$in_reply_to_header
            .$references_header
            ."Date: Mon, 20 Jul 2026 12:00:00 +0000\r\n"
            ."MIME-Version: 1.0\r\n"
            ."Content-Type: text/html; charset=UTF-8\r\n\r\n"
            ."<html><body><p>{$new_body}</p>"
            .'<div id="'.Mail::REPLY_SEPARATOR_NOTIFICATION.'">'
            ."<div style=\"display:none\">{$marker}</div></div></body></html>";

        new ClientManager(config('imap'));
        $path = tempnam(sys_get_temp_dir(), 'freescout-issue-4515-');
        file_put_contents($path, $eml);
        $message = Message::fromFile($path);
        unlink($path);

        return $message;
    }

    private function processMessage($message, $mailbox, $message_id)
    {
        $command = new FetchEmailsForTest();
        $command->mailbox = $mailbox;
        $command->processMessage(
            $message,
            $message_id,
            $mailbox,
            collect([$this->mailbox_a, $this->mailbox_b])
        );
    }
}

class FetchEmailsForTest extends FetchEmails
{
    public function line($string, $style = null, $verbosity = null)
    {
        // Suppress console output in tests.
    }

    public function setSeen($message, $mailbox)
    {
        // A message loaded from an EML fixture has no IMAP server connection.
    }
}
