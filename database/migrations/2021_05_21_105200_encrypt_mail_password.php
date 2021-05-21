<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EncryptOptionPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        \App\Option::get('mail_password', \Config::get('mail.password'));
        $section = 'emails';
        $settings = [
            'mail_password' => !is_null($mail_password) ? encrypt($mail_password) : null;
        ];
        $Option->processSave($section, $settings);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        \App\Option::get('mail_password', \Config::get('mail.password'));
        $section = 'emails';
        $settings = [
            'mail_password' => !is_null($mail_password) ? decrypt($mail_password) : null;
        ];
        $Option->processSave($section, $settings);

    }
}