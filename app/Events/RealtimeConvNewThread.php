<?php
/**
 * New thread created in conversation.
 */
namespace App\Events;

use App\Conversation;
use App\Mailbox;
use App\Thread;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class RealtimeConvNewThread implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * The notification data.
     *
     * @var array
     */
    public $data = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\Channel($this->channelName());
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return $this->data;
    }

    /**
     * Get the broadcast channel name for the event.
     *
     * @return string
     */
    protected function channelName()
    {
        if (!empty($this->data['conversation_id'])) {
            return 'conv.'.$this->data['conversation_id'];
        } else {
            return 'conv.0';
        }
    }

    /**
     * Helper funciton.
     */
    public static function dispatchSelf($thread)
    {
        if ($thread->state != Thread::STATE_PUBLISHED) {
            return;
        }
        $notification_data = [
            'thread_id'       => $thread->id,
            'conversation_id' => $thread->conversation_id,
            // conversation is prefetched in ThreadObserver.
            'mailbox_id'      => $thread->conversation->mailbox_id,
            //'user_id'         => $thread->created_by_user_id,
        ];
        event(new \App\Events\RealtimeConvNewThread($notification_data));
    }

    public static function processPayload($payload)
    {
        $user = auth()->user();
        $mailbox = Mailbox::find($payload->mailbox_id);

        // Check if user can listen to this event.
        if (!$user || !$mailbox || !$user->can('view', $mailbox)) {
            return [];
        }

        $thread = Thread::find($payload->thread_id);
        if (!$thread) {
            return $payload;
        }

        // Add thread html to the payload.
        $template_data = [
            'conversation' => $thread->conversation,
            'mailbox'      => $thread->conversation->mailbox,
            'threads'      => [$thread],
        ];

        $payload->thread_html = \View::make('conversations/partials/threads')->with($template_data)->render();
        $payload->conversation_user_id = $thread->conversation->user_id;
        $payload->conversation_status = $thread->conversation->status;
        $payload->conversation_status_class = Conversation::$status_classes[$thread->conversation->status];
        $payload->conversation_status_icon = Conversation::$status_icons[$thread->conversation->status];

        return $payload;
    }
}
