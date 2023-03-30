<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationCustomFieldTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation_custom_field', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id');
            $table->integer('custom_field_id');
            $table->text('value');

            // Indexes
            $table->unique(['conversation_id', 'custom_field_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversation_custom_field');
    }
}
