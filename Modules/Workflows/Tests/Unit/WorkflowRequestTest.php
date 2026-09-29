<?php

namespace Modules\Workflows\Tests\Unit;

use Modules\Workflows\Http\Controllers\WorkflowsController;
use Modules\Workflows\Http\Requests\WorkflowRequest;
use Tests\TestCase;

class WorkflowRequestTest extends TestCase
{
    public function test_trims_condition_and_action_string_values(): void
    {
        $result = WorkflowRequest::sanitize($this->payload([
            'conditions' => [
                [
                    'type' => '  subject  ',
                    'operator' => '  not_a_real_operator  ',
                    'value' => '  hello  ',
                ],
            ],
            'actions' => [
                ['type' => '  add_note  ', 'value' => '  note  '],
                [
                    'type' => 'email',
                    'value' => [
                        'text' => '  x  ',
                        'count' => 2,
                        'ok' => false,
                        'empty' => null,
                        'nested' => ['  y  ', 3],
                    ],
                ],
                ['type' => 'notify', 'value' => 5],
                ['type' => 'notify', 'value' => false],
                ['type' => 'notify', 'value' => null],
            ],
        ]));

        $this->assertFalse($result['errors']);
        $this->assertSame('Billing', $result['workflow']['name']);
        $this->assertSame('subject', $result['conditions'][0]['type']);
        $this->assertSame('not_a_real_operator', $result['conditions'][0]['operator']);
        $this->assertSame('hello', $result['conditions'][0]['value']);
        $this->assertSame('add_note', $result['actions'][0]['type']);
        $this->assertSame('note', $result['actions'][0]['value']);
        $this->assertSame('x', $result['actions'][1]['value']['text']);
        $this->assertSame(2, $result['actions'][1]['value']['count']);
        $this->assertFalse($result['actions'][1]['value']['ok']);
        $this->assertNull($result['actions'][1]['value']['empty']);
        $this->assertSame(['y', 3], $result['actions'][1]['value']['nested']);
        $this->assertSame(5, $result['actions'][2]['value']);
        $this->assertFalse($result['actions'][3]['value']);
        $this->assertNull($result['actions'][4]['value']);
    }

