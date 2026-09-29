<?php

namespace Modules\Workflows\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Workflows\Entities\Workflow;
use Modules\Workflows\Entities\WorkflowAction;
use Modules\Workflows\Entities\WorkflowCondition;
use Tests\TestCase;

class WorkflowSchemaTest extends TestCase
{
    public function test_migration_creates_tables_and_models_match_the_schema(): void
    {
        $previousDefault = config('database.default');

        config([
            'database.connections.workflow_schema' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
        DB::purge('workflow_schema');
        config(['database.default' => 'workflow_schema']);

        // PHP 8.4 deprecates ${var} while compiling Laravel 5.5's SQLiteConnector.
        // The app error handler turns that compile warning into an exception.
        $reporting = error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        try {
            class_exists(\Illuminate\Database\Connectors\SQLiteConnector::class, true);
        } finally {
            error_reporting($reporting);
        }

        require_once base_path('Modules/Workflows/Database/Migrations/2026_09_29_120000_create_workflow_tables.php');

        $migration = new \CreateWorkflowTables();

        try {
            $migration->up();

            $this->assertTrue(Schema::hasTable('workflows'));
            $this->assertTrue(Schema::hasTable('workflow_conditions'));
            $this->assertTrue(Schema::hasTable('workflow_actions'));
            $this->assertTrue(Schema::hasTable('conversation_workflows'));

            $indexes = $this->indexColumns('conversation_workflows');
            $this->assertTrue(
                $this->hasIndex($indexes, ['conversation_id', 'workflow_id'], true),
                'conversation_workflows is missing a unique index on conversation_id, workflow_id'
            );
            $this->assertTrue(
                $this->hasIndex($indexes, ['workflow_id'], false),
                'conversation_workflows is missing a standalone index on workflow_id'
            );

            $operator = $this->column('workflow_actions', 'operator');
            $this->assertNotNull($operator);
            $this->assertSame(0, (int) $operator->notnull);

            $workflow = new Workflow();
            $this->assertSame(['id'], $workflow->getGuarded());
            $this->assertNotEmpty($workflow->getGuarded());
            $this->assertSame('boolean', $workflow->getCasts()['active']);
            $this->assertSame('boolean', $workflow->getCasts()['apply_to_previous']);
            $this->assertOrderedHasMany($workflow->conditions(), WorkflowCondition::class);
            $this->assertOrderedHasMany($workflow->actions(), WorkflowAction::class);

            $condition = new WorkflowCondition();
            $action = new WorkflowAction();
            $this->assertSame('array', $condition->getCasts()['value']);
            $this->assertSame('array', $action->getCasts()['value']);
            $this->assertFalse($action->hasCast('operator'));
            $action->operator = null;
            $this->assertNull($action->operator);
        } finally {
            try {
                $migration->down();
            } finally {
                config(['database.default' => $previousDefault]);
                DB::purge('workflow_schema');
            }
        }
    }

    private function hasIndex(array $indexes, array $columns, $unique)
    {
        foreach ($indexes as $index) {
            if ($index['columns'] === $columns && $index['unique'] === $unique) {
                return true;
            }
        }

        return false;
    }

    private function indexColumns($table)
    {
        $indexes = [];

        foreach (DB::select('PRAGMA index_list('.$table.')') as $index) {
            $columns = [];
            foreach (DB::select('PRAGMA index_info('.$index->name.')') as $column) {
                $columns[] = $column->name;
            }

            $indexes[] = [
                'unique' => (int) $index->unique === 1,
                'columns' => $columns,
            ];
        }

        return $indexes;
    }

    private function column($table, $name)
    {
        foreach (DB::select('PRAGMA table_info('.$table.')') as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        return null;
    }

    private function assertOrderedHasMany($relation, $related)
    {
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf($related, $relation->getRelated());

        $orders = $relation->getBaseQuery()->orders;
        $this->assertNotEmpty($orders);
        $this->assertSame('sort_order', $orders[0]['column']);
        $this->assertSame('asc', $orders[0]['direction']);
    }
}
