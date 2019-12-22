<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePasswordsTypesInMailboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // To avoid 'Row size too large' error.
        // https://github.com/freescout-helpdesk/freescout/issues/393
        // https://dev.mysql.com/doc/refman/8.0/en/column-count-limit.html
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->text('out_password')->nullable()->change();
            $table->text('in_password')->nullable()->change();
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
