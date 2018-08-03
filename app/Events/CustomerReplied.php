<?php

namespace App\Events;

use App\Conversation;
use App\Thread;

class CustomerReplied
{
    public $conversation;
    public $thread;
    public $is_new_conversation;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, Thread $thread, bool $is_new_conversation)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
        $this->is_new_conversation = $is_new_conversation;
    }
}
