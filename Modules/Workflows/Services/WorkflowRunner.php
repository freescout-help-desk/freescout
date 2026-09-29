<?php

namespace Modules\Workflows\Services;

use App\Conversation;
use App\Thread;
use Modules\Workflows\Entities\ConversationWorkflow;
use Modules\Workflows\Entities\Workflow;

class WorkflowRunner
{
    /**
     * Date rows run from schedule and from manual.
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
        'subject',
        'customer_name',
        'customer_email',
        'to',
        'cc',
        'headers',
        'added_tag',
        'tags',
        'custom_field_value',
        'now',
        'last_reply_from',
        'last_reply_from_workflow',
        'last_customer_reply_at',
        'last_user_reply_at',
        'created_at',
        'trigger_source',
        'trigger_body',
        'latest_body_by_source',
        'has_attachment',
    ];

    /**
     * Ids currently inside an executor. Nested runList calls skip these.
     *
     * @var array
     */
    private static $running = [];

    /**
     * Drafts never run. Date rows run from schedule and from manual.
     * A move runs only when new_reply_moved values moved.
     * apply_to_previous true, 1, or "1" includes older conversations. Created-at strings compare as Y-m-d H:i:s.
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

        if ($hasDateCondition && $triggerName !== 'schedule' && $triggerName !== 'manual') {
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
     * Load this mailbox's active automatic workflows and run the ones select() keeps.
     * Dates are Y-m-d H:i:s strings. Carbon objects fail the created-at check.
     * Workflow ids are not cast, so the running-id comparison stays strict.
     *
     * @param mixed $conversation
     * @param array $trigger
     * @param mixed $thread
     * @return void
     */
    public static function runMailbox($conversation, array $trigger, $thread = null): void
    {
        if (!is_object($conversation)) {
            return;
        }

        $models = self::automaticWorkflows($conversation);
        $workflows = [];
        foreach ($models as $model) {
            $workflows[] = self::workflowArray($model);
        }

        $selected = self::select($workflows, self::conversationArray($conversation, $thread, $trigger), $trigger, []);
        if ($selected === []) {
            return;
        }

        $workflowUser = WorkflowUser::findOrCreate();
        self::runList($selected, function ($workflow) use ($conversation, $models, $workflowUser) {
            return self::executeSelected($conversation, $workflow, $models, $workflowUser);
        });
    }

    /**
     * One workflow chosen by the caller. A non-object returns before any query.
     * select() gets the manual trigger, then runList and executeSelected.
     *
     * @param mixed $conversation
     * @param mixed $workflow
     * @param array $trigger
     * @return void
     */
    public function runOne($conversation, $workflow, array $trigger = []): void
    {
        if (!is_object($conversation) || !is_object($workflow)) {
            return;
        }

        if (!array_key_exists('name', $trigger) || !is_string($trigger['name']) || $trigger['name'] === '') {
            $trigger = ['name' => 'manual'];
        }

        $selected = self::select(
            [self::workflowArray($workflow)],
            self::conversationArray($conversation, null, $trigger),
            $trigger,
            []
        );
        if ($selected === []) {
            return;
        }

        $workflowUser = WorkflowUser::findOrCreate();
        self::runList($selected, function ($row) use ($conversation, $workflow, $workflowUser) {
            return self::executeSelected($conversation, $row, [$workflow], $workflowUser);
        });
    }

