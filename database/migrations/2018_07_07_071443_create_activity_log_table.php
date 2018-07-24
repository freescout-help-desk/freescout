<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateActivityLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('log_name', 191)->nullable();
            $table->text('description');
            $table->integer('subject_id')->nullable();
            $table->string('subject_type', 255)->nullable();
            $table->integer('causer_id')->nullable();
            $table->string('causer_type', 55)->nullable();
            $table->text('properties')->nullable();
            $table->timestamps();

            $table->index('log_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop(config('activitylog.table_name'));
    }
}
