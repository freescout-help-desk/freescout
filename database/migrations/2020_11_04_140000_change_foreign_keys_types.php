<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeForeignKeysTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->unsignedInteger('causer_id')->nullable()->change();
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedInteger('thread_id')->nullable()->change();
            $table->unsignedInteger('user_id')->nullable()->change();
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedInteger('folder_id')->change();
            $table->unsignedInteger('mailbox_id')->change();
            $table->unsignedInteger('user_id')->nullable()->change();
            $table->unsignedInteger('customer_id')->nullable()->change();
            $table->unsignedInteger('created_by_user_id')->nullable()->change();
            $table->unsignedInteger('created_by_customer_id')->nullable()->change();
            $table->unsignedInteger('closed_by_user_id')->nullable()->change();
        });
        Schema::table('conversation_folder', function (Blueprint $table) {
            $table->unsignedInteger('folder_id')->change();
            $table->unsignedInteger('conversation_id')->change();
        });
        Schema::table('emails', function (Blueprint $table) {
            $table->unsignedInteger('customer_id')->change();
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->unsignedInteger('mailbox_id')->change();
            $table->unsignedInteger('user_id')->nullable()->change();
        });
        Schema::table('mailbox_user', function (Blueprint $table) {
            $table->unsignedInteger('mailbox_id')->change();
            $table->unsignedInteger('user_id')->change();
        });
        Schema::table('send_logs', function (Blueprint $table) {
            $table->unsignedInteger('thread_id')->nullable()->change();
            $table->unsignedInteger('customer_id')->nullable()->change();
            $table->unsignedInteger('user_id')->nullable()->change();
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });
        Schema::table('threads', function (Blueprint $table) {
            $table->unsignedInteger('conversation_id')->change();
            $table->unsignedInteger('user_id')->nullable()->change();
            $table->unsignedInteger('customer_id')->nullable()->change();
            $table->unsignedInteger('created_by_user_id')->nullable()->change();
            $table->unsignedInteger('created_by_customer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
