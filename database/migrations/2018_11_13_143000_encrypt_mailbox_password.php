<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EncryptMailboxPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE `mailboxes` MODIFY `in_password` VARCHAR(512) NOT NULL;');

        foreach (\App\Mailbox::whereNotNull('in_password')->get() as $Mailbox) {
            $Mailbox->in_password = $Mailbox->getOriginal('in_password');
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
        foreach (\App\Mailbox::whereNotNull('in_password')->get() as $Mailbox) {
            $attributes = $Mailbox->getAttributes();
            $attributes = array_merge($attributes, ['in_password' => $Mailbox->in_password]);
            $Mailbox->setRawAttributes($attributes);
            $Mailbox->save();
        }

        \DB::statement('ALTER TABLE `mailboxes` MODIFY `in_password` VARCHAR(255) NOT NULL;');
    }
}
