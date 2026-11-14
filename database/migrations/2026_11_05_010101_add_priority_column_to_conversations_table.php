<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorityColumnToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Key of the priority level (see APP_PRIORITIES in .env).
            // NULL means "default priority level" (APP_PRIORITY_DEFAULT).
            // DO NOT use this field for sorting as it intentionally does not have any indexes.
            $table->unsignedTinyInteger('priority')->after('state')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
}
