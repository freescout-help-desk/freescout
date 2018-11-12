<?php

namespace App\Channels;

use App\Events\RealtimeBroadcastNotificationCreated;
use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Notification;

class RealtimeBroadcastChannel extends BroadcastChannel
{
    /**
     * Send the given notification immediately using non-quable event.
     *
     * @param mixed                                  $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return array|null
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $this->getData($notifiable, $notification);

        $event = new RealtimeBroadcastNotificationCreated(
            $notifiable, $notification, is_array($message) ? $message : $message->data
        );

        return $this->events->dispatch($event);
    }
}
