<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexToCustomerCustomerFieldValue extends Migration
{
    const INDEX_NAME = 'customer_customer_field_value_idx';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // The Crm module (paid, not installed in this repo) owns this table.
        // No-op if it isn't present on this environment.
        if (!Schema::hasTable('customer_customer_field')) {
            return;
        }

        $table = DB::getTablePrefix().'customer_customer_field';
        // Postgres index names are schema-scoped, not table-scoped — prefix
        // it the same way the table itself is prefixed, so a second
        // differently-prefixed FreeScout install sharing the same schema
        // can't collide with this one.
        $index = DB::getTablePrefix().self::INDEX_NAME;

        if (\Helper::isPgSql()) {
            if (!$this->pgIndexExists($index)) {
                // text_pattern_ops makes a prefix LIKE ('value%') sargable on
                // Postgres, which a plain btree index on a text column is not.
                DB::statement('CREATE INDEX '.$index.' ON '.$table.' (value text_pattern_ops)');
            }
        } else {
            if (!$this->mysqlIndexExists($table, $index)) {
                // value is a TEXT column — MySQL requires a prefix length to
                // index it. 40 chars comfortably covers account numbers/ID
                // card numbers while keeping the index small at 100k+ rows.
                DB::statement('ALTER TABLE '.$table.' ADD INDEX '.$index.' (value(40))');
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('customer_customer_field')) {
            return;
        }

        $table = DB::getTablePrefix().'customer_customer_field';
        $index = DB::getTablePrefix().self::INDEX_NAME;

        if (\Helper::isPgSql()) {
            if ($this->pgIndexExists($index)) {
                DB::statement('DROP INDEX '.$index);
            }
        } else {
            if ($this->mysqlIndexExists($table, $index)) {
                DB::statement('ALTER TABLE '.$table.' DROP INDEX '.$index);
            }
        }
    }

    protected function mysqlIndexExists($table, $indexName)
    {
        $rows = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$indexName]);

        return count($rows) > 0;
    }

    protected function pgIndexExists($indexName)
    {
        $rows = DB::select('SELECT 1 FROM pg_indexes WHERE indexname = ?', [$indexName]);

        return count($rows) > 0;
    }
}
