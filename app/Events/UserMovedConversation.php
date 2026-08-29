<?php

namespace App\Events;

use App\Conversation;
use App\Mailbox;
use App\Thread;

class UserMovedConversation
{
    public $conversation;
    //public $last_thread;
    public $caused_by_user_id;
    public $from_mailbox;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, /*$last_thread,*/ $caused_by_user_id, Mailbox $from_mailbox)
    {
        $this->conversation = $conversation;
        //$this->last_thread = $last_thread;
        $this->caused_by_user_id = $caused_by_user_id;
        $this->from_mailbox = $from_mailbox;
    }
}
