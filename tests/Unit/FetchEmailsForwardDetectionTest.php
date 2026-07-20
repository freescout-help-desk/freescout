<?php

namespace Tests\Unit;

use App\Console\Commands\FetchEmails;
use App\Misc\Mail;
use Tests\TestCase;

class FetchEmailsForwardDetectionTest extends TestCase
{
    public function testForwardedNotificationStartsNewConversation()
    {
        $command = new FetchEmails();
        $message_id = $this->notificationMessageId();

        self::assertTrue($command->shouldStartNewConversationForForward('Fwd: Notification', [$message_id]));
        self::assertTrue($command->shouldStartNewConversationForForward('FW: Notification', [$message_id]));
        self::assertTrue($command->shouldStartNewConversationForForward('WG: Notification', [$message_id]));
    }

    public function testReplyToNotificationKeepsExistingThreadingBehavior()
    {
        $command = new FetchEmails();
        $message_id = $this->notificationMessageId();

        self::assertFalse($command->shouldStartNewConversationForForward('Re: Notification', [$message_id]));
        self::assertFalse($command->shouldStartNewConversationForForward('Aw: Notification', [$message_id]));
    }

    public function testOnlyValidUserNotificationMessageIdsAreHandled()
    {
        $command = new FetchEmails();
        $message_id = $this->notificationMessageId();

        self::assertTrue($command->shouldStartNewConversationForForward('Fwd: Notification', [
            'unrelated@example.org',
            $message_id,
        ]));
        self::assertTrue($command->shouldStartNewConversationForForward('Fwd: Notification', [
            preg_replace('/^FS_/', '', $message_id),
        ]));
        self::assertFalse($command->shouldStartNewConversationForForward('Fwd: Notification', [
            'FS_notify-123-456-0000000000000000@example.org',
        ]));
        self::assertFalse($command->shouldStartNewConversationForForward('Fwd: Customer reply', [
            'FS_reply-123-'.Mail::getMessageIdHash(123).'@example.org',
        ]));
        self::assertFalse($command->shouldStartNewConversationForForward('Fwd: Notification', [
            'regular-message@example.org',
        ]));
    }

    private function notificationMessageId()
    {
        return 'FS_notify-123-456-'.Mail::getMessageIdHash(123).'@example.org';
    }
}
