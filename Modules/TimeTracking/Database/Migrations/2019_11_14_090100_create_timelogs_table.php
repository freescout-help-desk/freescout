<?php

use App\Conversation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimelogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timelogs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id');
            $table->integer('user_id');
            $table->unsignedTinyInteger('conversation_status')->default(Conversation::STATUS_ACTIVE);
            $table->integer('time_spent')->default(0);
            $table->boolean('paused')->default(false);
            $table->boolean('finished')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'finished', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timelogs');
    }
}
