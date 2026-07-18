<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSortablecustomfieldsUserColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Absence of a row means "visible and sortable" (today's behaviour) —
        // rows only exist once an agent actively hides a field or turns off
        // sorting for it, so nothing needs backfilling for existing users.
        Schema::create('sortablecustomfields_user_columns', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('mailbox_id');
            $table->unsignedInteger('custom_field_id');
            $table->boolean('visible')->default(true);
            $table->boolean('sortable')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'mailbox_id', 'custom_field_id'], 'scf_user_columns_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sortablecustomfields_user_columns');
    }
}
