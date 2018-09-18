<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // https://developer.helpscout.com/mailbox-api/endpoints/customers/get/
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('company', 255)->nullable();
            $table->string('job_title', 255)->nullable();
            $table->unsignedTinyInteger('photo_type')->nullable();
            $table->string('photo_url', 255)->nullable();
            // Age and gender do not exist in the web interface, but exist in the API
            $table->string('age', 7)->nullable();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->text('phones')->nullable(); // JSON
            $table->text('websites')->nullable(); // JSON
            $table->text('social_profiles')->nullable(); // JSON
            $table->text('chats')->nullable(); // JSON
            $table->text('background')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('zip', 12)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamps();

            // Indexes
            // For ajax search
            $table->index([DB::raw('first_name(191)'), DB::raw('last_name(191)')]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
