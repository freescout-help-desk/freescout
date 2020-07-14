<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConversationsAddHistorySetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Mute notifications for events not directly related to the user
            $table->string('email_conv_history', 10)->default('global');
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
            $table->dropColumn('email_conv_history');
        });
    }
}
