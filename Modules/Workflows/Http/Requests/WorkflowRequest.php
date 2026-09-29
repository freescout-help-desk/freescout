<?php

namespace Modules\Workflows\Http\Requests;

use Modules\Workflows\Services\ConditionCatalog;

class WorkflowRequest
{
    /**
     * Normalize editor input. Does not run the workflow.
     *
     * @param array $input
     * @return array
     */
    public static function sanitize(array $input): array
    {
        $errors = false;
        $workflow = self::workflow($input, $errors);
        $conditions = self::conditions(self::rows($input, 'conditions'), $errors);
        $actions = self::actions(self::rows($input, 'actions'), $workflow, $errors);

        return [
            'workflow' => $workflow,
            'conditions' => $conditions,
            'actions' => $actions,
            'errors' => $errors,
        ];
    }

    /**
     * @param array $input
     * @param bool  $errors
     * @return array
     */
    private static function workflow(array $input, &$errors): array
    {
        $type = self::trimmedString(self::value($input, 'type'));
        if ($type !== 'automatic' && $type !== 'manual') {
            $errors = true;
        }

        $match = 'all';
        if (self::value($input, 'match') === 'any') {
            $match = 'any';
        }

        return [
            'name' => self::trimmedString(self::value($input, 'name')),
            'type' => $type,
            'active' => self::flag(self::value($input, 'active')),
            'apply_to_previous' => self::flag(self::value($input, 'apply_to_previous')),
            'max_executions' => self::maxExecutions($input, $errors),
            'match' => $match,
        ];
    }

    /**
     * Missing is 1. Integers and digit-strings below 1, and anything else
     * that is not an integer of 1 or more, are errors.
     *
     * @param array $input
     * @param bool  $errors
     * @return int
     */
    private static function maxExecutions(array $input, &$errors): int
    {
        if (!array_key_exists('max_executions', $input)) {
            return 1;
        }

        $value = $input['max_executions'];
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
                $errors = true;

                return 1;
            }
            $value = (int) $value;
        }

        if (!is_int($value)) {
            $errors = true;

            return 1;
        }

        if ($value < 1) {
            $errors = true;
        }

        return $value;
    }

    /**
     * @param mixed $rows
     * @param bool  $errors
     * @return array
     */
    private static function conditions($rows, &$errors): array
    {
        if (!is_array($rows)) {
            $errors = true;

            return [];
        }

        $catalog = self::catalogTypes();
        $clean = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $errors = true;
                continue;
            }

            $type = self::trimmedString(self::value($row, 'type'));
            if (!isset($catalog[$type])) {
                $errors = true;
            }

            $clean[] = [
                'type' => $type,
                'operator' => self::trimmedString(self::value($row, 'operator')),
                'value' => self::rowValue($row),
            ];
        }

        return $clean;
    }

    /**
     * Unknown action types are left for workflow.validate_action.
     *
     * @param mixed $rows
     * @param array $workflow
     * @param bool  $errors
     * @return array
     */
    private static function actions($rows, array $workflow, &$errors): array
    {
        if (!is_array($rows)) {
            $errors = true;

            return [];
        }

        $clean = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $errors = true;
                continue;
            }

            $action = [
                'type' => self::trimmedString(self::value($row, 'type')),
                'value' => self::rowValue($row),
            ];

            if (\Eventy::filter('workflow.validate_action', false, $action, $workflow) === true) {
                $errors = true;
            }

            $clean[] = $action;
        }

        return $clean;
    }

    /**
     * Item keys from the catalog, including types added by the conditions filter.
     * Flags keep this off Module::isActive.
     *
     * @return array
     */
    private static function catalogTypes(): array
    {
        $config = ConditionCatalog::configured(1, ['tags' => true, 'customfields' => true]);
        $types = [];

        foreach ($config as $group) {
            if (!is_array($group) || !isset($group['items']) || !is_array($group['items'])) {
                continue;
            }

            foreach ($group['items'] as $type => $item) {
                $types[(string) $type] = true;
            }
        }

        return $types;
    }

    /**
     * @param array  $input
     * @param string $key
     * @return mixed
     */
    private static function rows(array $input, string $key)
    {
        if (!array_key_exists($key, $input)) {
            return [];
        }

        return $input[$key];
    }

    /**
     * @param array  $row
     * @param string $key
     * @return mixed
     */
    private static function value(array $row, string $key)
    {
        if (!array_key_exists($key, $row)) {
            return null;
        }

        return $row[$key];
    }

    /**
     * @param array $row
     * @return mixed
     */
    private static function rowValue(array $row)
    {
        if (!array_key_exists('value', $row)) {
            return null;
        }

        return self::trimDeep($row['value']);
    }

    /**
     * True only for true, integer 1, and string "1".
     *
     * @param mixed $value
     * @return bool
     */
    private static function flag($value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function trimmedString($value): string
    {
        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    /**
     * Trim strings. Leave ints, bools, and null alone.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function trimDeep($value)
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        $trimmed = [];
        foreach ($value as $key => $item) {
            $trimmed[$key] = self::trimDeep($item);
        }

        return $trimmed;
    }
}
