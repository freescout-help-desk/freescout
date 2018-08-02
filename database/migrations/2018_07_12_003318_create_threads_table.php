<?php

use App\Thread;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id');
            // assignedTo - The user assigned to this thread
            // used to display user who was assigned to the thread in the conversation
            $table->integer('user_id')->nullable();
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('status')->default(Thread::STATUS_ACTIVE);
            $table->unsignedTinyInteger('state')->default(Thread::STATE_DRAFT);
            // Describes an optional action associated with the line item
            $table->unsignedTinyInteger('action_type')->nullable();
            $table->string('action_text', 255)->nullable();
            // lineitems do not have body
            $table->text('body', 65535)->nullable();
            // Original body after thread text is changed
            $table->text('body_original', 65535)->nullable();
            $table->text('to')->nullable(); // JSON
            $table->text('cc')->nullable(); // JSON
            $table->text('bcc')->nullable(); // JSON
            // Email Message-ID header for email received from customer
            $table->string('message_id', 998)->nullable();
            // source.via - Originating source of the thread - user or customer
            $table->unsignedTinyInteger('source_via');
            // source.type - Originating type of the thread (email, web, API etc)
            $table->unsignedTinyInteger('source_type');
            // customer - If thread type is message, this is the customer associated with the thread.
            // If thread type is customer, this is the the customer who initiated the thread.
            $table->integer('customer_id');
            //  Who created this thread. The source_via property will specify whether it was created by a user or a customer.
            //  See source_via
            $table->integer('created_by_user_id')->nullable();
            $table->integer('created_by_customer_id')->nullable();
            // ID of Saved reply that was used to create this Thread (savedReplyId)
            $table->integer('saved_reply_id')->nullable();
            // Status of the email sent to customer or user, to whom the thread is assigned
            $table->unsignedTinyInteger('send_status')->default(Thread::SEND_STATUS_TOSEND);
            // Text describing the sending status
            $table->string('send_status_text', 255)->nullable();
            // Email opened by customer
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('threads');
    }
}
