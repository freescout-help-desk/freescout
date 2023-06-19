<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlaSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sla_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('to_email');
            $table->string('frequency');
            $table->string('schedule');
            $table->time('time');
            $table->boolean('auto_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sla_settings');
    }
}
