<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Conversation;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // https://developer.helpscout.com/mailbox-api/endpoints/conversations/get/
        Schema::create('conversations', function (Blueprint $table) {
            $table->increments('id');
            // Conversation Number
            $table->unsignedInteger('number');
            // Number of threads the conversation has
            $table->unsignedInteger('threads')->default(0);
            $table->unsignedTinyInteger('type');
            $table->integer('folder_id');
            $table->unsignedTinyInteger('status')->default(Conversation::STATUS_ACTIVE);
            $table->unsignedTinyInteger('state')->default(Conversation::STATE_DRAFT);
            $table->string('subject', 998);
            $table->text('cc')->nullable(); // JSON
            $table->text('bcc')->nullable(); // JSON
            $table->string('preview', Conversation::PREVIEW_MAXLENGTH);
            // The imported field enables conversation to be created for historical purposes 
            // (i.e. if moving from a different platform, you can import your history). 
            // When imported is set to true, no outgoing emails or notifications will be generated.
            $table->boolean('imported')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->integer('mailbox_id');
            // assignee - Who the conversation is assigned to
            $table->integer('user_id')->nullable();
            // primaryCustomer
            $table->integer('customer_id');
            // Originating source of the conversation - user or customer
            // ID of the customer or user who created the conversation
            // createdBy in the API
            $table->integer('created_by');
            // source.via - Originating source of the conversation - user or customer
            $table->unsignedTinyInteger('source_via');
            // source.type - Originating type of the conversation (email, web, API etc)
            $table->unsignedTinyInteger('source_type');
            // closedBy - ID of the user who closed the conversation
            $table->integer('closed_by_user')->nullable();
            // UTC time when the conversation was closed
            $table->timestamp('closed_at')->nullable();
            // UTC time when the last user update occurred
            $table->timestamp('user_updated_at')->nullable();
            // customerWaitingSince
            $table->timestamp('last_reply_at')->nullable();
            // Whether the last reply was from a user or a customer
            $table->unsignedTinyInteger('last_reply_from')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['folder_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
