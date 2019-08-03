<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
//use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class RealtimeConvView implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * The notifiable entity who received the notification.
     *
     * @var mixed
     */
    //public $notifiable;

    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Notification
     */
    //public $notification;

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
    public function __construct(/*$notifiable, $notification,*/ $data)
    {
        $this->data = $data;
        // $this->notifiable = $notifiable;
        // $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // $channels = $this->notification->broadcastOn();

        // if (!empty($channels)) {
        //     return $channels;
        // }

        return new \Illuminate\Broadcasting\Channel($this->channelName());
        //return [new PrivateChannel()];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return $this->data;

        /*return array_merge($this->data, [
            'id'   => $this->notification->id,
            'type' => get_class($this->notification),
        ]);*/
    }

    /**
     * Get the broadcast channel name for the event.
     *
     * @return string
     */
    protected function channelName()
    {
        // if (method_exists($this->notifiable, 'receivesBroadcastNotificationsOn')) {
        //     return $this->notifiable->receivesBroadcastNotificationsOn($this->notification);
        // }

        return 'conv';

        // $class = str_replace('\\', '.', get_class($this->notifiable));

        // return $class.'.'.$this->notifiable->getKey();
    }

    /**
     * Helper funciton.
     */
    public static function dispatchSelf($conversation_id, $user, $replying = false)
    {
        $notification_data = [
            'conversation_id' => $conversation_id,
            'user_id'         => $user->id,
            'user_photo_url'  => $user->getPhotoUrl(false),
            // These has to be encoded to avoid "Unable to JSON encode payload. Error code: 5"
            'user_initials'   => htmlentities($user->getInitials()),
            'user_name'       => htmlentities($user->getFullName()),
            'replying'        => (int)$replying,
        ];
        event(new \App\Events\RealtimeConvView($notification_data));
    }
}
