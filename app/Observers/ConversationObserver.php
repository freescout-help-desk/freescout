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
        $conversation->mailbox->updateFoldersCounters();
    }

    /**
     * On conversation delete.
     *
     * @param Conversation $conversation
     */
    public function deleting(Conversation $conversation)
    {
        $conversation->threads()->delete();
    }
}
