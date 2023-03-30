<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKbCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 191)->unique();
            $table->string('description', 191)->nullable();
            // Parent category id.
            $table->integer('kb_category_id')->nullable();
            // public, private.
            $table->unsignedInteger('visibility')->default(1);
            $table->boolean('expand')->default(false);
            // Dinamically.
            //$table->integer('articles_count')->default(0);
            // Category position.
            $table->integer('sort_order')->default(1);
            // Articles sorting: popularity, name, last updated, custom
            $table->integer('articles_order')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kb_categories');
    }
}
