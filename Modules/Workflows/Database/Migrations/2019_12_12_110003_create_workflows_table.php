<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkflowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mailbox_id');
            $table->string('name', 75);
            $table->unsignedTinyInteger('type')->default(1);
            $table->boolean('apply_to_prev')->default(false);
            $table->boolean('complete')->default(false);
            $table->boolean('active')->default(false);
            // JSON.
            $table->text('conditions')->nullable();
            // JSON.
            $table->text('actions')->nullable();
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->index(['mailbox_id', 'active', 'type', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflows');
    }
}
