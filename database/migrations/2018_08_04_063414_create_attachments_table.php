<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('file_dir', 20)->nullable(); // examples: 1/2, 1/2/3
            $table->string('file_name', 255);
            $table->string('mime_type', 127);
            $table->unsignedInteger('type');
            $table->unsignedInteger('size')->nullable();
            $table->boolean('embedded')->default(false);

            // Indexes
            $table->index(['thread_id', 'embedded']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}
