<?php

namespace Modules\Workflows\Tests\Unit;

use App\Conversation;
use Modules\Workflows\Services\WorkflowRunner;
use Tests\TestCase;

class WorkflowRunnerTest extends TestCase
{
    public function test_a_draft_is_never_eligible(): void
    {
        $workflow = $this->workflow([
            'apply_to_previous' => true,
            'created_at' => '2026-09-29 12:00:00',
            'conditions' => [
                ['type' => 'waiting_since', 'operator' => 'in_the_last', 'value' => ['number' => 1, 'unit' => 'days']],
            ],
        ]);
        $conversation = $this->conversation([
            'state' => Conversation::STATE_DRAFT,
            'created_at' => '2026-09-29 12:00:00',
        ]);

        $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'schedule']));

        $conversation['state'] = Conversation::STATE_PUBLISHED;
        $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'schedule']));
    }

    public function test_older_conversations_need_apply_to_previous_strictly_true(): void
    {
        $conversation = $this->conversation(['created_at' => '2026-08-01 00:00:00']);
        $trigger = ['name' => 'customer_reply'];
        $missing = $this->workflow(['created_at' => '2026-09-01 00:00:00']);
        unset($missing['apply_to_previous']);

        $this->assertFalse(WorkflowRunner::eligible($missing, $conversation, $trigger));

        foreach ([false, 0, '0'] as $flag) {
            $workflow = $this->workflow([
                'apply_to_previous' => $flag,
                'created_at' => '2026-09-01 00:00:00',
            ]);
            $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, $trigger));
        }

        $included = $this->workflow([
            'apply_to_previous' => true,
            'created_at' => '2026-09-01 00:00:00',
        ]);
        $this->assertTrue(WorkflowRunner::eligible($included, $conversation, $trigger));

        $newer = $this->conversation(['created_at' => '2026-10-01 00:00:00']);
        $closed = $this->workflow([
            'apply_to_previous' => false,
            'created_at' => '2026-09-01 00:00:00',
        ]);
        $this->assertTrue(WorkflowRunner::eligible($closed, $newer, $trigger));
    }

    public function test_a_stored_apply_to_previous_flag_of_one_includes_older_conversations(): void
    {
        $conversation = $this->conversation(['created_at' => '2026-08-01 00:00:00']);
        $trigger = ['name' => 'customer_reply'];

        foreach ([1, '1'] as $flag) {
            $workflow = $this->workflow([
                'apply_to_previous' => $flag,
                'created_at' => '2026-09-01 00:00:00',
            ]);
            $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, $trigger));
        }

        foreach ([0, '0'] as $flag) {
            $workflow = $this->workflow([
                'apply_to_previous' => $flag,
                'created_at' => '2026-09-01 00:00:00',
            ]);
            $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, $trigger));
        }
    }

    public function test_equal_created_at_stays_eligible_without_apply_to_previous(): void
    {
        $workflow = $this->workflow([
            'apply_to_previous' => false,
            'created_at' => '2026-09-01 12:00:00',
        ]);
        $conversation = $this->conversation(['created_at' => '2026-09-01 12:00:00']);

        $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'customer_reply']));
    }

    public function test_a_missing_created_at_skips_the_previous_conversation_rule(): void
    {
        $trigger = ['name' => 'customer_reply'];
        $workflow = $this->workflow([
            'apply_to_previous' => false,
            'created_at' => '2026-09-01 00:00:00',
        ]);
        $conversation = $this->conversation();
        unset($conversation['created_at']);

        $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, $trigger));

        unset($workflow['created_at']);
        $older = $this->conversation(['created_at' => '2020-01-01 00:00:00']);

        $this->assertTrue(WorkflowRunner::eligible($workflow, $older, $trigger));
    }

    public function test_date_workflows_are_eligible_only_for_the_schedule_trigger(): void
    {
        $conversation = $this->conversation();
        $types = ['waiting_since', 'last_user_reply', 'last_customer_reply', 'date_created'];

        foreach ($types as $type) {
            $workflow = $this->workflow([
                'apply_to_previous' => true,
                'conditions' => [
                    ['type' => $type, 'operator' => 'in_the_last', 'value' => ['number' => 2, 'unit' => 'hours']],
                ],
            ]);

            $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'customer_reply']));
            $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'moved']));
            $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'user_reply']));
            $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'schedule']));
        }
    }

    public function test_a_workflow_without_a_date_condition_is_not_eligible_for_schedule(): void
    {
        $conversation = $this->conversation();
        $workflow = $this->workflow([
            'conditions' => [
                ['type' => 'status', 'operator' => 'equal', 'value' => 'pending'],
            ],
        ]);

        $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'customer_reply']));
        $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'schedule']));

        unset($workflow['conditions']);

        $this->assertTrue(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'customer_reply']));
        $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'schedule']));
        $this->assertFalse(WorkflowRunner::eligible($workflow, $conversation, ['name' => 'moved']));
    }

    public function test_moved_keeps_only_a_new_reply_moved_condition_valued_moved(): void
    {
        $conversation = $this->conversation();
        $trigger = ['name' => 'moved'];

        $this->assertFalse(WorkflowRunner::eligible($this->workflow(), $conversation, $trigger));

        $wrong = $this->workflow([
            'conditions' => [
                ['type' => 'new_reply_moved', 'operator' => 'is', 'value' => 'customer_reply'],
            ],
        ]);
        $this->assertFalse(WorkflowRunner::eligible($wrong, $conversation, $trigger));

        $arrayWithout = $this->workflow([
            'conditions' => [
                ['type' => 'new_reply_moved', 'operator' => 'is', 'value' => ['new', 'customer_reply']],
            ],
        ]);
        $this->assertFalse(WorkflowRunner::eligible($arrayWithout, $conversation, $trigger));

        $string = $this->workflow([
            'conditions' => [
                ['type' => 'status', 'operator' => 'equal', 'value' => 'pending'],
                ['type' => 'new_reply_moved', 'operator' => 'is', 'value' => 'moved'],
            ],
        ]);
        $this->assertTrue(WorkflowRunner::eligible($string, $conversation, $trigger));
        $this->assertTrue(WorkflowRunner::eligible($string, $conversation, ['name' => 'customer_reply']));
        $this->assertFalse(WorkflowRunner::eligible($string, $conversation, ['name' => 'schedule']));

        $arrayWith = $this->workflow([
            'conditions' => [
                ['type' => 'new_reply_moved', 'operator' => 'is', 'value' => ['new', 'moved']],
            ],
        ]);
        $this->assertTrue(WorkflowRunner::eligible($arrayWith, $conversation, $trigger));

        $dated = $this->workflow([
            'conditions' => [
                ['type' => 'date_created', 'operator' => 'in_the_last', 'value' => ['number' => 1, 'unit' => 'days']],
                ['type' => 'new_reply_moved', 'operator' => 'is', 'value' => 'moved'],
            ],
        ]);
        $this->assertFalse(WorkflowRunner::eligible($dated, $conversation, $trigger));
    }

    public function test_allows_another_run_only_while_executions_are_below_the_max(): void
    {
        $this->assertFalse(WorkflowRunner::allowsAnotherRun(1, 1));
        $this->assertTrue(WorkflowRunner::allowsAnotherRun(0, 1));
        $this->assertTrue(WorkflowRunner::allowsAnotherRun(2, 5));
        $this->assertFalse(WorkflowRunner::allowsAnotherRun(5, 5));
    }

    public function test_select_orders_by_sort_order_and_drops_strict_running_ids(): void
    {
        $later = $this->workflow([
            'id' => 2,
            'sort_order' => 20,
            'name' => 'later',
            'apply_to_previous' => true,
        ]);
        $running = $this->workflow([
            'id' => 3,
            'sort_order' => 1,
            'name' => 'running',
            'apply_to_previous' => true,
        ]);
        $earlier = $this->workflow([
            'id' => 1,
            'sort_order' => 5,
            'name' => 'earlier',
            'apply_to_previous' => true,
        ]);
        $stringId = $this->workflow([
            'id' => '3',
            'sort_order' => 0,
            'name' => 'string-id',
            'apply_to_previous' => true,
        ]);

        $selected = WorkflowRunner::select(
            [$later, $running, $earlier, $stringId],
            $this->conversation(),
            ['name' => 'customer_reply'],
            [3]
        );

        $this->assertSame([$stringId, $earlier, $later], $selected);
    }

    public function test_select_orders_a_tied_sort_order_by_id(): void
    {
        $second = $this->workflow([
            'id' => 2,
            'sort_order' => 0,
            'apply_to_previous' => true,
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'invoice'],
            ],
        ]);
        $first = $this->workflow([
            'id' => 1,
            'sort_order' => 0,
            'apply_to_previous' => true,
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'invoice'],
            ],
        ]);

        $selected = WorkflowRunner::select(
            [$second, $first],
            $this->conversation(['subject' => 'Invoice please']),
            ['name' => 'customer_reply'],
            []
        );

        $ids = [];
        foreach ($selected as $workflow) {
            $ids[] = $workflow['id'];
        }

        $this->assertSame([1, 2], $ids);
    }

    public function test_select_drops_workflows_that_are_not_eligible(): void
    {
        $workflow = $this->workflow([
            'id' => 9,
            'sort_order' => 1,
            'apply_to_previous' => true,
            'conditions' => [
                ['type' => 'status', 'operator' => 'equal', 'value' => 'pending'],
            ],
        ]);
        $draft = $this->conversation([
            'state' => Conversation::STATE_DRAFT,
            'status' => Conversation::STATUS_PENDING,
        ]);

        $this->assertSame([], WorkflowRunner::select([$workflow], $draft, ['name' => 'customer_reply'], []));
    }

    public function test_select_uses_match_any_or_all_against_condition_evaluator(): void
    {
        $conversation = $this->conversation([
            'status' => Conversation::STATUS_PENDING,
        ]);
        $conditions = [
            ['type' => 'status', 'operator' => 'equal', 'value' => 'pending'],
            ['type' => 'status', 'operator' => 'equal', 'value' => 'closed'],
        ];
        $any = $this->workflow([
            'id' => 1,
            'sort_order' => 20,
            'match' => 'any',
            'name' => 'any',
            'apply_to_previous' => true,
            'conditions' => $conditions,
        ]);
        $all = $this->workflow([
            'id' => 2,
            'sort_order' => 10,
            'match' => 'all',
            'name' => 'all',
            'apply_to_previous' => true,
            'conditions' => $conditions,
        ]);
        $defaultAll = $this->workflow([
            'id' => 3,
            'sort_order' => 30,
            'name' => 'default-all',
            'apply_to_previous' => true,
            'conditions' => $conditions,
        ]);
        $passingAll = $this->workflow([
            'id' => 4,
            'sort_order' => 5,
            'match' => 'all',
            'name' => 'passing-all',
            'apply_to_previous' => true,
            'conditions' => [
                ['type' => 'status', 'operator' => 'equal', 'value' => 'pending'],
            ],
        ]);
        $emptyAll = $this->workflow([
            'id' => 5,
            'sort_order' => 40,
            'name' => 'empty-all',
            'apply_to_previous' => true,
            'conditions' => [],
        ]);
        $emptyAny = $this->workflow([
            'id' => 6,
            'sort_order' => 50,
            'match' => 'any',
            'name' => 'empty-any',
            'apply_to_previous' => true,
            'conditions' => [],
        ]);

        $selected = WorkflowRunner::select(
            [$all, $emptyAny, $any, $defaultAll, $emptyAll, $passingAll],
            $conversation,
            ['name' => 'customer_reply'],
            []
        );

        $this->assertSame([$passingAll, $any, $emptyAll], $selected);
    }

    public function test_select_copies_present_conversation_keys_and_the_trigger_name(): void
    {
        $workflow = $this->workflow([
            'id' => 8,
            'sort_order' => 1,
            'apply_to_previous' => true,
            'match' => 'all',
            'conditions' => [
                ['type' => 'assignee', 'operator' => 'equal', 'value' => '5'],
                ['type' => 'conversation_type', 'operator' => 'equal', 'value' => 'phone'],
                ['type' => 'customer_viewed', 'operator' => 'yes', 'value' => null],
                ['type' => 'channel', 'operator' => 'equal', 'value' => 'chat'],
                ['type' => 'tag', 'operator' => 'equal', 'value' => 'billing'],
                ['type' => 'custom_field', 'operator' => 'equal', 'value' => 'gold'],
                ['type' => 'user_action', 'operator' => 'replied', 'value' => null],
            ],
        ]);
        $conversation = $this->conversation([
            'user_id' => 5,
            'type' => Conversation::TYPE_PHONE,
            'customer_viewed' => true,
            'channel' => 'chat',
            'added_tag' => 'billing',
            'tags' => ['other'],
            'custom_field_value' => 'gold',
        ]);

        $selected = WorkflowRunner::select([$workflow], $conversation, ['name' => 'user_reply'], []);

        $this->assertSame([$workflow], $selected);

        $conversation['channel'] = 'email';

        $this->assertSame([], WorkflowRunner::select([$workflow], $conversation, ['name' => 'user_reply'], []));
    }

    public function test_select_copies_date_fields_for_a_schedule_workflow(): void
    {
        $workflow = $this->workflow([
            'id' => 11,
            'sort_order' => 1,
            'apply_to_previous' => true,
            'match' => 'all',
            'conditions' => [
                [
                    'type' => 'waiting_since',
                    'operator' => 'in_the_last',
                    'value' => ['number' => 1, 'unit' => 'days'],
                ],
            ],
        ]);
        $conversation = $this->conversation([
            'status' => Conversation::STATUS_ACTIVE,
            'now' => '2026-09-29 12:00:00',
            'last_reply_from' => Conversation::PERSON_CUSTOMER,
            'last_reply_from_workflow' => false,
            'last_customer_reply_at' => '2026-09-29 11:00:00',
        ]);

        $this->assertSame(
            [$workflow],
            WorkflowRunner::select([$workflow], $conversation, ['name' => 'schedule'], [])
        );

        $conversation['last_reply_from'] = Conversation::PERSON_USER;

        $this->assertSame(
            [],
            WorkflowRunner::select([$workflow], $conversation, ['name' => 'schedule'], [])
        );
    }

    public function test_stopped_is_true_when_any_result_is_the_string_stop(): void
    {
        $this->assertTrue(WorkflowRunner::stopped(['ok', 'stop', 'later']));
        $this->assertFalse(WorkflowRunner::stopped(['ok', 'later']));
        $this->assertFalse(WorkflowRunner::stopped([]));
        $this->assertFalse(WorkflowRunner::stopped(['STOP']));
    }

    public function test_run_list_runs_the_lower_sort_order_first_and_stops(): void
    {
        $called = [];

        $results = WorkflowRunner::runList([
            ['id' => 20, 'sort_order' => 20],
            ['id' => 5, 'sort_order' => 5],
        ], function ($workflow) use (&$called) {
            $called[] = $workflow['id'];
            if ($workflow['id'] === 5) {
                return 'stop';
            }

            return 'should-not-run';
        });

        $this->assertSame([5], $called);
        $this->assertSame(['stop'], $results);
    }

    public function test_run_list_skips_a_workflow_already_inside_an_executor(): void
    {
        $ran = [];
        $innerResults = null;

        $results = WorkflowRunner::runList([
            ['id' => 2, 'sort_order' => 20],
            ['id' => 1, 'sort_order' => 10],
        ], function ($workflow) use (&$ran, &$innerResults) {
            $ran[] = $workflow['id'];
            if ($workflow['id'] !== 1) {
                return 'later';
            }

            $innerResults = WorkflowRunner::runList([
                ['id' => 1, 'sort_order' => 2],
                ['id' => 3, 'sort_order' => 1],
            ], function ($innerWorkflow) use (&$ran) {
                $ran[] = $innerWorkflow['id'];

                return 'from-'.$innerWorkflow['id'];
            });

            return 'stop';
        });

        $this->assertSame([1, 3], $ran);
        $this->assertSame(['from-3'], $innerResults);
        $this->assertSame(['stop'], $results);

        $again = [];
        $second = WorkflowRunner::runList([
            ['id' => 1, 'sort_order' => 1],
        ], function ($workflow) use (&$again) {
            $again[] = $workflow['id'];

            return 'again';
        });

        $this->assertSame([1], $again);
        $this->assertSame(['again'], $second);
    }

    public function test_run_list_releases_the_running_id_when_the_executor_throws(): void
    {
        $ran = [];
        $threw = false;

        try {
            WorkflowRunner::runList([
                ['id' => 8, 'sort_order' => 2],
                ['id' => 7, 'sort_order' => 1],
            ], function ($workflow) use (&$ran) {
                $ran[] = $workflow['id'];
                if ($workflow['id'] === 7) {
                    throw new \RuntimeException('boom');
                }

                return 'ok';
            });
        } catch (\RuntimeException $e) {
            $threw = $e->getMessage() === 'boom';
        }

        $this->assertTrue($threw);
        $this->assertSame([7], $ran);

        $after = [];
        $results = WorkflowRunner::runList([
            ['id' => 7, 'sort_order' => 2],
            ['id' => 8, 'sort_order' => 1],
        ], function ($workflow) use (&$after) {
            $after[] = $workflow['id'];

            return $workflow['id'];
        });

        $this->assertSame([8, 7], $after);
        $this->assertSame([8, 7], $results);
    }

    /**
     * @param array $overrides
     * @return array
     */
    private function workflow(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'sort_order' => 1,
            'apply_to_previous' => false,
            'created_at' => '2026-09-01 00:00:00',
            'conditions' => [],
        ], $overrides);
    }

    /**
     * @param array $overrides
     * @return array
     */
    private function conversation(array $overrides = []): array
    {
        return array_merge([
            'state' => Conversation::STATE_PUBLISHED,
            'status' => Conversation::STATUS_PENDING,
            'created_at' => '2026-09-15 00:00:00',
        ], $overrides);
    }
}
