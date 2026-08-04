<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastCustomerReplyAtColumnToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            // DO NOT use this field for sorting as it intentionally does not have any indexes.
            // All indexes are using "last_reply_at".
            $table->timestamp('last_customer_reply_at')->after('last_reply_from')->nullable();
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
            $table->dropColumn('last_customer_reply_at');
        });
    }
}
