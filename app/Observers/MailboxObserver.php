<?php

namespace App\Observers;

use App\Folder;
use App\Mailbox;

class MailboxObserver
{
    /**
     * Listen to the Mailbox created event.
     *
     * @param \App\Mailbox $mailbox
     *
     * @return void
     */
    public function created(Mailbox $mailbox)
    {
        foreach (Folder::$public_types as $type) {
            $folder = new Folder();
            $folder->mailbox_id = $mailbox->id;
            $folder->type = $type;
            $folder->save();
        }
        $mailbox->createAdminPersonalFolders();
    }

    /**
     * Delete the following on mailbox delete:
     * - folders
     * - conversations
     * - user permissions.
     *
     * @param Mailbox $mailbox
     *
     * @return [type] [description]
     */
    public function deleting(Mailbox $mailbox)
    {
        $mailbox->users()->delete();
        $mailbox->conversations()->delete();
        $mailbox->folders()->delete();
    }
}
