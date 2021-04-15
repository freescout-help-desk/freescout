<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetaColumnToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // To avoid "Row size too large" error.
            $table->text('company')->nullable()->change();
            $table->text('job_title')->nullable()->change();

            // Meta data in JSON format.
            $table->text('meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('meta');

            $table->string('company', 255)->nullable()->change();
            $table->string('job_title', 255)->nullable()->change();
        });
    }
}
