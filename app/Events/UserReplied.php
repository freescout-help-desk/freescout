<?php

namespace App\Events;

use App\Conversation;

class UserReplied
{
    public $conversation;
    public $is_new_conversation;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, $is_new_conversation)
    {
        $this->conversation = $conversation;
        $this->is_new_conversation = $is_new_conversation;
    }
}
