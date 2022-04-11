<?php
/**
 * Workflows processed for conversations.
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationWorkflowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation_workflow', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id');
            $table->integer('workflow_id');

            // Indexes
            $table->unique(['conversation_id', 'workflow_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversation_workflow');
    }
}
