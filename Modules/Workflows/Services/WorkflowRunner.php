<?php

namespace Modules\Workflows\Services;

use App\Conversation;

class WorkflowRunner
{
    /**
     * Date rows run from the schedule trigger only.
     *
     * @var array
     */
    private static $dateTypes = [
        'waiting_since',
        'last_user_reply',
        'last_customer_reply',
        'date_created',
    ];

    /**
     * Conversation keys copied onto the condition context. Nothing else is loaded.
     *
     * @var array
     */
    private static $contextKeys = [
        'status',
        'user_id',
        'type',
        'customer_viewed',
        'channel',
        'added_tag',
        'tags',
        'custom_field_value',
        'now',
        'last_reply_from',
        'last_reply_from_workflow',
        'last_customer_reply_at',
        'last_user_reply_at',
        'created_at',
    ];

    /**
     * Ids currently inside an executor. Nested runList calls skip these.
     *
     * @var array
     */
    private static $running = [];

    /**
     * Drafts never run. Date conditions run only from schedule.
     * A move runs only when new_reply_moved values moved.
     * apply_to_previous must be boolean true. Created-at strings compare as Y-m-d H:i:s.
     *
     * @param array $workflow
     * @param array $conversation
     * @param array $trigger
     * @return bool
     */
    public static function eligible(array $workflow, array $conversation, array $trigger): bool
    {
        if (array_key_exists('state', $conversation) && $conversation['state'] === Conversation::STATE_DRAFT) {
            return false;
        }

        if (!self::appliesToPrevious($workflow) && self::conversationStartedEarlier($conversation, $workflow)) {
            return false;
        }

        $triggerName = self::triggerName($trigger);
        $hasDateCondition = self::hasDateCondition($workflow);

        if ($hasDateCondition && $triggerName !== 'schedule') {
            return false;
        }

        if (!$hasDateCondition && $triggerName === 'schedule') {
            return false;
        }

        if ($triggerName === 'moved' && !self::hasMovedCondition($workflow)) {
            return false;
        }

        return true;
    }

    /**
     * True only while this conversation has had fewer runs than the cap.
     *
     * @param int $executions
     * @param int $max
     * @return bool
     */
    public static function allowsAnotherRun(int $executions, int $max): bool
    {
        return $executions < $max;
    }

    /**
     * Workflows that pass the running-id, eligibility, and condition gates, in sort_order.
     * The original workflow arrays are returned.
     *
     * @param array $workflows
     * @param array $conversation
     * @param array $trigger
     * @param array $runningIds
     * @return array
     */
    public static function select(array $workflows, array $conversation, array $trigger, array $runningIds): array
    {
        self::sortByOrder($workflows);
        $context = self::context($conversation, $trigger);
        $selected = [];

        foreach ($workflows as $workflow) {
            if (!is_array($workflow)) {
                continue;
            }
            if (array_key_exists('id', $workflow) && in_array($workflow['id'], $runningIds, true)) {
                continue;
            }
            if (!self::eligible($workflow, $conversation, $trigger)) {
                continue;
            }
            if (!self::conditionsMatch($workflow, $context)) {
                continue;
            }

            $selected[] = $workflow;
        }

        return $selected;
    }

    /**
     * @param array $actionResults
     * @return bool
     */
    public static function stopped(array $actionResults): bool
    {
        foreach ($actionResults as $result) {
            if ($result === 'stop') {
                return true;
            }
        }

        return false;
    }

    /**
     * Run the given workflows in sort_order. Does not decide eligibility.
     * The same id cannot enter again until its executor returns, including when it throws.
     * A stop result skips the rest of this list.
     *
     * @param array    $workflows
     * @param callable $execute
     * @return array
     */
    public static function runList(array $workflows, callable $execute): array
    {
        self::sortByOrder($workflows);
        $results = [];

        foreach ($workflows as $workflow) {
            $hasId = is_array($workflow) && array_key_exists('id', $workflow);
            $id = $hasId ? $workflow['id'] : null;
            if ($hasId && in_array($id, self::$running, true)) {
                continue;
            }
            if ($hasId) {
                self::$running[] = $id;
            }

            try {
                $result = $execute($workflow);
            } finally {
                if ($hasId) {
                    self::release($id);
                }
            }

            $results[] = $result;
            if ($result === 'stop') {
                break;
            }
        }

        return $results;
    }

    /**
     * Boolean true only. Missing, false, 0, and "0" still apply the created-at rule.
     *
     * @param array $workflow
     * @return bool
     */
    private static function appliesToPrevious(array $workflow): bool
    {
        return array_key_exists('apply_to_previous', $workflow) && $workflow['apply_to_previous'] === true;
    }

