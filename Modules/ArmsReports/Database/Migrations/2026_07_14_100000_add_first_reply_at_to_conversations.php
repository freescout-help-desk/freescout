<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirstReplyAtToConversations extends Migration
{
    /**
     * first_reply_at is the launch-critical column for first-response
     * medians (ARMS-13): stamped by the conversation.user_replied listener
     * from day one so the metric is a column read, not a threads-table scan.
     * No backfill — historical rows are derived from threads at query time.
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('first_reply_at')->nullable()->after('closed_at');
        });
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('first_reply_at');
        });
    }
}
