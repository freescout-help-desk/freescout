<?php

namespace App\Events;

use App\Conversation;
use App\Thread;

class UserCreatedConversationDraft
{
    public $conversation;
    public $thread;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, Thread $thread)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
    }
}