    /**
     * Both created_at values must be strings. Equal timestamps are not earlier.
     * Y-m-d H:i:s order matches chronological order, so this does not parse dates.
     *
     * @param array $conversation
     * @param array $workflow
     * @return bool
     */
    private static function conversationStartedEarlier(array $conversation, array $workflow): bool
    {
        $conversationCreated = self::createdAt($conversation);
        $workflowCreated = self::createdAt($workflow);
        if ($conversationCreated === null || $workflowCreated === null) {
            return false;
        }

        return $conversationCreated < $workflowCreated;
    }

    /**
     * @param array $row
     * @return string|null
     */
    private static function createdAt(array $row): ?string
    {
        if (!array_key_exists('created_at', $row) || !is_string($row['created_at'])) {
            return null;
        }

        return $row['created_at'];
    }

    /**
     * @param array $trigger
     * @return string
     */
    private static function triggerName(array $trigger): string
    {
        if (!array_key_exists('name', $trigger) || !is_string($trigger['name'])) {
            return '';
        }

        return $trigger['name'];
    }

    /**
     * @param array $workflow
     * @return bool
     */
    private static function hasDateCondition(array $workflow): bool
    {
        foreach (self::conditions($workflow) as $condition) {
            if (!is_array($condition) || !array_key_exists('type', $condition)) {
                continue;
            }
            if (in_array($condition['type'], self::$dateTypes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Value may be the string moved or a list that contains that string.
     *
     * @param array $workflow
     * @return bool
     */
    private static function hasMovedCondition(array $workflow): bool
    {
        foreach (self::conditions($workflow) as $condition) {
            if (!is_array($condition) || !array_key_exists('type', $condition)) {
                continue;
            }
            if ($condition['type'] !== 'new_reply_moved' || !array_key_exists('value', $condition)) {
                continue;
            }
            if (self::valueIsMoved($condition['value'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private static function valueIsMoved($value): bool
    {
        if ($value === 'moved') {
            return true;
        }

        return is_array($value) && in_array('moved', $value, true);
    }

    /**
     * A missing conditions key is an empty list.
     *
     * @param array $workflow
     * @return array
     */
    private static function conditions(array $workflow): array
    {
        if (!array_key_exists('conditions', $workflow) || !is_array($workflow['conditions'])) {
            return [];
        }

        return $workflow['conditions'];
    }

    /**
     * @param array $workflows
     * @return void
     */
    private static function sortByOrder(array &$workflows): void
    {
        usort($workflows, function ($left, $right) {
            return self::compareSortOrder($left, $right);
        });
    }

    /**
     * @param mixed $left
     * @param mixed $right
     * @return int
     */
    private static function compareSortOrder($left, $right): int
    {
        $leftOrder = self::sortOrder(is_array($left) ? $left : []);
        $rightOrder = self::sortOrder(is_array($right) ? $right : []);
        if ($leftOrder === $rightOrder) {
            return 0;
        }

        return ($leftOrder < $rightOrder) ? -1 : 1;
    }

    /**
     * @param array $workflow
     * @return int
     */
    private static function sortOrder(array $workflow): int
    {
        if (!array_key_exists('sort_order', $workflow) || !is_numeric($workflow['sort_order'])) {
            return 0;
        }

        return (int) $workflow['sort_order'];
    }

    /**
     * @param array $conversation
     * @param array $trigger
     * @return ConditionContext
     */
    private static function context(array $conversation, array $trigger): ConditionContext
    {
        $context = new ConditionContext();
        foreach (self::$contextKeys as $key) {
            if (array_key_exists($key, $conversation)) {
                $context->{$key} = $conversation[$key];
            }
        }
        if (array_key_exists('name', $trigger)) {
            $context->trigger = $trigger['name'];
        }

        return $context;
    }

    /**
     * match any needs one passing row. Anything else, including a missing match, needs every row.
     * An empty list passes all and fails any.
     *
     * @param array            $workflow
     * @param ConditionContext $context
     * @return bool
     */
    private static function conditionsMatch(array $workflow, ConditionContext $context): bool
    {
        $conditions = self::conditions($workflow);
        if (array_key_exists('match', $workflow) && $workflow['match'] === 'any') {
            foreach ($conditions as $condition) {
                if (self::conditionMatches($condition, $context)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($conditions as $condition) {
            if (!self::conditionMatches($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed            $condition
     * @param ConditionContext $context
     * @return bool
     */
    private static function conditionMatches($condition, ConditionContext $context): bool
    {
        if (!is_array($condition)) {
            return false;
        }

        $type = array_key_exists('type', $condition) && is_string($condition['type']) ? $condition['type'] : '';
        $operator = array_key_exists('operator', $condition) && is_string($condition['operator'])
            ? $condition['operator']
            : '';
        $value = array_key_exists('value', $condition) ? $condition['value'] : null;

        return ConditionEvaluator::matches($type, $operator, $value, $context);
    }

    /**
     * @param mixed $id
     * @return void
     */
    private static function release($id): void
    {
        $index = array_search($id, self::$running, true);
        if ($index === false) {
            return;
        }

        unset(self::$running[$index]);
    }
}
