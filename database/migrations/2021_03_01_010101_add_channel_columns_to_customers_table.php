<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChannelColumnsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('chats');
            $table->unsignedTinyInteger('channel')->nullable();
            // It may have any length.
            // Now it's not clear why it may have any length,
            // so in customer_channel table it's varchar.
            $table->text('channel_id')->nullable();

            // We are not adding index, as requests are made in the background,
            // so performance here not very critical.
            //$table->index(['channel', DB::raw('channel_id(5)')]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('chats')->nullable(); // JSON
            $table->dropColumn('channel');
            $table->dropColumn('channel_id');
        });
    }
}
