<?php
/**
 * Fefresh conversation list when new thread is created in a folder.
 */
namespace App\Events;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class RealtimeConvList implements ShouldBroadcastNow
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
        if (!empty($this->data['folder_id'])) {
            return 'conv_list.'.$this->data['folder_id'];
        } else {
            return 'conv_list.0';
        }
    }

    /**
     * Helper funciton.
     */
    public static function dispatchSelf($folder_id, $thread_id)
    {
        $notification_data = [
            'folder_id'  => $folder_id,
            //'mailbox_id' => $mailbox_id,
            'thread_id'  => $thread_id,
            //'is_chat'    => (int)$conversation_is_chat,
        ];
        event(new \App\Events\RealtimeConvList($notification_data));
    }

    public static function processPayload($payload)
    {
        $request = request();
        $user = auth()->user();

        $folder = Folder::find($payload->folder_id);

        // Check if user can listen to this event.
        if (!$user || !$folder || !$user->can('view', $folder)) {
            return [];
        }

        $conversations = Conversation::getConvList($user, $folder);

        // Chats are retrieved in the template.
        $template_data = [
            'conversations' => $conversations,
        ];

        $payload->list_html = \View::make('conversations/partials/conv_list')->with($template_data)->render();

        return $payload;
    }
}
