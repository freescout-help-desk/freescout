<?php
/**
 * New thread created in mailbox.
 */
namespace App\Events;

use App\Conversation;
use App\Mailbox;
use App\Folder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class RealtimeMailboxNewThread implements ShouldBroadcastNow
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
            return 'mailbox.'.$this->data['mailbox_id'];
        } else {
            return 'mailbox.0';
        }
    }

    /**
     * Helper funciton.
     */
    public static function dispatchSelf($mailbox_id)
    {
        $notification_data = [
            'mailbox_id'      => $mailbox_id
        ];
        event(new \App\Events\RealtimeMailboxNewThread($notification_data));
    }

    public static function processPayload($payload)
    {
        $user = auth()->user();
        $mailbox = Mailbox::rememberForever()->find($payload->mailbox_id);

        // Check if user can listen to this event.
        if (!$user || !$mailbox || !$user->can('viewCached', $mailbox)) {
            return [];
        }

        $folder = null;
        $foler_id = Conversation::getFolderParam();
        if ($foler_id) {
            $folder = Folder::find($foler_id);
        }
        // Just in case.
        if (!$folder) {
            $folder = new Folder();
        }
        $template_data = [
            'folders' => $mailbox->getAssesibleFolders(),
            'folder'  => $folder,
            'mailbox' => $mailbox,
        ];

        $payload->folders_html = \View::make('mailboxes/partials/folders')->with($template_data)->render();

        return $payload;
    }
}
