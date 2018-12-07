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
        if (version_compare(config('app.version'), '1.0.6', '<')) {
            Schema::table('mailboxes', function (Blueprint $table) {
                $table->string('in_password', 512)->nullable()->change();
            });

            foreach (\App\Mailbox::whereNotNull('in_password')->get() as $Mailbox) {
                $Mailbox->in_password = $Mailbox->getOriginal('in_password');
                $Mailbox->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (version_compare(config('app.version'), '1.0.6', '<')) {
            foreach (\App\Mailbox::whereNotNull('in_password')->get() as $Mailbox) {
                $attributes = $Mailbox->getAttributes();
                $attributes = array_merge($attributes, ['in_password' => $Mailbox->in_password]);
                $Mailbox->setRawAttributes($attributes);
                $Mailbox->save();
            }

            Schema::table('mailboxes', function (Blueprint $table) {
                $table->string('in_password')->nullable()->change();
            });
        }
    }
}
