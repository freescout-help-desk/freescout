<?php

namespace Modules\Workflows\Tests\Unit;

use App\Conversation;
use App\User;
use Modules\Workflows\Listeners\RunWorkflows;
use Modules\Workflows\Providers\WorkflowsServiceProvider;
use Modules\Workflows\Services\WorkflowRunner;
use Tests\TestCase;

class RunWorkflowsTriggerTest extends TestCase
{
    public function test_trigger_for_maps_events_and_skips_drafts_and_unknown_names(): void
    {
        $published = ['state' => Conversation::STATE_PUBLISHED];
        $names = [
            'conversation.created_by_customer' => 'new',
            'conversation.customer_replied' => 'customer_reply',
            'conversation.user_replied' => 'user_reply',
            'conversation.note_added' => 'user_note',
            'conversation.moved' => 'moved',
            'conversation.status_changed' => 'updated',
            'conversation.user_changed' => 'updated',
            'conversation.subject_changed' => 'updated',
            'conversation.state_changed' => 'updated',
        ];

        foreach ($names as $eventName => $triggerName) {
            $this->assertSame(
                ['name' => $triggerName],
                RunWorkflows::triggerFor($eventName, $published)
            );
        }

        $draft = new \stdClass();
        $draft->state = Conversation::STATE_DRAFT;
        $this->assertNull(RunWorkflows::triggerFor('conversation.created_by_customer', $draft));

        $stringDraft = ['state' => (string) Conversation::STATE_DRAFT];
        $this->assertNull(RunWorkflows::triggerFor('conversation.customer_replied', $stringDraft));

        $missingState = ['status' => Conversation::STATUS_ACTIVE];
        $this->assertSame(
            ['name' => 'user_note'],
            RunWorkflows::triggerFor('conversation.note_added', $missingState)
        );

        $this->assertNull(RunWorkflows::triggerFor('conversation.deleted', $published));
        $this->assertNull(RunWorkflows::triggerFor('mailbox.updated', $published));
    }

    public function test_updated_is_not_a_date_trigger(): void
    {
        $workflow = [
            'apply_to_previous' => true,
            'created_at' => '2026-09-29 12:00:00',
            'conditions' => [
                ['type' => 'waiting_since', 'operator' => 'in_the_last', 'value' => ['number' => 1, 'unit' => 'days']],
            ],
        ];
        $conversation = [
            'state' => Conversation::STATE_PUBLISHED,
            'created_at' => '2026-09-29 12:00:00',
        ];

        $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'updated']));
        $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'schedule']));
    }

    public function test_hooks_register_the_schedule_filters_and_conversation_actions(): void
    {
        $events = $this->eventSpy();

        WorkflowsServiceProvider::hooks($events);

        $filters = [
            'schedule' => 1,
            'user.is_user_available' => 2,
            'autoreply.should_send' => 2,
        ];
        foreach ($filters as $name => $arguments) {
            $hook = $this->recorded($events->filters, $name);
            $this->assertNotNull($hook, $name);
            $this->assertSame($arguments, $hook['arguments']);
        }

        $actions = [
            'conversation.created_by_customer' => 3,
            'conversation.customer_replied' => 3,
            'conversation.user_replied' => 2,
            'conversation.note_added' => 2,
            'conversation.moved' => 3,
            'conversation.status_changed' => 4,
            'conversation.user_changed' => 3,
            'conversation.subject_changed' => 3,
            'conversation.state_changed' => 3,
            'mailboxes.settings.menu' => 1,
        ];
        foreach ($actions as $name => $arguments) {
            $hook = $this->recorded($events->actions, $name);
            $this->assertNotNull($hook, $name);
            $this->assertSame($arguments, $hook['arguments']);
        }

        $scheduler = new class {
            public $name;
            public $everyMinute = false;
            public $withoutOverlapping = false;

            public function command($name)
            {
                $this->name = $name;

                return $this;
            }

            public function everyMinute()
            {
                $this->everyMinute = true;

                return $this;
            }

            public function withoutOverlapping($expiresAt = 1440)
            {
                $this->withoutOverlapping = true;

                return $this;
            }
        };
        $schedule = $this->recorded($events->filters, 'schedule');
        $returned = call_user_func($schedule['callback'], $scheduler);

        $this->assertSame($scheduler, $returned);
        $this->assertSame('freescout:workflows-process', $scheduler->name);
        $this->assertTrue($scheduler->everyMinute);
        $this->assertTrue($scheduler->withoutOverlapping);

        $draft = ['state' => (string) Conversation::STATE_DRAFT];
        foreach ($events->actions as $action) {
            $args = array_fill(0, $action['arguments'], null);
            $args[0] = $draft;
            call_user_func_array($action['callback'], $args);
        }
    }

    public function test_user_is_available_keeps_false_and_checks_active_status(): void
    {
        $events = $this->eventSpy();
        WorkflowsServiceProvider::hooks($events);
        $callback = $this->recorded($events->filters, 'user.is_user_available')['callback'];

        $active = new \stdClass();
        $active->status = User::STATUS_ACTIVE;
        $disabled = new \stdClass();
        $disabled->status = User::STATUS_DISABLED;

        $this->assertFalse($callback(false, $active));
        $this->assertSame(0, $callback(0, $active));
        $this->assertTrue($callback(true, $active));
        $this->assertFalse($callback(true, $disabled));
        $this->assertFalse($callback(true, 'nobody'));
        $this->assertFalse($callback(true, null));

        // MySQL is down in this suite. The lookup must not replace the incoming value.
        $this->assertTrue($callback(true, 4));
        $this->assertTrue($callback(true, '4'));
        $this->assertFalse($callback(false, 4));
    }

    public function test_autoreply_should_send_ignores_conversations_without_meta(): void
    {
        $events = $this->eventSpy();
        WorkflowsServiceProvider::hooks($events);
        $callback = $this->recorded($events->filters, 'autoreply.should_send')['callback'];

        $plain = new \stdClass();
        $this->assertTrue($callback(true, $plain));
        $this->assertFalse($callback(false, $plain));
        $this->assertSame('keep', $callback('keep', $plain));

        $blocked = new class {
            public function getMeta($key)
            {
                return $key === 'ar_off';
            }
        };
        $this->assertFalse($callback(true, $blocked));

        $open = new class {
            public function getMeta($key)
            {
                return false;
            }
        };
        $this->assertTrue($callback(true, $open));
        $this->assertNull($callback(null, 'not-a-conversation'));
    }

    /**
     * @return object
     */
    private function eventSpy()
    {
        return new class {
            public $filters = [];
            public $actions = [];

            public function addFilter($hook, $callback, $priority = 20, $arguments = 1)
            {
                $this->filters[] = [
                    'hook' => $hook,
                    'callback' => $callback,
                    'priority' => $priority,
                    'arguments' => $arguments,
                ];
            }

            public function addAction($hook, $callback, $priority = 20, $arguments = 1)
            {
                $this->actions[] = [
                    'hook' => $hook,
                    'callback' => $callback,
                    'priority' => $priority,
                    'arguments' => $arguments,
                ];
            }
        };
    }

    /**
     * @param array  $records
     * @param string $name
     * @return array|null
     */
    private function recorded(array $records, string $name): ?array
    {
        foreach ($records as $record) {
            if ($record['hook'] === $name) {
                return $record;
            }
        }

        return null;
    }
}
