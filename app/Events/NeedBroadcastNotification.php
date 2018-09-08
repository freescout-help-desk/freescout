<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NeedBroadcastNotification implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * The name of the queue on which to place the event.
     *
     * @var string
     */
    public $broadcastQueue = 'broadcast';

    public $receiver_user_id;
    public $thread;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiver_user_id, $conversation, $thread)
    {
        $this->receiver_user_id = $receiver_user_id;
        $this->thread = $thread;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.User.'.$this->receiver_user_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'thread_id' => $this->thread->id
        ];
    }
}