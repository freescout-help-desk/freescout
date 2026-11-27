<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChannelIdColumnToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores the customer's channel ID (customer_channel.channel_id) the conversation
     * is being held with, as a customer can have several channel IDs for the same channel.
     * This allows to determine the channel when sending reply to conversation.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            // To avoid "Row size too large" error.
            $table->text('preview')->change();
            $table->string('channel_id', 64)->after('channel')->nullable();
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
            $table->dropColumn('channel_id');
            $table->string('preview', 255 /*Conversation::PREVIEW_MAXLENGTH*/)->change();
        });
    }
}
