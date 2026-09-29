<?php

namespace Modules\Workflows\Tests\Unit;

use App\Conversation;
use App\Thread;
use Modules\Workflows\Services\ActionRunner;
use Tests\TestCase;

class ActionRunnerTest extends TestCase
{
    public function test_change_status_closed_calls_change_status(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];

        $result = ActionRunner::perform('change_status', 'closed', $this->context($conversation, $user));

        $this->assertSame('done', $result);
        $this->assertSame([
            ['changeStatus', Conversation::STATUS_CLOSED, $user, true],
        ], $conversation->calls);
    }

    public function test_change_status_maps_active_pending_and_spam(): void
    {
        $map = [
            'active' => Conversation::STATUS_ACTIVE,
            'pending' => Conversation::STATUS_PENDING,
            'spam' => Conversation::STATUS_SPAM,
        ];

        foreach ($map as $slug => $status) {
            $conversation = $this->conversationDouble();
            $user = (object) ['id' => 9];

            $result = ActionRunner::perform('change_status', $slug, $this->context($conversation, $user));

            $this->assertSame('done', $result);
            $this->assertSame([
                ['changeStatus', $status, $user, true],
            ], $conversation->calls);
        }
    }

    public function test_unknown_status_slug_returns_done_without_calling_change_status(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];

        $result = ActionRunner::perform('change_status', 'open', $this->context($conversation, $user));

        $this->assertSame('done', $result);
        $this->assertSame([], $conversation->calls);
    }

    public function test_assign_calls_change_user_when_the_user_is_available(): void
    {
        $seen = null;
        $callback = function ($available, $userId) use (&$seen) {
            $seen = [$available, $userId];

            return true;
        };
        \Eventy::addFilter('user.is_user_available', $callback, 20, 2);

        try {
            $conversation = $this->conversationDouble();
            $user = (object) ['id' => 9];
            $result = ActionRunner::perform('assign', [
                'user_id' => 4,
                'only_if_available' => true,
            ], $this->context($conversation, $user));

            $this->assertSame('done', $result);
            $this->assertSame([true, 4], $seen);
            $this->assertSame([
                ['changeUser', 4, $user, true],
            ], $conversation->calls);
        } finally {
            \Eventy::removeFilter('user.is_user_available', $callback, 20);
        }
    }

    public function test_assign_skips_change_user_when_the_user_is_not_available(): void
    {
        $callback = function ($available, $userId) {
            return false;
        };
        \Eventy::addFilter('user.is_user_available', $callback, 20, 2);

        try {
            $conversation = $this->conversationDouble();
            $user = (object) ['id' => 9];
            $result = ActionRunner::perform('assign', [
                'user_id' => 4,
                'only_if_available' => true,
            ], $this->context($conversation, $user));

            $this->assertSame('done', $result);
            $this->assertSame([], $conversation->calls);
        } finally {
            \Eventy::removeFilter('user.is_user_available', $callback, 20);
        }
    }

    public function test_only_if_available_accepts_integer_one_and_string_one(): void
    {
        foreach ([1, '1'] as $flag) {
            foreach ([true, false] as $available) {
                $seenUserId = null;
                $callback = function ($isAvailable, $userId) use (&$seenUserId, $available) {
                    $seenUserId = $userId;

                    return $available;
                };
                \Eventy::addFilter('user.is_user_available', $callback, 20, 2);

                try {
                    $conversation = $this->conversationDouble();
                    $user = (object) ['id' => 9];
                    $result = ActionRunner::perform('assign', [
                        'user_id' => 4,
                        'only_if_available' => $flag,
                    ], $this->context($conversation, $user));

                    $this->assertSame('done', $result);
                    $this->assertSame(4, $seenUserId);
                    if ($available) {
                        $this->assertSame([
                            ['changeUser', 4, $user, true],
                        ], $conversation->calls);
                    } else {
                        $this->assertSame([], $conversation->calls);
                    }
                } finally {
                    \Eventy::removeFilter('user.is_user_available', $callback, 20);
                }
            }
        }
    }

    public function test_assign_without_the_flag_calls_change_user_even_when_the_filter_returns_false(): void
    {
        $consulted = false;
        $callback = function ($available, $userId) use (&$consulted) {
            $consulted = true;

            return false;
        };
        \Eventy::addFilter('user.is_user_available', $callback, 20, 2);

        try {
            $conversation = $this->conversationDouble();
            $user = (object) ['id' => 9];
            $result = ActionRunner::perform('assign', [
                'user_id' => 4,
            ], $this->context($conversation, $user));

            $this->assertSame('done', $result);
            $this->assertFalse($consulted);
            $this->assertSame([
                ['changeUser', 4, $user, true],
            ], $conversation->calls);
        } finally {
            \Eventy::removeFilter('user.is_user_available', $callback, 20);
        }
    }

    public function test_assign_with_only_if_available_false_does_not_consult_the_filter(): void
    {
        $consulted = false;
        $callback = function ($available, $userId) use (&$consulted) {
            $consulted = true;

            return false;
        };
        \Eventy::addFilter('user.is_user_available', $callback, 20, 2);

        try {
            $conversation = $this->conversationDouble();
            $user = (object) ['id' => 9];
            $result = ActionRunner::perform('assign', [
                'user_id' => 4,
                'only_if_available' => false,
            ], $this->context($conversation, $user));

            $this->assertSame('done', $result);
            $this->assertFalse($consulted);
            $this->assertSame([
                ['changeUser', 4, $user, true],
            ], $conversation->calls);
        } finally {
            \Eventy::removeFilter('user.is_user_available', $callback, 20);
        }
    }

    public function test_add_note_creates_a_note_thread(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];
        $body = 'Waiting on the carrier';

        $result = ActionRunner::perform('add_note', $body, $this->context($conversation, $user));

        $this->assertSame('done', $result);
        $this->assertSame([
            ['createUserThread', $user, $body, ['type' => Thread::TYPE_NOTE]],
        ], $conversation->calls);
    }

    public function test_move_deleted_calls_delete_to_folder(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];

        $result = ActionRunner::perform('move_deleted', null, $this->context($conversation, $user));

        $this->assertSame('done', $result);
        $this->assertSame([
            ['deleteToFolder', $user],
        ], $conversation->calls);
    }

    public function test_delete_forever_calls_delete(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];

        $result = ActionRunner::perform('delete_forever', null, $this->context($conversation, $user));

        $this->assertSame('done', $result);
        $this->assertSame([
            ['delete'],
        ], $conversation->calls);
    }

    public function test_move_mailbox_calls_move_to_mailbox(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];

        $result = ActionRunner::perform('move_mailbox', 15, $this->context($conversation, $user));

        $this->assertSame('done', $result);
        $this->assertSame([
            ['moveToMailbox', 15, $user],
        ], $conversation->calls);
    }

    public function test_stop_returns_stop_and_does_not_touch_the_conversation(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];

        $result = ActionRunner::perform('stop', null, $this->context($conversation, $user));

        $this->assertSame('stop', $result);
        $this->assertSame([], $conversation->calls);
    }

    public function test_unknown_action_returns_done_when_the_filter_returns_true(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];
        $workflow = (object) ['id' => 3];
        $value = ['board' => 'support'];
        $seen = null;
        $callback = function ($performed, $type, $operator, $value, $conversation, $workflow) use (&$seen) {
            $seen = [$performed, $type, $operator, $value, $conversation, $workflow];

            return true;
        };
        \Eventy::addFilter('workflow.perform_action', $callback, 20, 6);

        try {
            $context = $this->context($conversation, $user);
            $context->workflow = $workflow;

            $result = ActionRunner::perform('add_to_kanban', $value, $context);

            $this->assertSame('done', $result);
            $this->assertSame([], $conversation->calls);
            $this->assertSame([
                false,
                'add_to_kanban',
                null,
                $value,
                $conversation,
                $workflow,
            ], $seen);
        } finally {
            \Eventy::removeFilter('workflow.perform_action', $callback, 20);
        }
    }

    public function test_unknown_action_returns_done_when_the_filter_returns_false(): void
    {
        $conversation = $this->conversationDouble();
        $user = (object) ['id' => 9];
        $seenWorkflow = 'missing';
        $callback = function ($performed, $type, $operator, $value, $conversation, $workflow) use (&$seenWorkflow) {
            $seenWorkflow = $workflow;

            return false;
        };
        \Eventy::addFilter('workflow.perform_action', $callback, 20, 6);

        try {
            $result = ActionRunner::perform(
                'add_to_kanban',
                'board-2',
                $this->context($conversation, $user)
            );

            $this->assertSame('done', $result);
            $this->assertNull($seenWorkflow);
            $this->assertSame([], $conversation->calls);
        } finally {
            \Eventy::removeFilter('workflow.perform_action', $callback, 20);
        }
    }

    public function test_reply_sends_the_replaced_body(): void
    {
        $conversation = $this->mailConversation();
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $body = 'Hello {%user.fullName%}';
        $expected = $this->replacedBody($conversation, $user, $body);
        $context = $this->context($conversation, $user);
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('reply', $body, $context);

        $this->assertSame('done', $result);
        $this->assertSame([
            ['createUserThread', $user, $expected, ['type' => Thread::TYPE_MESSAGE]],
        ], $conversation->calls);
        $this->assertTrue($context->send_reply);
        $this->assertSame($expected, $context->body);
        $this->assertStringContainsString('Workflow Person', $context->body);
        $this->assertSame([
            ['sendReply', $conversation, $conversation->thread],
        ], $gateway->calls);
    }

    public function test_email_customer_sends_plain_text_without_a_thread(): void
    {
        $conversation = $this->mailConversation();
        $conversation->type = Conversation::TYPE_EMAIL;
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $body = 'Hello {%user.fullName%}';
        $expected = $this->replacedBody($conversation, $user, $body);
        $context = $this->context($conversation, $user);
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('email_customer', $body, $context);

        $this->assertSame('done', $result);
        $this->assertSame([], $conversation->calls);
        $this->assertTrue($context->send_plain);
        $this->assertFalse($context->send_reply);
        $this->assertSame($conversation->subject, $context->recorded_subject);
        $this->assertSame($expected, $context->body);
        $this->assertStringContainsString('Workflow Person', $context->body);
        $this->assertSame([
            ['sendPlain', $conversation, $expected],
        ], $gateway->calls);
    }

    public function test_email_customer_skips_chat_without_sending(): void
    {
        $conversation = $this->mailConversation();
        $conversation->type = Conversation::TYPE_CHAT;
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $context = $this->context($conversation, $user);
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('email_customer', 'Hello {%user.fullName%}', $context);

        $this->assertSame('done', $result);
        $this->assertFalse(property_exists($context, 'send_reply'));
        $this->assertFalse(property_exists($context, 'send_plain'));
        $this->assertSame([], $gateway->calls);
        $this->assertSame([], $conversation->calls);
    }

    public function test_email_customer_skips_a_string_chat_type_without_sending(): void
    {
        $conversation = $this->mailConversation();
        $conversation->type = '3';
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $context = $this->context($conversation, $user);
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('email_customer', 'Hello {%user.fullName%}', $context);

        $this->assertSame('done', $result);
        $this->assertFalse(property_exists($context, 'send_reply'));
        $this->assertFalse(property_exists($context, 'send_plain'));
        $this->assertSame([], $gateway->calls);
        $this->assertSame([], $conversation->calls);
    }

    public function test_forward_calls_forward_with_the_replaced_body(): void
    {
        $conversation = $this->mailConversation();
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $body = 'Please see {%user.fullName%}';
        $expected = $this->replacedBody($conversation, $user, $body);
        $context = $this->context($conversation, $user);
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('forward', [
            'body' => $body,
            'to' => 'ops@example.com',
        ], $context);

        $this->assertSame('done', $result);
        $this->assertSame([
            ['forward', $user, $expected, 'ops@example.com'],
        ], $conversation->calls);
        $this->assertStringContainsString('Workflow Person', $conversation->calls[0][2]);
        $this->assertFalse(property_exists($context, 'send_reply'));
        $this->assertFalse(property_exists($context, 'send_plain'));
        $this->assertSame([], $gateway->calls);
    }

    public function test_notification_records_the_assignee(): void
    {
        $conversation = $this->mailConversation();
        $conversation->user_id = 12;
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $context = $this->context($conversation, $user);
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('notification', 'assignee', $context);

        $this->assertSame('done', $result);
        $this->assertSame(12, $context->notification_user_id);
        $this->assertSame([], $gateway->calls);
        $this->assertSame([], $conversation->calls);
    }

    public function test_notification_records_the_last_user(): void
    {
        $conversation = $this->mailConversation();
        $user = $this->workflowUser();
        $gateway = $this->recordingGateway();
        $context = $this->context($conversation, $user);
        $context->last_user_id = 8;
        $context->mailGateway = $gateway;

        $result = ActionRunner::perform('notification', 'last_user', $context);

        $this->assertSame('done', $result);
        $this->assertSame(8, $context->notification_user_id);
        $this->assertSame([], $gateway->calls);
        $this->assertSame([], $conversation->calls);
    }

    public function test_notification_records_an_integer_or_numeric_string_user_id(): void
    {
        foreach ([15, '15'] as $value) {
            $conversation = $this->mailConversation();
            $user = $this->workflowUser();
            $gateway = $this->recordingGateway();
            $context = $this->context($conversation, $user);
            $context->mailGateway = $gateway;

            $result = ActionRunner::perform('notification', $value, $context);

            $this->assertSame('done', $result);
            $this->assertSame(15, $context->notification_user_id);
            $this->assertSame([], $gateway->calls);
            $this->assertSame([], $conversation->calls);
        }
    }

    public function test_disable_auto_reply_sets_ar_off_meta(): void
    {
        $conversation = $this->mailConversation();
        $conversation->id = 44;
        $id = $conversation->id;
        $user = $this->workflowUser();
        $callback = function ($send, $conversation) use ($id) {
            if ($conversation && $conversation->id === $id) {
                return false;
            }

            return $send;
        };
        \Eventy::addFilter('autoreply.should_send', $callback, 20, 2);

        try {
            $result = ActionRunner::perform('disable_auto_reply', null, $this->context($conversation, $user));

            $this->assertSame('done', $result);
            $this->assertSame([
                ['setMeta', 'ar_off', true, true],
            ], $conversation->calls);
            $this->assertFalse(\Eventy::filter('autoreply.should_send', true, $conversation));
        } finally {
            \Eventy::removeFilter('autoreply.should_send', $callback, 20);
        }
    }

    public function test_trigger_webhook_fires_the_event_name(): void
    {
        $conversation = $this->mailConversation();
        $user = $this->workflowUser();
        $workflow = (object) ['id' => 3];
        $seen = null;
        $callback = function ($eventName, $conversation, $workflow) use (&$seen) {
            $seen = [$eventName, $conversation, $workflow];
        };
        \Eventy::addAction('workflow.webhook', $callback, 20, 3);

        try {
            $context = $this->context($conversation, $user);
            $context->workflow = $workflow;

            $result = ActionRunner::perform('trigger_webhook', 'ticket.escalated', $context);

            $this->assertSame('done', $result);
            $this->assertSame(['ticket.escalated', $conversation, $workflow], $seen);
        } finally {
            \Eventy::removeAction('workflow.webhook', $callback, 20);
        }
    }

    /**
     * @param object $conversation
     * @param object $workflowUser
     * @return \stdClass
     */
    private function context($conversation, $workflowUser)
    {
        $context = new \stdClass();
        $context->conversation = $conversation;
        $context->workflowUser = $workflowUser;

        return $context;
    }

    /**
     * @return object
     */
    private function conversationDouble()
    {
        return new class {
            public $calls = [];
            public $thread = null;
            public $subject = null;
            public $number = null;
            public $customer_email = null;
            public $mailbox = null;
            public $customer = null;
            public $type = null;
            public $user_id = null;
            public $id = null;

            public function changeStatus($status, $user, $createThread)
            {
                $this->calls[] = ['changeStatus', $status, $user, $createThread];
            }

            public function changeUser($userId, $user, $createThread)
            {
                $this->calls[] = ['changeUser', $userId, $user, $createThread];
            }

            public function createUserThread($user, $body, $data = [])
            {
                $this->calls[] = ['createUserThread', $user, $body, $data];
                $this->thread = (object) ['id' => 501];

                return $this->thread;
            }

            public function deleteToFolder($user)
            {
                $this->calls[] = ['deleteToFolder', $user];
            }

            public function delete()
            {
                $this->calls[] = ['delete'];
            }

            public function moveToMailbox($mailboxId, $user)
            {
                $this->calls[] = ['moveToMailbox', $mailboxId, $user];
            }

            public function forward($user, $body, $to = '')
            {
                $this->calls[] = ['forward', $user, $body, $to];
            }

            public function setMeta($key, $value, $save = false)
            {
                $this->calls[] = ['setMeta', $key, $value, $save];
            }
        };
    }

    /**
     * @return object
     */
    private function mailConversation()
    {
        $conversation = $this->conversationDouble();
        $conversation->subject = 'Where is my order';
        $conversation->number = 1001;
        $conversation->customer_email = 'customer@example.com';
        $conversation->mailbox = null;
        $conversation->customer = null;

        return $conversation;
    }

    /**
     * Workflow user, not an App\User. replaceMailVars reads these members.
     *
     * @return object
     */
    private function workflowUser()
    {
        return new class {
            public $phone = '555-0100';
            public $email = 'workflow@localhost';
            public $job_title = 'Automation';
            public $last_name = 'Person';

            public function getFullName()
            {
                return 'Workflow Person';
            }

            public function getFirstName()
            {
                return 'Workflow';
            }

            public function getPhotoUrl()
            {
                return 'https://example.test/workflow.png';
            }
        };
    }

    /**
     * @return object
     */
    private function recordingGateway()
    {
        return new class {
            public $calls = [];

            public function sendReply($conversation, $thread)
            {
                $this->calls[] = ['sendReply', $conversation, $thread];
            }

            public function sendPlain($conversation, $body)
            {
                $this->calls[] = ['sendPlain', $conversation, $body];
            }
        };
    }

    /**
     * @param object $conversation
     * @param object $user
     * @param string $body
     * @return string
     */
    private function replacedBody($conversation, $user, $body)
    {
        return \App\Misc\Mail::replaceMailVars($body, [
            'conversation' => $conversation,
            'mailbox' => $conversation->mailbox,
            'customer' => $conversation->customer,
            'user' => $user,
        ], false, false);
    }
}
