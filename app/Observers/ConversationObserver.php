<?php

namespace App\Observers;

use App\Conversation;

class ConversationObserver
{
    /**
     * On create.
     * 
     * @param  Conversation $conversation
     */
    public function created(Conversation $conversation)
    {
        $conversation->mailbox->updateFoldersCounters();
    }

    /**
     * On conversation delete.
     * 
     * @param  Conversation $conversation
     */
    public function deleting(Conversation $conversation)
    {
        $conversation->threads()->delete();
    }
}