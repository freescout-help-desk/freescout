<?php

namespace Modules\Workflows\Tests\Unit;

use Modules\Workflows\Providers\WorkflowsServiceProvider;
use Modules\Workflows\Services\WorkflowHealth;
use Tests\TestCase;

class WorkflowHealthTest extends TestCase
{
    public function test_assign_user_is_missing_only_when_that_user_is_absent(): void
    {
        $workflow = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 4, 'only_if_available' => true]],
            ],
        ];

        $this->assertFalse(WorkflowHealth::referencesMissing($workflow, ['users' => [4]]));
        $this->assertTrue(WorkflowHealth::referencesMissing($workflow, ['users' => [5]]));
        $this->assertTrue(WorkflowHealth::referencesMissing($workflow, []));
    }

    public function test_assign_anybody_and_nobody_are_not_user_ids(): void
    {
        $anybody = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 'anybody']],
            ],
        ];
        $nobody = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 'nobody']],
            ],
        ];

        $this->assertFalse(WorkflowHealth::referencesMissing($anybody, ['users' => []]));
        $this->assertFalse(WorkflowHealth::referencesMissing($nobody, ['users' => []]));
    }

    public function test_notification_user_is_missing_only_when_absent(): void
    {
        $workflow = [
            'actions' => [
                ['type' => 'notification', 'value' => 7],
            ],
        ];

        $this->assertTrue(WorkflowHealth::referencesMissing($workflow, ['users' => []]));
        $this->assertFalse(WorkflowHealth::referencesMissing($workflow, ['users' => [7]]));
    }

    public function test_notification_assignee_and_last_user_are_not_user_ids(): void
    {
        $assignee = [
            'actions' => [
                ['type' => 'notification', 'value' => 'assignee'],
            ],
        ];
        $lastUser = [
            'actions' => [
                ['type' => 'notification', 'value' => 'last_user'],
            ],
        ];

        $this->assertFalse(WorkflowHealth::referencesMissing($assignee, ['users' => []]));
        $this->assertFalse(WorkflowHealth::referencesMissing($lastUser, ['users' => []]));
    }

    public function test_move_mailbox_is_missing_only_when_that_mailbox_is_absent(): void
    {
        $workflow = [
            'actions' => [
                ['type' => 'move_mailbox', 'value' => 9],
            ],
        ];

        $this->assertTrue(WorkflowHealth::referencesMissing($workflow, ['mailboxes' => []]));
        $this->assertFalse(WorkflowHealth::referencesMissing($workflow, ['mailboxes' => [9]]));
    }

    public function test_string_user_id_matches_the_same_integer(): void
    {
        $stringId = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => '4']],
            ],
        ];
        $intId = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 4]],
            ],
        ];

        $this->assertFalse(WorkflowHealth::referencesMissing($stringId, ['users' => [4]]));
        $this->assertFalse(WorkflowHealth::referencesMissing($intId, ['users' => ['4']]));
        $this->assertTrue(WorkflowHealth::referencesMissing($stringId, ['users' => [5]]));
    }

    public function test_workflow_with_every_referenced_id_present_is_not_missing(): void
    {
        $workflow = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 4, 'only_if_available' => true]],
                ['type' => 'move_mailbox', 'value' => 9],
                ['type' => 'notification', 'value' => 7],
            ],
        ];

        $this->assertFalse(WorkflowHealth::referencesMissing($workflow, [
            'users' => [4, 7],
            'mailboxes' => [9],
        ]));
    }

    public function test_non_ids_do_not_count_and_longer_numbers_are_distinct(): void
    {
        foreach ([null, '', '15.5'] as $userId) {
            $workflow = [
                'actions' => [
                    ['type' => 'assign', 'value' => ['user_id' => $userId]],
                ],
            ];
            $this->assertFalse(WorkflowHealth::referencesMissing($workflow, ['users' => []]));
        }

        $missingUserId = [
            'actions' => [
                ['type' => 'assign', 'value' => ['only_if_available' => true]],
            ],
        ];
        $this->assertFalse(WorkflowHealth::referencesMissing($missingUserId, []));

        $notification = [
            'actions' => [
                ['type' => 'notification', 'value' => '15.5'],
            ],
        ];
        $this->assertFalse(WorkflowHealth::referencesMissing($notification, ['users' => []]));

        $twelve = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 12]],
            ],
        ];
        $this->assertTrue(WorkflowHealth::referencesMissing($twelve, ['users' => [1, 123]]));
        $this->assertFalse(WorkflowHealth::referencesMissing($twelve, ['users' => ['12']]));

        $mailbox = [
            'actions' => [
                ['type' => 'move_mailbox', 'value' => '9'],
            ],
        ];
        $this->assertFalse(WorkflowHealth::referencesMissing($mailbox, ['mailboxes' => [9]]));
        $this->assertTrue(WorkflowHealth::referencesMissing($mailbox, ['mailboxes' => [19, 90]]));

        $conditionOnly = [
            'actions' => [],
            'conditions' => [
                ['type' => 'assignee', 'operator' => 'equal', 'value' => 4],
            ],
        ];
        $this->assertFalse(WorkflowHealth::referencesMissing($conditionOnly, ['users' => []]));
    }

    public function test_one_missing_id_among_present_ids_is_missing(): void
    {
        $workflow = [
            'actions' => [
                ['type' => 'assign', 'value' => ['user_id' => 4, 'only_if_available' => true]],
                ['type' => 'move_mailbox', 'value' => 9],
                ['type' => 'notification', 'value' => 7],
            ],
        ];

        $this->assertTrue(WorkflowHealth::referencesMissing($workflow, [
            'users' => [4, 7],
            'mailboxes' => [8],
        ]));
    }

    public function test_deletion_hooks_are_registered_and_ignore_non_records(): void
    {
        $events = new class {
            public $actions = [];

            public function addFilter($hook, $callback, $priority = 20, $arguments = 1)
            {
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

        WorkflowsServiceProvider::hooks($events);

        $customer = $this->recorded($events->actions, 'customer.deleting');
        $mailbox = $this->recorded($events->actions, 'mailbox.deleted');

        $this->assertNotNull($customer);
        $this->assertSame(1, $customer['arguments']);
        $this->assertNotNull($mailbox);
        $this->assertSame(1, $mailbox['arguments']);

        $plain = new \stdClass();
        foreach ([$customer, $mailbox] as $hook) {
            $this->assertNull(call_user_func($hook['callback'], ['state' => 1]));
            $this->assertNull(call_user_func($hook['callback'], $plain));
        }
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
