<?php

namespace App\Observers;

use App\Thread;

class ThreadObserver
{
    /**
     * Thread created.
     *
     * @param Thread $thread
     */
    public function created(Thread $thread)
    {
        // Update data in conversation
        $conversation = $thread->conversation;

        $now = date('Y-m-d H:i:s');
        if (!in_array($thread->type, [Thread::TYPE_LINEITEM, Thread::TYPE_NOTE])) {
            $conversation->threads_count++;
        }
        if (!in_array($thread->type, [Thread::TYPE_CUSTOMER])) {
            $conversation->user_updated_at = $now;
        }
        $conversation->last_reply_at = $now;
        $conversation->save();
    }
}
