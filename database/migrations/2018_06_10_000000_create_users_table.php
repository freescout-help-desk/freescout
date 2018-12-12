<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // https://developer.helpscout.com/mailbox-api/endpoints/users/get/
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 20);
            $table->string('last_name', 30);
            $table->string('email', User::EMAIL_MAX_LENGTH)->unique();
            $table->string('password', 255);
            $table->unsignedTinyInteger('role')->default(User::ROLE_USER)->index(); // admin/user
            $table->string('timezone', 255)->default('UTC');
            $table->string('photo_url', 255)->nullable();
            $table->unsignedTinyInteger('type')->default(User::TYPE_USER); // team/user
            $table->unsignedTinyInteger('invite_state')->default(User::INVITE_STATE_NOT_INVITED);
            $table->string('invite_hash', 100)->nullable();
            // It is not clear how alternate user emails should be used.
            // For now they are not used in the app and there is no uniqueness check.
            $table->string('emails', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('phone', 60)->nullable();
            $table->unsignedTinyInteger('time_format')->default(User::TIME_FORMAT_24);
            $table->boolean('enable_kb_shortcuts')->default(true);
            //$table->boolean('is_user_workflow_related')->default(false);
            $table->boolean('locked')->default(false);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
