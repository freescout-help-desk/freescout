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
