<?php
/**
 * Fefresh chats list when new thread created in mailbox.
 */
namespace App\Events;

use App\Conversation;
use App\Mailbox;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class RealtimeChat implements ShouldBroadcastNow
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
        if (!empty($this->data['mailbox_id'])) {
            return 'chat.'.$this->data['mailbox_id'];
        } else {
            return 'chat.0';
        }
    }

    /**
     * Helper funciton.
     */
    public static function dispatchSelf($mailbox_id)
    {
        if (!\Helper::isChatModeAvailable()) {
            return;
        }
        $notification_data = [
            'mailbox_id'      => $mailbox_id
        ];
        event(new \App\Events\RealtimeChat($notification_data));
    }

    public static function processPayload($payload)
    {
        $user = auth()->user();
        $mailbox = Mailbox::rememberForever()->find($payload->mailbox_id);

        // Check if user can listen to this event.
        if (!$user || !$mailbox || !$user->can('viewCached', $mailbox)) {
            return [];
        }

        // Chats are retrieved in the template.
        $template_data = [
            'mailbox' => $mailbox,
        ];

        $payload->chats_html = \View::make('mailboxes/partials/chat_list')->with($template_data)->render();

        return $payload;
    }
}
