<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentSavedReplyIdToSavedRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_replies', function (Blueprint $table) {
            $table->unsignedInteger('parent_saved_reply_id')->nullable();
            $table->longText('text')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saved_replies', function (Blueprint $table) {
            $table->dropColumn('parent_saved_reply_id');
            $table->longText('text')->change();
        });
    }
}
