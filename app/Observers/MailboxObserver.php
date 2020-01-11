<?php

namespace App\Observers;

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
        // Create folders
        $mailbox->createPublicFolders();
        $mailbox->syncPersonalFolders();
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

        \Eventy::action('mailbox.before_delete', $mailbox);
    }
}
