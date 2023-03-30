<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMailboxIdColumnToKbCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Schema::table('kb_categories', function (Blueprint $table) {
                $table->unsignedInteger('mailbox_id')->index();
            });
        } catch (\Exception $e) {

        }
        if (\Module::isActive('knowledgebase') && class_exists('KbCategory')) {
            $categories = \KbCategory::get();
            if ($categories) {
                // Get the first mailbox.
                $mailbox = \App\Mailbox::first();
                if ($mailbox) {
                    \KbCategory::where('mailbox_id', 0)->update(['mailbox_id' => $mailbox->id]);
                }
            }
        }
        // Schema::table('kb_categories', function (Blueprint $table) {
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
        Schema::table('kb_categories', function (Blueprint $table) {
            $table->dropColumn('mailbox_id');
        });
    }
}
