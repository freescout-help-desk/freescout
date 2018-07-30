<?php

namespace App\Listeners;

use App\Events\ConversationStatusChanged;
use App\Thread;

class CreateThreadStatusChanged
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ConversationStatusChanged $event
     *
     * @return void
     */
    public function handle(ConversationStatusChanged $event)
    {
        $thread = new Thread();
        $thread->conversation_id = $event->conversation->id;
        $thread->user_id = $event->conversation->user_id;
        $thread->type = Thread::TYPE_LINEITEM;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->status = $event->conversation->status;
        $thread->action_type = Thread::ACTION_TYPE_STATUS_CHANGED;
        $thread->source_via = Thread::PERSON_USER;
        // todo: this need to be changed for API
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->customer_id = $event->conversation->customer_id;
        $thread->created_by_user_id = auth()->user()->id;
        $thread->save();
    }
}
