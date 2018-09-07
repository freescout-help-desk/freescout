<?php

use \Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePolycastEventsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('polycast_events', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->text('channels');
            $table->text('event');
            $table->text('payload');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('polycast_events');
    }

}
