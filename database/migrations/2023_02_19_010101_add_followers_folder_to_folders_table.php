<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFollowersFolderToFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        # Find all user_ids and mailbox_ids they have access to
        $mailbox_users = \App\MailboxUser::all();

        # Create a folder of type Folder::TYPE_FOLLOWING (26)
        foreach ($mailbox_users as $mailbox_user) {
            $folder = \App\Folder::create([
                'mailbox_id' => $mailbox_user['mailbox_id'],
                'user_id' => $mailbox_user['user_id'],
                'type' => 26,
            ]);
            $folder->updateCounters();
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        # Remove all folders with type Folder::TYPE_FOLLOWING (26)
        \App\Folder::where('type','=',26)->delete();
    }
}