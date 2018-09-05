<?php

use App\MailboxUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailboxUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mailbox_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mailbox_id');
            $table->integer('user_id');
            $table->unsignedTinyInteger('after_send')->default(MailboxUser::AFTER_SEND_NEXT);

            // Indexes
            $table->index(['user_id', 'mailbox_id']);
            $table->index(['mailbox_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mailbox_user');
    }
}
