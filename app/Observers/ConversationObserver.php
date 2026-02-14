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
        \Eventy::action('conversation.updated', $conversation);
    }
}
