<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowTables extends Migration
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
            $table->unsignedInteger('mailbox_id')->index();
            $table->string('name');
            $table->string('type');
            $table->boolean('active')->default(true);
            $table->boolean('apply_to_previous')->default(false);
            $table->unsignedInteger('max_executions')->default(1);
            $table->string('match')->default('all');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflow_id')->index();
            $table->string('type');
            $table->string('operator');
            $table->json('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflow_id')->index();
            $table->string('type');
            $table->string('operator')->nullable();
            $table->json('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('conversation_workflows', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('conversation_id');
            $table->unsignedInteger('workflow_id');
            $table->unsignedInteger('executions')->default(0);
            $table->unique(['conversation_id', 'workflow_id']);
            $table->index('workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversation_workflows');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_conditions');
        Schema::dropIfExists('workflows');
    }
}
