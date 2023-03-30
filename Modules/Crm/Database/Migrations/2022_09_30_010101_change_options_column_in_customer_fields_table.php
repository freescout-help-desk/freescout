<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOptionsColumnInCustomerFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_fields', function (Blueprint $table) {
            // https://github.com/doctrine/dbal/issues/2566#issuecomment-480217999
            $table->longText('options')->comment(' ')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_fields', function (Blueprint $table) {
            $table->text('options')->comment('')->nullable()->change();
        });
    }
}
