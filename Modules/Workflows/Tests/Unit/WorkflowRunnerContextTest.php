<?php

namespace Modules\Workflows\Tests\Unit;

use App\Conversation;
use App\Thread;
use Modules\Workflows\Services\WorkflowRunner;
use Tests\TestCase;

class WorkflowRunnerContextTest extends TestCase
{
    public function test_last_user_reply_at_uses_the_latest_message_after_a_customer_reply(): void
    {
        $message = $this->thread(Thread::TYPE_MESSAGE, 'On it', '2026-08-01 12:00:00');
        $conversation = $this->conversation([
            Thread::TYPE_MESSAGE => $message,
        ], [
            'last_reply_from' => Conversation::PERSON_CUSTOMER,
            'last_reply_at' => '2026-09-01 00:00:00',
        ]);

        $data = $this->conversationArray($conversation, null, ['name' => 'schedule']);

        $this->assertSame('2026-08-01 12:00:00', isset($data['last_user_reply_at']) ? $data['last_user_reply_at'] : null);
    }

    public function test_a_missing_trigger_thread_fills_latest_bodies_and_blocks_not_contains(): void
    {
        $customer = $this->thread(Thread::TYPE_CUSTOMER, 'please unsubscribe', null, null, ['invoice.pdf']);
        $user = $this->thread(Thread::TYPE_MESSAGE, 'we replied yesterday');
        $note = $this->thread(Thread::TYPE_NOTE, 'internal note');
        $conversation = $this->conversation([
            Thread::TYPE_CUSTOMER => $customer,
            Thread::TYPE_MESSAGE => $user,
            Thread::TYPE_NOTE => $note,
        ]);

        $data = $this->conversationArray($conversation, null, ['name' => 'customer_reply']);

        $this->assertSame([
            'customer' => 'please unsubscribe',
            'user' => 'we replied yesterday',
            'note' => 'internal note',
        ], isset($data['latest_body_by_source']) ? $data['latest_body_by_source'] : null);
        $this->assertTrue($data['has_attachment']);

        $workflow = [
            'id' => 7,
            'sort_order' => 1,
            'apply_to_previous' => true,
            'match' => 'all',
            'conditions' => [
                [
                    'type' => 'body',
                    'operator' => 'not_contains',
                    'value' => ['source' => 'customer', 'text' => 'unsubscribe'],
                ],
            ],
        ];
        $selected = WorkflowRunner::select([$workflow], $data, ['name' => 'customer_reply'], []);

        $this->assertSame([], $selected);
    }

    public function test_a_missing_trigger_thread_without_files_has_no_attachment(): void
    {
        $conversation = $this->conversation([
            Thread::TYPE_CUSTOMER => $this->thread(Thread::TYPE_CUSTOMER, 'hello'),
            Thread::TYPE_MESSAGE => $this->thread(Thread::TYPE_MESSAGE, 'reply'),
            Thread::TYPE_NOTE => $this->thread(Thread::TYPE_NOTE, 'note'),
        ]);

        $data = $this->conversationArray($conversation, null, ['name' => 'schedule']);

        $this->assertFalse($data['has_attachment']);
    }

    public function test_customer_viewed_follows_the_latest_message_when_the_trigger_is_not_one(): void
    {
        $opened = $this->thread(Thread::TYPE_MESSAGE, 'sent', '2026-08-01 12:00:00', '2026-08-02 09:00:00');
        $conversation = $this->conversation([
            Thread::TYPE_MESSAGE => $opened,
        ]);
        $trigger = $this->thread(Thread::TYPE_CUSTOMER, 'new question');

        $data = $this->conversationArray($conversation, $trigger, ['name' => 'customer_reply']);

        $this->assertTrue($data['customer_viewed']);

        $unopened = $this->thread(Thread::TYPE_MESSAGE, 'sent', '2026-08-01 12:00:00', null);
        $closed = $this->conversation([
            Thread::TYPE_MESSAGE => $unopened,
        ]);

        $again = $this->conversationArray($closed, null, ['name' => 'schedule']);

        $this->assertFalse($again['customer_viewed']);
    }

    public function test_an_unopened_trigger_message_stays_unviewed(): void
    {
        $older = $this->thread(Thread::TYPE_MESSAGE, 'old', '2026-07-01 00:00:00', '2026-07-02 00:00:00');
        $conversation = $this->conversation([
            Thread::TYPE_MESSAGE => $older,
        ]);
        $trigger = $this->thread(Thread::TYPE_MESSAGE, 'just sent', '2026-09-01 00:00:00', null);

        $data = $this->conversationArray($conversation, $trigger, ['name' => 'user_reply']);

        $this->assertFalse($data['customer_viewed']);
    }

