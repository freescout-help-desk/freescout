<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // https://github.com/freescout-help-desk/freescout/issues/4069#issuecomment-2175476858
        Schema::table('threads', function (Blueprint $table) {
            $table->index(['created_at']);
        });
        // Schema::table('conversations', function (Blueprint $table) {
        //     $table->index(['mailbox_id', 'state', 'status'], 'conversations_id_state_status');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        // Schema::table('conversations', function (Blueprint $table) {
        //     $table->dropIndex('conversations_id_state_status');
        // });
    }
}
