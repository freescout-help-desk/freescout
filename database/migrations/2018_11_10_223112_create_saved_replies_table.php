<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSavedRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saved_replies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 80)->nullable(false);
            $table->integer('mailbox_id')->nullable(false);
            $table->text('body')->nullable(false);
            $table->unique(['mailbox_id', 'name']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saved_replies');
    }
}
