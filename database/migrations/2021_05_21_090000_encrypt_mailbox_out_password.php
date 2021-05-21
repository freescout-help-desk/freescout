<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EncryptMailboxOutPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (\App\Mailbox::whereNotNull('out_password')->get() as $Mailbox) {
            $unencrypted_password = $Mailbox->getOriginal('out_password');
            if ($unencrypted_password) {
                $attributes = $Mailbox->getAttributes();
                $attributes = array_merge($attributes, ['out_password' => encrypt($unencrypted_password)]);
                $Mailbox->setRawAttributes($attributes);
            }
            $Mailbox->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (\App\Mailbox::whereNotNull('out_password')->get() as $Mailbox) {
            $attributes = $Mailbox->getAttributes();
            $attributes = array_merge($attributes, ['out_password' => $Mailbox->out_password]);
            $Mailbox->setRawAttributes($attributes);
            $Mailbox->save();
        }
    }
}