    public function test_max_executions_below_one_is_an_error_and_one_is_not(): void
    {
        $invalid = WorkflowRequest::sanitize($this->payload([
            'max_executions' => 0,
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'hello'],
            ],
        ]));
        $valid = WorkflowRequest::sanitize($this->payload([
            'max_executions' => 1,
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'hello'],
            ],
        ]));

        $this->assertTrue($invalid['errors']);
        $this->assertSame(0, $invalid['workflow']['max_executions']);
        $this->assertFalse($valid['errors']);
        $this->assertSame(1, $valid['workflow']['max_executions']);
    }

    public function test_max_executions_rejects_non_integers_and_defaults_when_missing(): void
    {
        $missing = $this->payload();
        unset($missing['max_executions']);

        $this->assertFalse(WorkflowRequest::sanitize($missing)['errors']);
        $this->assertSame(1, WorkflowRequest::sanitize($missing)['workflow']['max_executions']);
        $this->assertSame(4, WorkflowRequest::sanitize($this->payload([
            'max_executions' => '4',
        ]))['workflow']['max_executions']);
        $this->assertFalse(WorkflowRequest::sanitize($this->payload([
            'max_executions' => '4',
        ]))['errors']);

        foreach ([0, -3, '0', '-3', '1.5', 'abc', ''] as $value) {
            $result = WorkflowRequest::sanitize($this->payload([
                'max_executions' => $value,
            ]));
            $this->assertTrue($result['errors'], 'Expected an error for '.var_export($value, true));
            $this->assertIsInt($result['workflow']['max_executions']);
        }
    }

    public function test_unknown_condition_type_is_an_error_and_tag_is_not(): void
    {
        $unknown = WorkflowRequest::sanitize($this->payload([
            'conditions' => [
                ['type' => 'made_up', 'operator' => 'contains', 'value' => 'x'],
            ],
        ]));
        $tag = WorkflowRequest::sanitize($this->payload([
            'conditions' => [
                ['type' => 'tag', 'operator' => 'contains', 'value' => 'vip'],
            ],
        ]));
        $customField = WorkflowRequest::sanitize($this->payload([
            'conditions' => [
                ['type' => 'custom_field', 'operator' => 'equal', 'value' => '1'],
            ],
        ]));

        $this->assertTrue($unknown['errors']);
        $this->assertSame('made_up', $unknown['conditions'][0]['type']);
        $this->assertSame('contains', $unknown['conditions'][0]['operator']);
        $this->assertFalse($tag['errors']);
        $this->assertSame('tag', $tag['conditions'][0]['type']);
        $this->assertFalse($customField['errors']);
    }

    public function test_conditions_config_filter_can_add_a_valid_type(): void
    {
        $seenMailbox = null;
        $callback = function ($config, $mailboxId) use (&$seenMailbox) {
            $seenMailbox = $mailboxId;
            $config['dates']['items']['today_is_business_day'] = [
                'title' => 'Today is a business day',
                'operators' => ['yes' => 'Yes', 'no' => 'No'],
                'values' => [],
            ];

            return $config;
        };

        \Eventy::addFilter('workflows.conditions_config', $callback, 20, 2);

        try {
            $result = WorkflowRequest::sanitize($this->payload([
                'conditions' => [
                    ['type' => 'today_is_business_day', 'operator' => 'yes', 'value' => '1'],
                ],
            ]));
        } finally {
            \Eventy::removeFilter('workflows.conditions_config', $callback, 20);
        }

        $this->assertSame(1, $seenMailbox);
        $this->assertFalse($result['errors']);
        $this->assertSame('today_is_business_day', $result['conditions'][0]['type']);
    }

    public function test_validate_action_filter_receives_the_row_and_can_reject(): void
    {
        $received = null;
        $callback = function ($hasError, $action, $workflow) use (&$received) {
            $received = [
                'hasError' => $hasError,
                'action' => $action,
                'workflow' => $workflow,
            ];

            return true;
        };

        \Eventy::addFilter('workflow.validate_action', $callback, 20, 3);

        try {
            $result = WorkflowRequest::sanitize($this->payload([
                'actions' => [
                    ['type' => 'add_note', 'value' => '  note  ', 'run' => '1'],
                ],
            ]));
        } finally {
            \Eventy::removeFilter('workflow.validate_action', $callback, 20);
        }

        $this->assertTrue($result['errors']);
        $this->assertFalse($received['hasError']);
        $this->assertSame('add_note', $received['action']['type']);
        $this->assertSame('note', $received['action']['value']);
        $this->assertArrayNotHasKey('run', $received['action']);
        $this->assertSame('Billing', $received['workflow']['name']);
        $this->assertSame('automatic', $received['workflow']['type']);
        $this->assertArrayNotHasKey('run', $received['workflow']);
    }

    public function test_validate_action_false_does_not_add_an_error(): void
    {
        $callback = function ($hasError, $action, $workflow) {
            return false;
        };

        \Eventy::addFilter('workflow.validate_action', $callback, 20, 3);

        try {
            $result = WorkflowRequest::sanitize($this->payload([
                'actions' => [
                    ['type' => 'not_a_real_action', 'value' => 'keep'],
                ],
            ]));
        } finally {
            \Eventy::removeFilter('workflow.validate_action', $callback, 20);
        }

        $this->assertFalse($result['errors']);
        $this->assertSame('not_a_real_action', $result['actions'][0]['type']);
        $this->assertSame('keep', $result['actions'][0]['value']);
    }

    public function test_sanitized_result_has_no_run_key(): void
    {
        $result = WorkflowRequest::sanitize($this->payload([
            'run' => '1',
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'hello', 'run' => true],
            ],
            'actions' => [
                ['type' => 'add_note', 'value' => 'note', 'run' => 1],
            ],
        ]));

        $this->assertFalse(array_key_exists('run', $result));
        $this->assertFalse(array_key_exists('run', $result['workflow']));
        $this->assertFalse(array_key_exists('run', $result['conditions'][0]));
        $this->assertFalse(array_key_exists('run', $result['actions'][0]));
        $this->assertFalse($result['errors']);
    }

    public function test_valid_payload_does_not_set_errors(): void
    {
        $result = WorkflowRequest::sanitize($this->payload());

        $this->assertFalse($result['errors']);
        $this->assertSame('automatic', $result['workflow']['type']);
        $this->assertTrue($result['workflow']['active']);
        $this->assertFalse($result['workflow']['apply_to_previous']);
        $this->assertSame('all', $result['workflow']['match']);
        $this->assertSame(1, $result['workflow']['max_executions']);
    }

    public function test_empty_conditions_and_actions_are_allowed(): void
    {
        $result = WorkflowRequest::sanitize($this->payload([
            'conditions' => [],
            'actions' => [],
        ]));

        $this->assertFalse($result['errors']);
        $this->assertSame([], $result['conditions']);
        $this->assertSame([], $result['actions']);
    }

    public function test_workflow_type_must_be_automatic_or_manual(): void
    {
        $manual = WorkflowRequest::sanitize($this->payload(['type' => 'manual']));
        $invalid = WorkflowRequest::sanitize($this->payload(['type' => 'made_up']));

        $this->assertFalse($manual['errors']);
        $this->assertSame('manual', $manual['workflow']['type']);
        $this->assertTrue($invalid['errors']);
        $this->assertSame('made_up', $invalid['workflow']['type']);
    }

    public function test_flags_and_match(): void
    {
        $on = WorkflowRequest::sanitize($this->payload([
            'active' => '1',
            'apply_to_previous' => 1,
            'match' => 'any',
        ]));
        $alsoOn = WorkflowRequest::sanitize($this->payload([
            'active' => true,
            'apply_to_previous' => '1',
        ]));
        $off = WorkflowRequest::sanitize($this->payload([
            'active' => '0',
            'apply_to_previous' => 0,
            'match' => 'ALL',
        ]));
        $missing = $this->payload();
        unset($missing['active'], $missing['apply_to_previous'], $missing['match']);
        $missingResult = WorkflowRequest::sanitize($missing);
        $nullFlags = WorkflowRequest::sanitize($this->payload([
            'active' => null,
            'apply_to_previous' => false,
            'match' => ' any ',
        ]));

        $this->assertTrue($on['workflow']['active']);
        $this->assertTrue($on['workflow']['apply_to_previous']);
        $this->assertSame('any', $on['workflow']['match']);
        $this->assertTrue($alsoOn['workflow']['active']);
        $this->assertTrue($alsoOn['workflow']['apply_to_previous']);
        $this->assertFalse($off['workflow']['active']);
        $this->assertFalse($off['workflow']['apply_to_previous']);
        $this->assertSame('all', $off['workflow']['match']);
        $this->assertFalse($missingResult['workflow']['active']);
        $this->assertFalse($missingResult['workflow']['apply_to_previous']);
        $this->assertSame('all', $missingResult['workflow']['match']);
        $this->assertFalse($nullFlags['workflow']['active']);
        $this->assertFalse($nullFlags['workflow']['apply_to_previous']);
        $this->assertSame('all', $nullFlags['workflow']['match']);
        $this->assertFalse(WorkflowRequest::sanitize($this->payload([
            'active' => 'true',
        ]))['workflow']['active']);
    }

    public function test_reordered_swaps_a_neighbor_and_leaves_the_ends_in_place(): void
    {
        $ids = [10, 20, 30];

        $this->assertSame([20, 10, 30], WorkflowsController::reordered($ids, 20, 'up'));
        $this->assertSame([10, 30, 20], WorkflowsController::reordered($ids, 20, 'down'));
        $this->assertSame($ids, WorkflowsController::reordered($ids, 10, 'up'));
        $this->assertSame($ids, WorkflowsController::reordered($ids, 30, 'down'));
        $this->assertSame([20, 10, 30], WorkflowsController::reordered($ids, '20', 'up'));
        $this->assertSame($ids, WorkflowsController::reordered($ids, 20, 'sideways'));
        $this->assertSame($ids, WorkflowsController::reordered($ids, 99, 'up'));
    }

    /**
     * @param array $overrides
     * @return array
     */
    private function payload(array $overrides = []): array
    {
        $payload = [
            'name' => '  Billing  ',
            'type' => 'automatic',
            'active' => true,
            'apply_to_previous' => false,
            'max_executions' => 1,
            'match' => 'all',
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => '  hello  '],
            ],
            'actions' => [
                ['type' => 'add_note', 'value' => '  note  '],
            ],
        ];

        return array_replace($payload, $overrides);
    }
}
