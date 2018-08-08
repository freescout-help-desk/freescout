<?php

namespace App\Events;

use App\Conversation;
use App\Thread;

class UserCreatedConversation
{
    public $conversation;
    public $last_thread;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, Thread $last_thread)
    {
        $this->conversation = $conversation;
        $this->last_thread = $last_thread;
    }
}
