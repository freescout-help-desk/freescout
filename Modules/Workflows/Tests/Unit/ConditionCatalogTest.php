<?php

namespace Modules\Workflows\Tests\Unit;

use Modules\Workflows\Services\ConditionCatalog;
use Tests\TestCase;

class ConditionCatalogTest extends TestCase
{
    public function test_builtin_groups_include_the_documented_types(): void
    {
        $config = ConditionCatalog::configured(1);

        $this->assertArrayHasKey('customer_name', $config['customer']['items']);
        $this->assertArrayHasKey('waiting_since', $config['dates']['items']);
        $this->assertArrayHasKey('body', $config['message']['items']);
        $this->assertSame(
            ['contains', 'not_contains', 'equal', 'not_equal', 'regex'],
            array_keys($config['customer']['items']['customer_name']['operators'])
        );
    }

    public function test_extension_filter_can_append_a_dates_condition(): void
    {
        \Eventy::addFilter('workflows.conditions_config', function ($config, $mailboxId) {
            $config['dates']['items']['today_is_business_day'] = [
                'title' => 'Today is a business day',
                'operators' => ['yes' => 'Yes', 'no' => 'No'],
                'values' => [],
            ];
            return $config;
        }, 20, 2);

        $config = ConditionCatalog::configured(7);

        $this->assertSame(7, $config['mailbox_id']);
        $this->assertArrayHasKey('today_is_business_day', $config['dates']['items']);
    }

    public function test_optional_modules_hide_tag_and_custom_field(): void
    {
        $config = ConditionCatalog::configured(3, ['tags' => false, 'customfields' => false]);

        $this->assertNull($this->conditionItem($config, 'tag'));
        $this->assertNull($this->conditionItem($config, 'custom_field'));
        $this->assertArrayHasKey('channel', $config['conversation']['items']);
    }

    public function test_tag_item_is_added_when_tags_are_enabled(): void
    {
        $config = ConditionCatalog::configured(3, ['tags' => true, 'customfields' => false]);

        $this->assertArrayHasKey('tags', $config);
        $this->assertArrayHasKey('tag', $config['tags']['items']);
        $this->assertSame(
            ['contains', 'not_contains', 'equal', 'not_equal'],
            array_keys($config['tags']['items']['tag']['operators'])
        );
        $this->assertSame(
            ['Contains', 'Does not contain', 'Is equal', 'Is not equal'],
            array_values($config['tags']['items']['tag']['operators'])
        );
        $this->assertNull($this->conditionItem($config, 'custom_field'));
        $this->assertArrayHasKey('channel', $config['conversation']['items']);
    }

    public function test_custom_field_item_is_added_when_customfields_are_enabled(): void
    {
        $config = ConditionCatalog::configured(4, ['customfields' => true]);

        $this->assertArrayHasKey('custom_fields', $config);
        $this->assertArrayHasKey('custom_field', $config['custom_fields']['items']);
        $this->assertSame(
            ['equal', 'not_equal', 'contains', 'not_contains', 'is_set', 'is_not_set'],
            array_keys($config['custom_fields']['items']['custom_field']['operators'])
        );
        $this->assertSame(
            ['Is equal', 'Is not equal', 'Contains', 'Does not contain', 'Is set', 'Is not set'],
            array_values($config['custom_fields']['items']['custom_field']['operators'])
        );
        $this->assertNull($this->conditionItem($config, 'tag'));
        $this->assertArrayHasKey('channel', $config['conversation']['items']);
    }

    /**
     * @param array  $config
     * @param string $type
     * @return array|null
     */
    private function conditionItem(array $config, string $type): ?array
    {
        foreach ($config as $group) {
            if (!is_array($group) || !isset($group['items']) || !is_array($group['items'])) {
                continue;
            }
            if (array_key_exists($type, $group['items'])) {
                return $group['items'][$type];
            }
        }

        return null;
    }
}
