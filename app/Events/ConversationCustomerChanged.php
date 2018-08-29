<?php

namespace App\Events;

use App\Conversation;

class ConversationCustomerChanged
{
    public $conversation;
    public $prev_customer_id;
    public $prev_customer_email;
    public $by_user;
    public $by_customer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Conversation $conversation, $prev_customer_id, $prev_customer_email, $by_user, $by_customer)
    {
        $this->conversation = $conversation;
        $this->prev_customer_id = $prev_customer_id;
        $this->prev_customer_email = $prev_customer_email;
        $this->by_user = $by_user;
        $this->by_customer = $by_customer;
    }
}
