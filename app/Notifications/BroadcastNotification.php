<?php

namespace App\Notifications;

use App\Conversation;
use App\Thread;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BroadcastNotification extends Notification
{
    public $conversation;

    public $thread;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($conversation, $thread)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $user
     * @return array
     */
    public function via($user)
    {
        return ['polycast'];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($user)
    {
        return new BroadcastMessage([
            'thread_id' => $this->thread->id,
        ]);
    }
}
