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
     * Mailbox state (stored in meta) may have changed (archived/active):
     * non-admin users can not access archived mailboxes, so website
     * notifications cache of the mailbox users must not be used anymore.
     * Note: in Laravel 5.5 dirty attributes are still available in "updated".
     *
     * @param \App\Mailbox $mailbox
     */
    public function updated(Mailbox $mailbox)
    {
        if ($mailbox->isDirty('meta')) {
            // Meta contains other params as well, so compare the state only.
            $original_meta = $mailbox->getOriginal('meta');
            if (is_string($original_meta)) {
                $original_meta = json_decode($original_meta, true);
            }
            $original_state = $original_meta[Mailbox::META_STATE] ?? Mailbox::STATE_ACTIVE;

            if ($original_state != $mailbox->state) {
                \App\User::clearWebsiteNotificationsCacheForUsers(
                    $mailbox->users()->pluck('users.id')->all()
                );
            }
        }
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
        // Same things are deleted in Mailbox->deleteMailbox().
        $mailbox->users()->delete();
        $mailbox->conversations()->delete();
        $mailbox->folders()->delete();

        \Eventy::action('mailbox.before_delete', $mailbox);
    }

    public function deleted(Mailbox $mailbox)
    {
        \Eventy::action('mailbox.deleted', $mailbox);
    }
}
