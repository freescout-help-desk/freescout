<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortOrderColumnToSavedRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_replies', function (Blueprint $table) {
            // By some reason MySQL does not use index not matter waht
            $table->integer('sort_order')->default(1);
            $table->index(['mailbox_id', 'sort_order']);
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
            $table->dropColumn('sort_order');
            $table->dropIndex(['mailbox_id', 'sort_order']);
        });
    }
}
