<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EncryptMailPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $mail_password = \Option::get('mail_password');
        if ($mail_password) {
            \Option::set('mail_password', encrypt($mail_password));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $mail_password = \Option::get('mail_password');
        if ($mail_password) {
            \Option::set('mail_password', \Helper::decrypt($mail_password));
        }
    }
}