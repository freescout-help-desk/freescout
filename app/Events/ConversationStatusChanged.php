<?php

namespace App\Events;

use App\Conversation;

class ConversationStatusChanged
{
    public $conversation;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }
}
