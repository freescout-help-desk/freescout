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

    /**
     * After conversation deleted from database.
     *
     * @param Conversation $conversation
     */
    public function deleted(Conversation $conversation)
    {
        \Eventy::action('conversation.deleted_forever', $conversation);
    }

    public function updated(Conversation $conversation)
    {
        \Eventy::action('conversation.updated', $conversation);
    }
}
