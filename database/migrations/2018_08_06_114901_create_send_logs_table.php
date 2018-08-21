<?php
/**
 * Outgoing emails.
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSendLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('send_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id')->index();
            // Customer ID is set only if email sent to the main conversation customer
            $table->integer('customer_id')->nullable();
            $table->integer('user_id')->nullable();
            // Message-ID header of the outgoing email
            $table->string('message_id', 998);
            // We have to keep email as customer's or user's email may change
            $table->string('email', 191);
            $table->unsignedTinyInteger('status');
            $table->string('status_message', 255)->nullable();
            $table->timestamps();

            // Indexes
            // https://github.com/laravel/framework/issues/9293#issuecomment-373229281
            $table->index([DB::raw('message_id(191)')], 'send_logs_message_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('send_logs');
    }
}
