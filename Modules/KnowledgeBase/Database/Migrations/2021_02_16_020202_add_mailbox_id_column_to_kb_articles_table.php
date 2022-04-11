<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMailboxIdColumnToKbArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->unsignedInteger('mailbox_id');
            $table->dropIndex('kb_articles_status_index');
            $table->index(['mailbox_id', 'status']);
        });
        if (\Module::isActive('knowledgebase')  && class_exists('KbCategory')) {
            $articles = \KbArticle::get();
            if ($articles) {
                // Get the first mailbox.
                $mailbox = \App\Mailbox::first();
                if ($mailbox) {
                    \KbArticle::where('mailbox_id', 0)->update(['mailbox_id' => $mailbox->id]);
                }
            }
        }
        // Schema::table('kb_articles', function (Blueprint $table) {
        //     $table->unsignedInteger('mailbox_id')->nullabe(false)->change();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->dropColumn('mailbox_id');
        });
    }
}
