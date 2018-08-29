<?php

use App\Email;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Each email must be always connected to some customer
        Schema::create('emails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('customer_id');
            // Max email length is 255, but if we specify 255, we get can not create an index:
            // SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
            $table->string('email', 191)->unique();
            // Type is not used in the web interface, but appears in API
            $table->unsignedTinyInteger('type')->default(Email::TYPE_WORK);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emails');
    }
}
