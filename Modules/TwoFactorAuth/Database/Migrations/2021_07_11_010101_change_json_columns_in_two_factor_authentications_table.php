<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeJsonColumnsInTwoFactorAuthenticationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('two_factor_authentications', function (Blueprint $table) {
            // To avoid MySQL error: https://github.com/freescout-helpdesk/freescout/issues/1305
            $table->text('recovery_codes')->nullable()->change();
            $table->text('safe_devices')->nullable()->change();
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
