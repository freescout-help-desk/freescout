<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMuteColumnToMailboxUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mailbox_user', function (Blueprint $table) {
            // Mute notifications for events not directly related to the user
            $table->boolean('mute')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mailbox_user', function (Blueprint $table) {
            $table->dropColumn('mute');
        });
    }
}
