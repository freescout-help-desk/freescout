<?php

namespace App\Observers;

use App\Conversation;

class ConversationObserver
{
    /**
     * On conversation delete.
     * 
     * @param  Conversation $mailbox
     * @return [type]           [description]
     */
    public function deleting(Conversation $conversation)
    {
        $conversation->threads()->delete();
    }
}