    /**
     * Enabled for boolean true, integer 1, and string "1".
     * Missing, null, false, 0, and "0" still apply the created-at rule.
     *
     * @param array $workflow
     * @return bool
     */
    private static function appliesToPrevious(array $workflow): bool
    {
        if (!array_key_exists('apply_to_previous', $workflow)) {
            return false;
        }

        $flag = $workflow['apply_to_previous'];

        return $flag === true || $flag === 1 || $flag === '1';
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
     * Equal sort orders use the numeric id, lower first. A non-numeric id is 0.
     *
     * @param mixed $left
     * @param mixed $right
     * @return int
     */
    private static function compareSortOrder($left, $right): int
    {
        $leftOrder = self::sortOrder(is_array($left) ? $left : []);
        $rightOrder = self::sortOrder(is_array($right) ? $right : []);
        if ($leftOrder !== $rightOrder) {
            return ($leftOrder < $rightOrder) ? -1 : 1;
        }

        $leftId = self::numericId(is_array($left) ? $left : []);
        $rightId = self::numericId(is_array($right) ? $right : []);
        if ($leftId === $rightId) {
            return 0;
        }

        return ($leftId < $rightId) ? -1 : 1;
    }

    /**
     * @param array $workflow
     * @return int
     */
    private static function numericId(array $workflow): int
    {
        if (!array_key_exists('id', $workflow) || !is_numeric($workflow['id'])) {
            return 0;
        }

        return (int) $workflow['id'];
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
        if (array_key_exists('conversation', $conversation) && is_object($conversation['conversation'])) {
            $context->conversation = $conversation['conversation'];
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

    /**
     * @param object $conversation
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function automaticWorkflows($conversation)
    {
        return Workflow::query()
            ->where('mailbox_id', $conversation->mailbox_id)
            ->where('active', 1)
            ->where('type', 'automatic')
            ->with(['conditions', 'actions'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Plain array select() already expects. id is left as stored.
     * match is a reserved word, so it is read with getAttribute.
     *
     * @param Workflow $model
     * @return array
     */
    private static function workflowArray($model): array
    {
        $workflow = [
            'id' => $model->getAttribute('id'),
            'sort_order' => $model->getAttribute('sort_order'),
            'apply_to_previous' => $model->getAttribute('apply_to_previous'),
            'match' => $model->getAttribute('match'),
            'conditions' => self::ruleRows($model->conditions),
            'actions' => self::ruleRows($model->actions),
            'max_executions' => $model->getAttribute('max_executions'),
        ];

        $createdAt = self::formatDate($model->getAttribute('created_at'));
        if ($createdAt !== null) {
            $workflow['created_at'] = $createdAt;
        }

        return $workflow;
    }

    /**
     * @param mixed $rows
     * @return array
     */
    private static function ruleRows($rows): array
    {
        $result = [];
        if ($rows === null) {
            return $result;
        }

        foreach ($rows as $row) {
            $result[] = [
                'type' => $row->type,
                'operator' => $row->operator,
                'value' => $row->value,
            ];
        }

        return $result;
    }

    /**
     * PDO integer columns arrive as strings. Status and reply-from checks are strict.
     * Latest published threads fill bodies and dates when the trigger has no thread.
     *
     * @param object $conversation
     * @param mixed  $thread
     * @param array  $trigger
     * @return array
     */
    private static function conversationArray($conversation, $thread, array $trigger = []): array
    {
        $latest = [
            'customer' => self::lastThread($conversation, Thread::TYPE_CUSTOMER),
            'user' => self::lastThread($conversation, Thread::TYPE_MESSAGE),
            'note' => self::lastThread($conversation, Thread::TYPE_NOTE),
        ];
        $bodies = [];
        foreach ($latest as $source => $row) {
            if (!is_object($row)) {
                continue;
            }
            $text = self::threadText($row);
            if ($text !== null) {
                $bodies[$source] = $text;
            }
        }

        $data = [
            'state' => self::integerColumn(self::readAttribute($conversation, 'state')),
            'status' => self::integerColumn(self::readAttribute($conversation, 'status')),
            'user_id' => self::integerColumn(self::readAttribute($conversation, 'user_id')),
            'type' => self::integerColumn(self::readAttribute($conversation, 'type')),
            'last_reply_from' => self::integerColumn(self::readAttribute($conversation, 'last_reply_from')),
            'now' => date('Y-m-d H:i:s'),
            'customer_viewed' => self::customerViewed($thread, $latest['user']),
            'tags' => self::tagNames($conversation, $trigger),
            'channel' => \Eventy::filter('workflow.conversation_channel', 'email', $conversation),
            'has_attachment' => self::contextHasAttachment($thread, $latest),
            'subject' => self::stringAttribute($conversation, 'subject'),
            'customer_email' => self::stringAttribute($conversation, 'customer_email'),
            'customer_name' => self::customerName($conversation),
            'conversation' => $conversation,
        ];

        $createdAt = self::formatDate(self::readAttribute($conversation, 'created_at'));
        if ($createdAt !== null) {
            $data['created_at'] = $createdAt;
        }

        if (is_object($conversation) && method_exists($conversation, 'getLastCustomerReplyAt')) {
            $lastCustomer = $conversation->getLastCustomerReplyAt();
            $formatted = self::formatDate($lastCustomer);
            if ($lastCustomer !== null && $lastCustomer !== '' && $formatted !== null) {
                $data['last_customer_reply_at'] = $formatted;
            }
        }

        $userReplyAt = self::formatDate(self::readAttribute($latest['user'], 'created_at'));
        if ($userReplyAt !== null) {
            $data['last_user_reply_at'] = $userReplyAt;
        }

        if (is_object($thread) && method_exists($thread, 'getBodyAsText')) {
            $text = self::threadText($thread);
            if ($text !== null) {
                $data['trigger_body'] = $text;
            }
            $source = self::triggerSource($thread);
            if ($source !== null) {
                $data['trigger_source'] = $source;
                if ($text !== null && !array_key_exists($source, $bodies)) {
                    $bodies[$source] = $text;
                }
            }
        }

        $data['latest_body_by_source'] = $bodies;

        $message = is_object($thread) ? $thread : $latest['customer'];
        $data['to'] = self::stringAttribute($message, 'to');
        $data['cc'] = self::stringAttribute($message, 'cc');
        $data['headers'] = self::stringAttribute($message, 'headers');

        if (array_key_exists('added_tag', $trigger) && $trigger['added_tag'] !== null) {
            $data['added_tag'] = $trigger['added_tag'];
        }

        return $data;
    }

    /**
     * Count the run before its actions. stop ends this workflow and later ones.
     *
     * @param object $conversation
     * @param array  $workflow
     * @param mixed  $models
     * @param mixed  $workflowUser
     * @return string|null
     */
    private static function executeSelected($conversation, array $workflow, $models, $workflowUser)
    {
        if (!array_key_exists('id', $workflow)) {
            return null;
        }

        $model = self::workflowModel($models, $workflow['id']);
        if ($model === null) {
            return null;
        }

        $record = ConversationWorkflow::where('conversation_id', $conversation->id)
            ->where('workflow_id', $workflow['id'])
            ->first();
        $executions = $record === null ? 0 : (int) $record->executions;
        $max = array_key_exists('max_executions', $workflow) ? (int) $workflow['max_executions'] : 0;
        if (!self::allowsAnotherRun($executions, $max)) {
            return null;
        }

        if ($record === null) {
            $record = new ConversationWorkflow();
            $record->conversation_id = $conversation->id;
            $record->workflow_id = $workflow['id'];
        }
        $record->executions = $executions + 1;
        $record->save();

        $context = new \stdClass();
        $context->conversation = $conversation;
        $context->workflowUser = $workflowUser;
        $context->workflow = $model;

        $actions = [];
        if (array_key_exists('actions', $workflow) && is_array($workflow['actions'])) {
            $actions = $workflow['actions'];
        }
        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }
            $type = array_key_exists('type', $action) ? $action['type'] : '';
            $value = array_key_exists('value', $action) ? $action['value'] : null;
            $result = ActionRunner::perform($type, $value, $context);
            if ($result === 'stop') {
                return 'stop';
            }
        }

        return null;
    }

    /**
     * @param mixed $models
     * @param mixed $id
     * @return Workflow|null
     */
    private static function workflowModel($models, $id)
    {
        foreach ($models as $model) {
            if ($model->getAttribute('id') === $id) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @param mixed  $model
     * @param string $name
     * @return string
     */
    private static function stringAttribute($model, string $name): string
    {
        try {
            $value = self::readAttribute($model, $name);
        } catch (\Throwable $e) {
            return '';
        }

        return is_string($value) ? $value : '';
    }

    /**
     * @param mixed $conversation
     * @return string
     */
    private static function customerName($conversation): string
    {
        try {
            $customer = self::readAttribute($conversation, 'customer');
            if (!is_object($customer) || !method_exists($customer, 'getFullName')) {
                return '';
            }

            $name = $customer->getFullName();
        } catch (\Throwable $e) {
            return '';
        }

        return is_string($name) ? $name : '';
    }

    /**
     * @param mixed  $model
     * @param string $name
     * @return mixed
     */
    private static function readAttribute($model, $name)
    {
        if (is_array($model) && array_key_exists($name, $model)) {
            return $model[$name];
        }
        if (!is_object($model)) {
            return null;
        }
        if (method_exists($model, 'getAttribute')) {
            return $model->getAttribute($name);
        }
        if (property_exists($model, $name)) {
            return $model->{$name};
        }

        return null;
    }

    /**
     * Whole-number strings become ints. Anything else is unchanged, including null.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function integerColumn($value)
    {
        if (is_int($value) || $value === null) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function formatDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    /**
     * A message the customer has opened. The triggering message wins.
     * Any other trigger uses the latest outbound message. A throw is unviewed.
     *
     * @param mixed $thread
     * @param mixed $latestMessage
     * @return bool
     */
    private static function customerViewed($thread, $latestMessage): bool
    {
        if (is_object($thread) && self::readAttribute($thread, 'type') == Thread::TYPE_MESSAGE) {
            return self::filled(self::readAttribute($thread, 'opened_at'));
        }

        if (!is_object($latestMessage)) {
            return false;
        }

        return self::filled(self::readAttribute($latestMessage, 'opened_at'));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private static function filled($value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        return !empty($value);
    }

    /**
     * Tag names from workflow.conversation_tags. A non-array result is an empty list.
     *
     * @param object $conversation
     * @param array  $trigger
     * @return array
     */
    private static function tagNames($conversation, array $trigger): array
    {
        $tags = \Eventy::filter('workflow.conversation_tags', [], $conversation, $trigger);
        if (!is_array($tags)) {
            return [];
        }

        $names = [];
        foreach ($tags as $tag) {
            $name = self::tagName($tag);
            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param mixed $tag
     * @return string|null
     */
    private static function tagName($tag): ?string
    {
        if (is_string($tag)) {
            return $tag;
        }
        if (is_array($tag) && isset($tag['name']) && is_string($tag['name'])) {
            return $tag['name'];
        }
        if (is_object($tag) && isset($tag->name) && is_string($tag->name)) {
            return $tag->name;
        }

        return null;
    }

    /**
     * Latest published thread of one type. A missing method or a throw is no thread.
     *
     * @param mixed $conversation
     * @param int   $type
     * @return object|null
     */
    private static function lastThread($conversation, $type)
    {
        if (!is_object($conversation) || !method_exists($conversation, 'getLastThread')) {
            return null;
        }

        try {
            $thread = $conversation->getLastThread([$type]);
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_object($thread)) {
            return null;
        }

        return $thread;
    }

    /**
     * A triggering thread decides by itself. With no thread, any latest
     * customer, user, or note attachment counts.
     *
     * @param mixed $thread
     * @param array $latest
     * @return bool
     */
    private static function contextHasAttachment($thread, array $latest): bool
    {
        if (is_object($thread)) {
            return self::threadHasAttachment($thread);
        }

        foreach ($latest as $row) {
            if (self::threadHasAttachment($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $thread
     * @return bool
     */
    private static function threadHasAttachment($thread): bool
    {
        if (!is_object($thread) || !method_exists($thread, 'attachments')) {
            return false;
        }

        try {
            $attachments = $thread->attachments;
        } catch (\Throwable $e) {
            return false;
        }

        if ($attachments === null) {
            return false;
        }
        if (is_object($attachments) && method_exists($attachments, 'isEmpty')) {
            return !$attachments->isEmpty();
        }
        if (is_countable($attachments)) {
            return count($attachments) > 0;
        }

        return !empty($attachments);
    }

    /**
     * htmlToText warns on a null body. That must not abort the run.
     *
     * @param object $thread
     * @return string|null
     */
    private static function threadText($thread): ?string
    {
        try {
            $text = $thread->getBodyAsText();
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_string($text)) {
            return null;
        }

        return $text;
    }

    /**
     * @param object $thread
     * @return string|null
     */
    private static function triggerSource($thread): ?string
    {
        $type = self::readAttribute($thread, 'type');
        if ($type == Thread::TYPE_CUSTOMER) {
            return 'customer';
        }
        if ($type == Thread::TYPE_MESSAGE) {
            return 'user';
        }
        if ($type == Thread::TYPE_NOTE) {
            return 'note';
        }

        return null;
    }
}
