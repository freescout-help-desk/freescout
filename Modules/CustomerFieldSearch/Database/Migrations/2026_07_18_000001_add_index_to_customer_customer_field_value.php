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

        if (\Helper::isPgSql()) {
            if (!$this->pgIndexExists(self::INDEX_NAME)) {
                // text_pattern_ops makes a prefix LIKE ('value%') sargable on
                // Postgres, which a plain btree index on a text column is not.
                DB::statement('CREATE INDEX '.self::INDEX_NAME.' ON '.$table.' (value text_pattern_ops)');
            }
        } else {
            if (!$this->mysqlIndexExists($table)) {
                // value is a TEXT column — MySQL requires a prefix length to
                // index it. 40 chars comfortably covers account numbers/ID
                // card numbers while keeping the index small at 100k+ rows.
                DB::statement('ALTER TABLE '.$table.' ADD INDEX '.self::INDEX_NAME.' (value(40))');
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

        if (\Helper::isPgSql()) {
            if ($this->pgIndexExists(self::INDEX_NAME)) {
                DB::statement('DROP INDEX '.self::INDEX_NAME);
            }
        } else {
            if ($this->mysqlIndexExists($table)) {
                DB::statement('ALTER TABLE '.$table.' DROP INDEX '.self::INDEX_NAME);
            }
        }
    }

    protected function mysqlIndexExists($table)
    {
        $rows = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [self::INDEX_NAME]);

        return count($rows) > 0;
    }

    protected function pgIndexExists($indexName)
    {
        $rows = DB::select('SELECT 1 FROM pg_indexes WHERE indexname = ?', [$indexName]);

        return count($rows) > 0;
    }
}
