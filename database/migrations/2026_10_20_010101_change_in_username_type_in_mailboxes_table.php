<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeInUsernameTypeInMailboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // https://github.com/freescout-help-desk/freescout/issues/5752
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->text('in_username')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->string('in_username', 100)->nullable()->change();
        });
    }
}
