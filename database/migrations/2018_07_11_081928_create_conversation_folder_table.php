<?php
/**
 * Table stores conversations which user marked as starred
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationFolderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation_folder', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('folder_id');
            $table->integer('conversation_id');

            // Indexes
            $table->unique(['folder_id', 'conversation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversation_folder');
    }
}