    public function test_trigger_text_fills_only_a_source_get_last_thread_missed(): void
    {
        $conversation = $this->conversation([
            Thread::TYPE_CUSTOMER => $this->thread(Thread::TYPE_CUSTOMER, 'latest customer'),
        ]);
        $trigger = $this->thread(Thread::TYPE_CUSTOMER, 'trigger text');

        $data = $this->conversationArray($conversation, $trigger, ['name' => 'customer_reply']);

        $this->assertSame('trigger text', $data['trigger_body']);
        $this->assertSame('customer', $data['trigger_source']);
        $this->assertSame('latest customer', $data['latest_body_by_source']['customer']);

        $empty = $this->conversation([]);
        $onlyTrigger = $this->conversationArray($empty, $trigger, ['name' => 'customer_reply']);

        $this->assertSame('trigger text', $onlyTrigger['latest_body_by_source']['customer']);
    }

    public function test_conversation_array_copies_subject_and_customer_email(): void
    {
        $conversation = new \stdClass();
        $conversation->subject = 'Billing';
        $conversation->customer_email = 'a@b.test';

        $data = $this->conversationArray($conversation, null, ['name' => 'customer_reply']);

        $this->assertSame('Billing', $data['subject']);
        $this->assertSame('a@b.test', $data['customer_email']);
    }

    public function test_tags_come_from_the_conversation_tags_filter(): void
    {
        $conversation = new \stdClass();
        $conversation->state = Conversation::STATE_PUBLISHED;
        $trigger = ['name' => 'customer_reply', 'added_tag' => 'vip'];
        $seen = null;
        $callback = function ($tags, $seenConversation, $seenTrigger) use (&$seen, $conversation, $trigger) {
            $seen = [$seenConversation, $seenTrigger];

            return ['billing'];
        };
        \Eventy::addFilter('workflow.conversation_tags', $callback, 20, 3);

        try {
            $data = $this->conversationArray($conversation, null, $trigger);

            $this->assertSame([$conversation, $trigger], $seen);
            $this->assertSame(['billing'], $data['tags']);
            $this->assertSame('vip', isset($data['added_tag']) ? $data['added_tag'] : null);
        } finally {
            \Eventy::removeFilter('workflow.conversation_tags', $callback, 20);
        }
    }

    public function test_a_throwing_get_last_thread_does_not_escape(): void
    {
        $conversation = new class {
            public $state;

            public function __construct()
            {
                $this->state = Conversation::STATE_PUBLISHED;
            }

            public function getLastThread($types = [])
            {
                throw new \RuntimeException('down');
            }
        };

        $data = $this->conversationArray($conversation, null, ['name' => 'schedule']);

        $this->assertFalse($data['customer_viewed']);
        $this->assertFalse($data['has_attachment']);
        $this->assertArrayNotHasKey('last_user_reply_at', $data);
    }

    /**
     * @param object     $conversation
     * @param mixed      $thread
     * @param array      $trigger
     * @return array
     */
    private function conversationArray($conversation, $thread, array $trigger)
    {
        $method = new \ReflectionMethod(WorkflowRunner::class, 'conversationArray');
        $method->setAccessible(true);

        return $method->invoke(null, $conversation, $thread, $trigger);
    }

    /**
     * @param array $threadsByType
     * @param array $attributes
     * @return object
     */
    private function conversation(array $threadsByType, array $attributes = [])
    {
        return new class($threadsByType, $attributes) {
            public $state;
            public $last_reply_from;
            public $last_reply_at;
            public $created_at;

            private $threadsByType;

            public function __construct(array $threadsByType, array $attributes)
            {
                $this->threadsByType = $threadsByType;
                $this->state = array_key_exists('state', $attributes)
                    ? $attributes['state']
                    : Conversation::STATE_PUBLISHED;
                $this->last_reply_from = array_key_exists('last_reply_from', $attributes)
                    ? $attributes['last_reply_from']
                    : null;
                $this->last_reply_at = array_key_exists('last_reply_at', $attributes)
                    ? $attributes['last_reply_at']
                    : null;
                $this->created_at = array_key_exists('created_at', $attributes)
                    ? $attributes['created_at']
                    : null;
            }

            public function getLastThread($types = [])
            {
                $type = is_array($types) && array_key_exists(0, $types) ? $types[0] : null;
                if ($type !== null && array_key_exists($type, $this->threadsByType)) {
                    return $this->threadsByType[$type];
                }

                return null;
            }

            public function getLastCustomerReplyAt()
            {
                return null;
            }
        };
    }

    /**
     * @param int         $type
     * @param string|null $body
     * @param string|null $createdAt
     * @param mixed       $openedAt
     * @param array       $attachments
     * @return object
     */
    private function thread($type, $body, $createdAt = null, $openedAt = null, array $attachments = [])
    {
        return new class($type, $body, $createdAt, $openedAt, $attachments) {
            public $type;
            public $created_at;
            public $opened_at;
            public $attachments;

            private $body;

            public function __construct($type, $body, $createdAt, $openedAt, array $attachments)
            {
                $this->type = $type;
                $this->body = $body;
                $this->created_at = $createdAt;
                $this->opened_at = $openedAt;
                $this->attachments = $attachments;
            }

            public function getBodyAsText($options = ['width' => 0])
            {
                return $this->body;
            }

            public function attachments()
            {
                return $this->attachments;
            }
        };
    }
}
