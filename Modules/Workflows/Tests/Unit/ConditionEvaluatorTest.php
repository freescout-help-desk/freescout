<?php

namespace Modules\Workflows\Tests\Unit;

use App\Conversation;
use Modules\Workflows\Services\ConditionContext;
use Modules\Workflows\Services\ConditionEvaluator;
use Tests\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    public function test_contains_is_case_insensitive(): void
    {
        $this->assertTrue(ConditionEvaluator::text('Refund request', 'contains', 'refund'));
        $this->assertFalse(ConditionEvaluator::text('Refund request', 'not_contains', 'refund'));
        $this->assertTrue(ConditionEvaluator::text('Refund request', 'not_contains', 'invoice'));
    }

    public function test_equal_is_case_insensitive(): void
    {
        $this->assertTrue(ConditionEvaluator::text('Refund', 'equal', 'refund'));
        $this->assertFalse(ConditionEvaluator::text('Refund', 'not_equal', 'refund'));
    }

    public function test_invalid_regex_fails_and_is_logged(): void
    {
        $handler = new \Monolog\Handler\TestHandler();
        \Log::getMonolog()->pushHandler($handler);

        try {
            $this->assertFalse(ConditionEvaluator::text('abc', 'regex', '/unterminated'));
            $this->assertTrue($handler->hasErrorThatContains('Invalid Workflow conditions regex: /unterminated'));
            // Closed, but still invalid. The log must keep the caller's pattern, not the i-flag rewrite.
            $this->assertFalse(ConditionEvaluator::text('abc', 'regex', '/+/'));
            $this->assertTrue($handler->hasError('Invalid Workflow conditions regex: /+/'));
        } finally {
            \Log::getMonolog()->popHandler();
        }
    }

    public function test_regex_requires_delimiters(): void
    {
        $this->assertTrue(ConditionEvaluator::text('say tba please', 'regex', '/ tba /'));
    }

    public function test_regex_is_case_insensitive(): void
    {
        $this->assertTrue(ConditionEvaluator::text('TBA', 'regex', '/tba/'));
        $this->assertFalse(ConditionEvaluator::text('TBA', 'regex', '/xyz/'));
    }

    public function test_unknown_operator_returns_false(): void
    {
        $this->assertFalse(ConditionEvaluator::text('Refund', 'starts_with', 'ref'));
    }

    public function test_null_text_is_treated_as_empty(): void
    {
        $this->assertTrue(ConditionEvaluator::text(null, 'equal', ''));
        $this->assertTrue(ConditionEvaluator::text(null, 'equal', null));
        $this->assertFalse(ConditionEvaluator::text(null, 'contains', 'a'));
    }

    public function test_status_equal_pending(): void
    {
        $context = new ConditionContext();
        $context->status = Conversation::STATUS_PENDING;

        $this->assertTrue(ConditionEvaluator::matches('status', 'equal', 'pending', $context));
        $this->assertFalse(ConditionEvaluator::matches('status', 'equal', 'active', $context));
        $this->assertFalse(ConditionEvaluator::matches('status', 'not_equal', 'pending', $context));
    }

    public function test_assignee_nobody_when_user_id_is_null(): void
    {
        $context = new ConditionContext();
        $context->user_id = null;

        $this->assertTrue(ConditionEvaluator::matches('assignee', 'equal', 'nobody', $context));
        $this->assertFalse(ConditionEvaluator::matches('assignee', 'equal', 'anybody', $context));
    }

    public function test_assignee_matches_a_user_id(): void
    {
        $context = new ConditionContext();
        $context->user_id = 5;

        $this->assertTrue(ConditionEvaluator::matches('assignee', 'equal', 'anybody', $context));
        $this->assertFalse(ConditionEvaluator::matches('assignee', 'equal', 4, $context));
        $this->assertTrue(ConditionEvaluator::matches('assignee', 'equal', '5', $context));
    }

    public function test_conversation_type_phone(): void
    {
        $context = new ConditionContext();
        $context->type = Conversation::TYPE_PHONE;

        $this->assertTrue(ConditionEvaluator::matches('conversation_type', 'equal', 'phone', $context));
    }

    public function test_customer_viewed_yes_and_no(): void
    {
        $context = new ConditionContext();
        $context->customer_viewed = true;

        $this->assertTrue(ConditionEvaluator::matches('customer_viewed', 'yes', null, $context));
        $this->assertFalse(ConditionEvaluator::matches('customer_viewed', 'no', null, $context));
    }

    public function test_user_action_matches_only_its_trigger(): void
    {
        $reply = new ConditionContext();
        $reply->trigger = 'user_reply';
        $note = new ConditionContext();
        $note->trigger = 'user_note';

        $this->assertTrue(ConditionEvaluator::matches('user_action', 'replied', null, $reply));
        $this->assertFalse(ConditionEvaluator::matches('user_action', 'replied', null, $note));
        $this->assertTrue(ConditionEvaluator::matches('user_action', 'added_note', null, $note));
        $this->assertFalse(ConditionEvaluator::matches('user_action', 'added_note', null, $reply));
    }

    public function test_new_reply_moved_is_moved_only_for_that_trigger(): void
    {
        $moved = new ConditionContext();
        $moved->trigger = 'moved';
        $reply = new ConditionContext();
        $reply->trigger = 'customer_reply';

        $this->assertTrue(ConditionEvaluator::matches('new_reply_moved', 'is', 'moved', $moved));
        $this->assertFalse(ConditionEvaluator::matches('new_reply_moved', 'is', 'moved', $reply));
    }

    public function test_body_uses_trigger_body_when_the_source_matches(): void
    {
        $context = new ConditionContext();
        $context->trigger_source = 'customer';
        $context->trigger_body = 'Please REFUND this order';
        $context->latest_body_by_source = ['customer' => 'unrelated invoice'];
        $value = ['source' => 'customer', 'text' => 'refund'];

        $this->assertTrue(ConditionEvaluator::matches('body', 'contains', $value, $context));

        $context->trigger_body = 'shipping update';
        $this->assertFalse(ConditionEvaluator::matches('body', 'contains', $value, $context));
    }

    public function test_body_uses_latest_customer_body_when_trigger_source_is_user_or_null(): void
    {
        $value = ['source' => 'customer', 'text' => 'refund'];

        $user = new ConditionContext();
        $user->trigger_source = 'user';
        $user->trigger_body = 'internal note without the word';
        $user->latest_body_by_source = ['customer' => 'Customer asked for a Refund'];

        $this->assertTrue(ConditionEvaluator::matches('body', 'contains', $value, $user));

        $unset = new ConditionContext();
        $unset->trigger_source = null;
        $unset->trigger_body = 'Refund from the trigger should be ignored';
        $unset->latest_body_by_source = ['customer' => 'something else'];

        $this->assertFalse(ConditionEvaluator::matches('body', 'contains', $value, $unset));

        $unset->latest_body_by_source = ['customer' => 'refund please'];
        $this->assertTrue(ConditionEvaluator::matches('body', 'contains', $value, $unset));
    }

    public function test_body_string_value_matches_the_latest_customer_message(): void
    {
        $match = new ConditionContext();
        $match->latest_body_by_source = ['customer' => 'please invoice us'];

        $this->assertTrue(ConditionEvaluator::matches('body', 'contains', 'invoice', $match));

        $other = new ConditionContext();
        $other->latest_body_by_source = ['customer' => 'hello'];

        $this->assertFalse(ConditionEvaluator::matches('body', 'contains', 'invoice', $other));

        $empty = new ConditionContext();
        $empty->latest_body_by_source = ['customer' => ''];

        $this->assertFalse(ConditionEvaluator::matches('body', 'contains', 'invoice', $empty));
    }

    public function test_subject_contains_matches_the_context_property(): void
    {
        $context = new ConditionContext();
        $context->subject = 'Invoice please';

        $this->assertTrue(ConditionEvaluator::matches('subject', 'contains', 'invoice', $context));
    }

    public function test_attachment_contains_when_the_message_has_a_file(): void
    {
        $context = new ConditionContext();
        $context->has_attachment = true;

        $this->assertTrue(ConditionEvaluator::matches('attachment', 'contains', null, $context));
        $this->assertFalse(ConditionEvaluator::matches('attachment', 'not_contains', null, $context));
    }

    public function test_unknown_match_type_or_operator_returns_false(): void
    {
        $context = new ConditionContext();
        $context->status = Conversation::STATUS_PENDING;

        $this->assertFalse(ConditionEvaluator::matches('unknown_type', 'equal', 'pending', $context));
        $this->assertFalse(ConditionEvaluator::matches('status', 'starts_with', 'pending', $context));
    }

    public function test_waiting_since_in_the_last_is_false_when_last_reply_from_is_user(): void
    {
        $context = $this->dateContext();
        $context->last_reply_from = Conversation::PERSON_USER;
        $context->last_reply_from_workflow = false;
        $context->status = Conversation::STATUS_ACTIVE;
        $context->last_customer_reply_at = '2026-09-29 11:00:00';

        $this->assertFalse(ConditionEvaluator::matches(
            'waiting_since',
            'in_the_last',
            ['number' => 1, 'unit' => 'days'],
            $context
        ));
    }

    public function test_waiting_since_in_the_last_is_false_when_status_is_closed(): void
    {
        $context = $this->dateContext();
        $context->last_reply_from = Conversation::PERSON_CUSTOMER;
        $context->last_reply_from_workflow = false;
        $context->status = Conversation::STATUS_CLOSED;
        $context->last_customer_reply_at = '2026-09-29 11:00:00';

        $this->assertFalse(ConditionEvaluator::matches(
            'waiting_since',
            'in_the_last',
            ['number' => 1, 'unit' => 'days'],
            $context
        ));
    }

    public function test_waiting_since_not_in_the_last_is_true_for_an_older_customer_reply(): void
    {
        $context = $this->dateContext();
        $context->last_reply_from = Conversation::PERSON_CUSTOMER;
        $context->last_reply_from_workflow = false;
        $context->status = Conversation::STATUS_ACTIVE;
        $context->last_customer_reply_at = '2026-09-27 12:00:00';

        $this->assertTrue(ConditionEvaluator::matches(
            'waiting_since',
            'not_in_the_last',
            ['number' => 1, 'unit' => 'days'],
            $context
        ));
    }

    public function test_waiting_since_is_false_when_last_reply_from_workflow(): void
    {
        $context = $this->dateContext();
        $context->last_reply_from = Conversation::PERSON_CUSTOMER;
        $context->last_reply_from_workflow = true;
        $context->status = Conversation::STATUS_ACTIVE;
        $context->last_customer_reply_at = '2026-09-27 12:00:00';

        $this->assertFalse(ConditionEvaluator::matches(
            'waiting_since',
            'not_in_the_last',
            ['number' => 1, 'unit' => 'days'],
            $context
        ));
    }

    public function test_last_customer_reply_not_in_the_last_is_false_when_timestamp_is_null(): void
    {
        $context = $this->dateContext();
        $context->last_customer_reply_at = null;

        $this->assertFalse(ConditionEvaluator::matches(
            'last_customer_reply',
            'not_in_the_last',
            ['number' => 1, 'unit' => 'days'],
            $context
        ));
    }

    public function test_date_created_in_the_last_two_hours(): void
    {
        $context = $this->dateContext();
        $context->created_at = '2026-09-29 11:00:00';

        $this->assertTrue(ConditionEvaluator::matches(
            'date_created',
            'in_the_last',
            ['number' => 2, 'unit' => 'hours'],
            $context
        ));
    }

    public function test_date_created_minutes_unit_returns_false(): void
    {
        $context = $this->dateContext();
        $context->created_at = '2026-09-29 11:30:00';

        $this->assertFalse(ConditionEvaluator::matches(
            'date_created',
            'in_the_last',
            ['number' => 60, 'unit' => 'minutes'],
            $context
        ));
    }

    public function test_last_user_reply_in_the_last_hour_has_no_status_guard(): void
    {
        $context = $this->dateContext();
        $context->last_user_reply_at = '2026-09-29 11:30:00';
        $context->status = Conversation::STATUS_CLOSED;
        $context->last_reply_from = Conversation::PERSON_USER;

        $this->assertTrue(ConditionEvaluator::matches(
            'last_user_reply',
            'in_the_last',
            ['number' => 1, 'unit' => 'hours'],
            $context
        ));
    }

    public function test_tag_contains_uses_added_tag_and_ignores_the_tag_list(): void
    {
        $context = new ConditionContext();
        $context->added_tag = 'refund';
        $context->tags = ['other'];

        $this->assertTrue(ConditionEvaluator::matches('tag', 'contains', 'refund', $context));
        $this->assertFalse(ConditionEvaluator::matches('tag', 'contains', 'other', $context));
        $this->assertFalse(ConditionEvaluator::matches('tag', 'equal', 'other', $context));
        $this->assertTrue(ConditionEvaluator::matches('tag', 'not_contains', 'other', $context));
    }

    public function test_tag_equal_uses_tags_when_added_tag_is_null(): void
    {
        $context = new ConditionContext();
        $context->added_tag = null;
        $context->tags = ['refund', 'vip'];

        $this->assertTrue(ConditionEvaluator::matches('tag', 'equal', 'vip', $context));
        $this->assertTrue(ConditionEvaluator::matches('tag', 'equal', 'VIP', $context));
        $this->assertTrue(ConditionEvaluator::matches('tag', 'contains', 'fund', $context));
        $this->assertFalse(ConditionEvaluator::matches('tag', 'equal', 'other', $context));
        $this->assertTrue(ConditionEvaluator::matches('tag', 'not_equal', 'other', $context));
        $this->assertFalse(ConditionEvaluator::matches('tag', 'not_contains', 'fund', $context));
    }

    public function test_tag_not_operators_pass_when_no_candidate_matches(): void
    {
        $context = new ConditionContext();
        $context->added_tag = null;
        $context->tags = [];

        $this->assertFalse(ConditionEvaluator::matches('tag', 'contains', 'refund', $context));
        $this->assertFalse(ConditionEvaluator::matches('tag', 'equal', 'refund', $context));
        $this->assertTrue(ConditionEvaluator::matches('tag', 'not_contains', 'refund', $context));
        $this->assertTrue(ConditionEvaluator::matches('tag', 'not_equal', 'refund', $context));
        $this->assertFalse(ConditionEvaluator::matches('tag', 'regex', 'refund', $context));
    }

    public function test_channel_equal_telegram(): void
    {
        $context = new ConditionContext();
        $context->channel = 'telegram';

        $this->assertTrue(ConditionEvaluator::matches('channel', 'equal', 'telegram', $context));
        $this->assertFalse(ConditionEvaluator::matches('channel', 'not_equal', 'telegram', $context));
        $this->assertFalse(ConditionEvaluator::matches('channel', 'contains', 'telegram', $context));

        $context->channel = null;
        $this->assertFalse(ConditionEvaluator::matches('channel', 'equal', 'telegram', $context));
    }

    public function test_custom_field_is_set_and_is_not_set(): void
    {
        $set = new ConditionContext();
        $set->custom_field_value = 'sku-1';

        $this->assertTrue(ConditionEvaluator::matches('custom_field', 'is_set', null, $set));
        $this->assertFalse(ConditionEvaluator::matches('custom_field', 'is_not_set', null, $set));
        $this->assertTrue(ConditionEvaluator::matches('custom_field', 'equal', 'sku-1', $set));
        $this->assertTrue(ConditionEvaluator::matches('custom_field', 'contains', 'sku', $set));
        $this->assertFalse(ConditionEvaluator::matches('custom_field', 'not_equal', 'sku-1', $set));
        $this->assertFalse(ConditionEvaluator::matches('custom_field', 'not_contains', 'sku', $set));

        $unset = new ConditionContext();
        $unset->custom_field_value = null;

        $this->assertTrue(ConditionEvaluator::matches('custom_field', 'is_not_set', null, $unset));
        $this->assertFalse(ConditionEvaluator::matches('custom_field', 'is_set', null, $unset));

        $empty = new ConditionContext();
        $empty->custom_field_value = '';

        $this->assertTrue(ConditionEvaluator::matches('custom_field', 'is_not_set', null, $empty));
        $this->assertFalse(ConditionEvaluator::matches('custom_field', 'is_set', null, $empty));
        $this->assertFalse(ConditionEvaluator::matches('custom_field', 'regex', 'sku', $set));
    }

    public function test_custom_field_equal_reads_the_field_filter(): void
    {
        $conversation = new \stdClass();
        $callback = function ($current, $fieldId, $seenConversation) use ($conversation) {
            if ($fieldId === 4 && $seenConversation === $conversation) {
                return 'gold';
            }

            return $current;
        };
        \Eventy::addFilter('workflow.custom_field_value', $callback, 20, 3);

        try {
            $context = new ConditionContext();
            $context->conversation = $conversation;

            $this->assertTrue(ConditionEvaluator::matches(
                'custom_field',
                'equal',
                ['field_id' => 4, 'text' => 'gold'],
                $context
            ));
        } finally {
            \Eventy::removeFilter('workflow.custom_field_value', $callback, 20);
        }
    }

    public function test_check_condition_filter_runs_after_the_builtin_result(): void
    {
        $seen = [];
        $callback = function ($result, $type, $operator, $value, $conversation, $workflow) use (&$seen) {
            $seen[] = [$type, $result, $conversation, $workflow];
            if ($type === 'today_is_business_day') {
                return true;
            }

            return $result;
        };
        \Eventy::addFilter('workflow.check_condition', $callback, 20, 6);

        try {
            $context = new ConditionContext();
            $context->channel = 'telegram';

            $this->assertTrue(ConditionEvaluator::matches('channel', 'equal', 'telegram', $context));
            $this->assertTrue(ConditionEvaluator::matches('today_is_business_day', 'yes', null, $context));
            $this->assertSame([
                ['channel', true, null, null],
                ['today_is_business_day', false, null, null],
            ], $seen);
        } finally {
            \Eventy::removeFilter('workflow.check_condition', $callback, 20);
        }
    }

    /**
     * @return ConditionContext
     */
    private function dateContext(): ConditionContext
    {
        $context = new ConditionContext();
        $context->now = '2026-09-29 12:00:00';

        return $context;
    }
}
