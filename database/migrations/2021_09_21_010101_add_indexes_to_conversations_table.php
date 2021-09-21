<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['user_id', 'mailbox_id', 'state', 'status']);
            $table->index(['folder_id', 'state']);
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
            $table->dropIndex(['folder_id', 'state']);
            $table->dropIndex(['user_id', 'mailbox_id', 'state', 'status']);
        });
    }
}
