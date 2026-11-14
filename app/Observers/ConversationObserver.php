<?php

namespace App\Observers;

use App\Conversation;

class ConversationObserver
{
    /**
     * On create before saving.
     *
     * @param Conversation $conversation
     */
    public function creating(Conversation $conversation)
    {
        if ($conversation->source_via == Conversation::PERSON_USER) {
            $conversation->read_by_user = true;
        }

        $conversation->subject = mb_substr($conversation->subject ?? '', 0, Conversation::SUBJECT_MAXLENGTH);
    }

    /**
     * On create.
     *
     * @param Conversation $conversation
     */
    public function created(Conversation $conversation)
    {
        // Better to do it manually
        //$conversation->mailbox->updateFoldersCounters();
    }

    /**
     * On before updating.
     *
     * @param Conversation $conversation
     */
    public function updating(Conversation $conversation)
    {
        // https://github.com/freescout-help-desk/freescout/issues/5201
        $conversation->subject = mb_substr($conversation->subject ?? '', 0, Conversation::SUBJECT_MAXLENGTH);
    }

    /**
     * On conversation delete.
     *
     * @param Conversation $conversation
     */
    public function deleting(Conversation $conversation)
    {
        $conversation->threads()->delete();
        $conversation->followers()->delete();

        \Eventy::action('conversation.deleting', $conversation);
    }

    public function updated(Conversation $conversation)
    {
        // Access of users to the conversation depends on its mailbox and assignee
        // ("only assigned conversations" permission), so website notifications
        // cache of the users who could lose access to the conversation must be cleared.
        // Note: in Laravel 5.5 dirty attributes are still available in "updated".
        if ($conversation->isDirty('mailbox_id')) {
            // Conversation moved: users of the previous mailbox may lose access.
            $original_mailbox = \App\Mailbox::find($conversation->getOriginal('mailbox_id'));
            if ($original_mailbox) {
                \App\User::clearWebsiteNotificationsCacheForUsers($original_mailbox->userIdsHavingAccess());
            }
        }
        if ($conversation->isDirty('user_id') && $conversation->getOriginal('user_id')) {
            // Previous assignee may lose access ("only assigned conversations").
            \App\User::clearWebsiteNotificationsCacheForUsers([$conversation->getOriginal('user_id')]);
        }

        \Eventy::action('conversation.updated', $conversation);
    }
}
