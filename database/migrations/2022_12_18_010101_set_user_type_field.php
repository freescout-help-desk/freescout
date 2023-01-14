<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetUserTypeField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->where('status', 3) // User::STATUS_DELETED
            ->where('email', 'like', 'fs%@example.org%')
            ->update(['type' => 2]); // User::TYPE_ROBOT
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->where('status', 3) // User::STATUS_DELETED
            ->where('email', 'like', 'fs%@example.org%')
            ->update(['type' => 1]);
    }
}
