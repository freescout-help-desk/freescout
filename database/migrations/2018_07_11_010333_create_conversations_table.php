<?php

use App\Conversation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            // Lineitems and notes are not counted
            $table->unsignedInteger('threads_count')->default(0);
            $table->unsignedTinyInteger('type');
            $table->integer('folder_id');
            $table->unsignedTinyInteger('status')->default(Conversation::STATUS_ACTIVE);
            $table->unsignedTinyInteger('state')->default(Conversation::STATE_DRAFT);
            // It has to be optional in order to create empty drafts.
            $table->string('subject', 998)->nullable();
            // Customer's email to which replies from users are sent.
            // Not used when fetching emails.
            // Customer may have several emails, so we need to know which
            // email to use for each conversation.
            $table->string('customer_email', 191)->nullable();
            // CC and BCC store values from the last reply from customer or user
            // For incoming messages values are stored as is
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
            // It has to be optional in order to create empty drafts.
            $table->integer('customer_id')->nullable();
            // Originating source of the conversation - user or customer
            // ID of the customer or user who created the conversation
            // createdBy in the API
            $table->integer('created_by_user_id')->nullable();
            $table->integer('created_by_customer_id')->nullable();
            // source.via - Originating source of the conversation - user or customer
            $table->unsignedTinyInteger('source_via');
            // source.type - Originating type of the conversation (email, web, API etc)
            $table->unsignedTinyInteger('source_type');
            // closedBy - ID of the user who closed the conversation
            $table->integer('closed_by_user_id')->nullable();
            // UTC time when the conversation was closed
            $table->timestamp('closed_at')->nullable();
            // UTC time when the last user update occurred
            $table->timestamp('user_updated_at')->nullable();
            // customerWaitingSince - reply by customer or user
            $table->timestamp('last_reply_at')->nullable();
            // Whether the last reply was from a user or a customer
            $table->unsignedTinyInteger('last_reply_from')->nullable();
            // Thread read by any user (used to display spam folder)
            $table->boolean('read_by_user')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['folder_id', 'status']);
            $table->index(['mailbox_id', 'customer_id']);
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
