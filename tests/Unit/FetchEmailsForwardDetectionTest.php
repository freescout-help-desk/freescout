<?php

namespace Tests\Unit;

use App\Console\Commands\FetchEmails;
use App\Misc\Mail;
use Tests\TestCase;

class FetchEmailsForwardDetectionTest extends TestCase
{
    public function testReplySubjectsKeepExistingThreadingBehavior()
    {
        $command = new FetchEmails();
        $message_id = $this->notificationMessageId();

        self::assertFalse($command->shouldStartNewConversationForForward('Re: Notification', [$message_id]));
        self::assertFalse($command->shouldStartNewConversationForForward('Aw: Notification', [$message_id]));
    }

    public function testReplyKeepsOriginalForwardedSubjectWhenInReplyToIsPresent()
    {
        $command = new FetchEmails();
        $message_id = $this->notificationMessageId();

        self::assertFalse($command->shouldStartNewConversationForForward(
            'Fwd: Test',
            [$message_id],
            $message_id
        ));
    }

    public function testOnlyValidUserNotificationMessageIdsAreAccepted()
    {
        $command = new FetchEmails();
        $message_id = $this->notificationMessageId();

        self::assertSame('123', $command->getUserNotificationThreadId($message_id));
        self::assertSame('123', $command->getUserNotificationThreadId(preg_replace('/^FS_/', '', $message_id)));
        self::assertNull($command->getUserNotificationThreadId('FS_notify-123-456-0000000000000000@example.org'));
        self::assertNull($command->getUserNotificationThreadId(
            'FS_reply-123-'.Mail::getMessageIdHash(123).'@example.org'
        ));
        self::assertNull($command->getUserNotificationThreadId('regular-message@example.org'));
    }

    private function notificationMessageId()
    {
        return 'FS_notify-123-456-'.Mail::getMessageIdHash(123).'@example.org';
    }
}
