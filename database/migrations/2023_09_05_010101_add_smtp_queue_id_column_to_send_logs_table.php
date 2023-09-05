<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSmtpQueueIdColumnToSendLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_logs', function (Blueprint $table) {
            $table->text('smtp_queue_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('send_logs', function (Blueprint $table) {
            $table->dropColumn('smtp_queue_id');
        });
    }
}
