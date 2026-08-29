<?php

namespace App\Observers;

use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationObserver
{
	/**
	 * Populate "conversation_id" column.
	 */
    public function creating(DatabaseNotification $notification)
    {
        // $notification->data is already cast to array by the base model.
        if (!empty($notification->data['conversation_id'])) {
            $notification->conversation_id = $notification->data['conversation_id'];
        }
    }

    /**
     * Notifications DB record created.
     *
     * @param DatabaseNotification $notification
     */
    public function created(DatabaseNotification $notification)
    {
        $notification->notifiable->clearWebsiteNotificationsCache();
    }
}
