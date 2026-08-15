<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIndexesInConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {

            // Not needed as it's covered by [folder_id, state, closed_at] now.
            $table->dropIndex(['folder_id', 'state']);

            // Unassigned folder:
            // SELECT *
            // FROM `conversations`
            // WHERE `conversations`.`folder_id` = '9' 
            // AND `conversations`.`folder_id` IS NOT NULL
            // AND `state` = '2'
            // ORDER BY `status` ASC, `last_reply_at` DESC
            // LIMIT 50 OFFSET 0
            // 
            // Assigned folder:
            // SELECT *
            // FROM `conversations`
            // WHERE `conversations`.`folder_id` = '11' 
            // AND `conversations`.`folder_id` IS NOT NULL 
            // AND `user_id` <> '1' 
            // AND `state` = '2'
            // ORDER BY `status` ASC, `last_reply_at` DESC
            // LIMIT 50 OFFSET 0
            // 
            // Index is not used, so we are not adding it.
            //$table->index(['folder_id', 'state', 'status']);
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
            //$table->dropIndex(['folder_id', 'state', 'status']);
            $table->index(['folder_id', 'state']);
        });
    }
}
