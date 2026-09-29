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
}
