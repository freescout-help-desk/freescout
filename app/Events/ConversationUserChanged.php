<?php

namespace App\Events;

use App\Conversation;
use App\User;

class ConversationUserChanged
{
    public $conversation;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, User $user)
    {
        $this->conversation = $conversation;
        $this->user = $user;
    }
}
