<?php

namespace Modules\Workflows\Services;

use Modules\Workflows\Entities\Workflow;

class WorkflowHealth
{
    /**
     * True when an assign, notification, or move_mailbox action names an id
     * that is not in the existing lists. Conditions are not checked.
     * anybody, nobody, assignee, and last_user are not ids.
     * A missing users or mailboxes key is an empty list.
     *
     * @param array $workflow
     * @param array $existing
     * @return bool
     */
    public static function referencesMissing(array $workflow, array $existing): bool
    {
        $users = self::idSet(isset($existing['users']) ? $existing['users'] : []);
        $mailboxes = self::idSet(isset($existing['mailboxes']) ? $existing['mailboxes'] : []);
        $actions = isset($workflow['actions']) && is_array($workflow['actions']) ? $workflow['actions'] : [];

        foreach ($actions as $action) {
            if (!is_array($action) || !isset($action['type'])) {
                continue;
            }

            $value = array_key_exists('value', $action) ? $action['value'] : null;
            if ($action['type'] === 'assign' && self::assignUserMissing($value, $users)) {
                return true;
            }
            if ($action['type'] === 'notification' && self::idMissing($value, $users)) {
                return true;
            }
            if ($action['type'] === 'move_mailbox' && self::idMissing($value, $mailboxes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set active false on workflows whose action or condition value is this id.
     * kind is user, mailbox, or customer. Compared in PHP, not with SQL LIKE.
     *
     * @param mixed  $id
     * @param string $kind
     * @return void
     */
    public static function deactivateReferencing($id, string $kind): void
    {
        if (self::idKey($id) === null) {
            return;
        }
        if ($kind !== 'user' && $kind !== 'mailbox' && $kind !== 'customer') {
            return;
        }

        $workflows = Workflow::with(['actions', 'conditions'])->get();
        foreach ($workflows as $workflow) {
            if (!self::workflowReferences($workflow, $id, $kind)) {
                continue;
            }

            $workflow->active = false;
            $workflow->save();
        }
    }

    /**
     * @param mixed $value
     * @param array $ids
     * @return bool
     */
    private static function assignUserMissing($value, array $ids): bool
    {
        if (!is_array($value) || !array_key_exists('user_id', $value)) {
            return false;
        }

        return self::idMissing($value['user_id'], $ids);
    }

    /**
     * A non-id is not missing. An id is missing when the list does not contain it.
     *
     * @param mixed $value
     * @param array $ids
     * @return bool
     */
    private static function idMissing($value, array $ids): bool
    {
        $key = self::idKey($value);
        if ($key === null) {
            return false;
        }

        return !isset($ids[$key]);
    }

    /**
     * @param Workflow $workflow
     * @param mixed    $id
     * @param string   $kind
     * @return bool
     */
    private static function workflowReferences(Workflow $workflow, $id, string $kind): bool
    {
        if ($kind === 'user') {
            foreach ($workflow->actions as $action) {
                $value = self::decoded($action->value);
                if ($action->type === 'assign' && is_array($value) && self::sameId(isset($value['user_id']) ? $value['user_id'] : null, $id)) {
                    return true;
                }
                if ($action->type === 'notification' && self::sameId($value, $id)) {
                    return true;
                }
            }
            foreach ($workflow->conditions as $condition) {
                if ($condition->type === 'assignee' && self::sameId(self::decoded($condition->value), $id)) {
                    return true;
                }
            }

            return false;
        }

        if ($kind === 'mailbox') {
            foreach ($workflow->actions as $action) {
                if ($action->type === 'move_mailbox' && self::sameId(self::decoded($action->value), $id)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($workflow->actions as $action) {
            if (self::referencesCustomer(self::decoded($action->value), $id)) {
                return true;
            }
        }
        foreach ($workflow->conditions as $condition) {
            if (self::referencesCustomer(self::decoded($condition->value), $id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The value itself, or customer_id on that array. Not a longer number or an email.
     *
     * @param mixed $value
     * @param mixed $id
     * @return bool
     */
    private static function referencesCustomer($value, $id): bool
    {
        if (self::sameId($value, $id)) {
            return true;
        }
        if (!is_array($value) || !array_key_exists('customer_id', $value)) {
            return false;
        }

        return self::sameId($value['customer_id'], $id);
    }

    /**
     * Eloquent already decodes the json cast. A raw JSON object or array is decoded once.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function decoded($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[' && $trimmed[0] !== '"')) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return $decoded;
    }

    /**
     * @param mixed $value
     * @param mixed $id
     * @return bool
     */
    private static function sameId($value, $id): bool
    {
        $left = self::idKey($value);
        $right = self::idKey($id);

        return $left !== null && $left === $right;
    }

    /**
     * Int 4 and digit-string "4" share a key. "15.5", "", and null do not.
     * Leading zeros are the same number. 12 does not match 123 or 1.
     *
     * @param mixed $value
     * @return string|null
     */
    private static function idKey($value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (!is_string($value) || preg_match('/^[0-9]+$/', $value) !== 1) {
            return null;
        }

        $normalized = ltrim($value, '0');

        return $normalized === '' ? '0' : $normalized;
    }

    /**
     * @param mixed $ids
     * @return array
     */
    private static function idSet($ids): array
    {
        $set = [];
        if (!is_array($ids)) {
            return $set;
        }

        foreach ($ids as $id) {
            $key = self::idKey($id);
            if ($key !== null) {
                $set[$key] = true;
            }
        }

        return $set;
    }
}
