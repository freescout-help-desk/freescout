<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInImapFoldersInMailboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add INBOX to the in_imap_folders.
        $mailboxes = App\Mailbox::select(['id', 'in_imap_folders'])->get();
        foreach ($mailboxes as $mailbox) {
            $in_imap_folders = $mailbox->getInImapFolders();
            if (count($in_imap_folders) && !in_array('INBOX', $in_imap_folders)) {
                array_unshift($in_imap_folders, 'INBOX');
                $mailbox->setInImapFolders($in_imap_folders);
                $mailbox->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
