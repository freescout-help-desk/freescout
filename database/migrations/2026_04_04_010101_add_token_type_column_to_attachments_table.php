<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenTypeColumnToAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // We can't use Attachement::TOKEN_TYPE_... constants here
        // as they may not be available during the process of updating the application.
        
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedTinyInteger('token_type')->default(3);
        });

        DB::table('attachments')
            ->where('public', true)
            ->update(['token_type' => 1]);

        DB::table('attachments')
            ->where('public', false)
            ->update(['token_type' => 2]);

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('public');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->boolean('public')->default(false);
        });

        DB::table('attachments')
            ->where('token_type', 1)
            ->update(['public' => true]);

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('token_type');
        });
    }
}
