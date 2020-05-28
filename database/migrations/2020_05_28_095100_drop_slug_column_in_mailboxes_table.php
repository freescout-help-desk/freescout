<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropSlugColumnInMailboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // slug field is not used.
        Schema::table('mailboxes', function (Blueprint $table) {
            // Index is dropped automatically.
            $table->dropColumn('slug');
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
            $table->string('slug', 16)->unique()->nullable();
        });
    }
}
