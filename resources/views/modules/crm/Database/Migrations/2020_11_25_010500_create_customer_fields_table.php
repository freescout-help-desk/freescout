<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_fields', function (Blueprint $table) {
            $table->increments('id');
            //$table->integer('mailbox_id');
            $table->string('name', 75);
            $table->unsignedTinyInteger('type')->default(1);
            $table->text('options')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('display')->default(true);
            $table->boolean('customer_can_view')->default(false);
            $table->boolean('customer_can_edit')->default(false);
            $table->integer('sort_order')->default(1)->index();
            //$table->timestamps();

            //$table->index(['mailbox_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_fields');
    }
}
