<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNumberColumnToStringInConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE `conversations` MODIFY `number` VARCHAR(7);');
        \DB::statement('CREATE UNIQUE INDEX `idx_number` ON `conversations` (`number`)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement('ALTER TABLE `conversations` DROP INDEX `idx_number`');
        \DB::statement('ALTER TABLE `conversations` MODIFY `number` INT(10) UNSIGNED;');
    }
}
