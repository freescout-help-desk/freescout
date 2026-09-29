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
        };
    }
}
