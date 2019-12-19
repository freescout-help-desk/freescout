<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBeforeReplyColumnToMailboxesTable extends Migration
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
        if (version_compare(config('app.version'), '1.3.17', '<')) {
            Schema::table('mailboxes', function (Blueprint $table) {
                $table->text('out_password')->nullable()->change();
                $table->text('in_password')->nullable()->change();
            });
        }

        Schema::table('mailboxes', function (Blueprint $table) {
            $table->text('before_reply')->nullable();
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
            $table->dropColumn('before_reply');
        });

        if (version_compare(config('app.version'), '1.3.17', '<')) {
            Schema::table('mailboxes', function (Blueprint $table) {
                $table->string('out_password', 255)->nullable()->change();
                $table->string('in_password', 512)->nullable()->change();
            });
        }
    }
}
