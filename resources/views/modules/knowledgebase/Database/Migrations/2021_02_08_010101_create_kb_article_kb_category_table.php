<?php

use App\MailboxUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKbArticleKbCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kb_article_kb_category', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('kb_article_id');
            $table->integer('kb_category_id');
            $table->integer('sort_order')->default(1);

            // Indexes
            $table->unique(['kb_article_id', 'kb_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kb_article_kb_category');
    }
}
