<?php

namespace Modules\Workflows\Tests\Unit;

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
}
