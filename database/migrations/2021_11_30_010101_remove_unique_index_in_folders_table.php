<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueIndexInFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropUnique(['mailbox_id', 'user_id', 'type']);
            $table->index(['mailbox_id', 'user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->unique(['mailbox_id', 'user_id', 'type']);
        });
    }
}
