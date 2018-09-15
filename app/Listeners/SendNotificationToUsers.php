<?php

namespace App\Listeners;

use App\Subscription;

class SendNotificationToUsers
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event)
    {
        $event_type = null;
        $caused_by_user_id = null;

        // Detect event type by event class
        switch (get_class($event)) {
            case 'App\Events\UserReplied':
                $caused_by_user_id = $event->thread->created_by_user_id;
                $event_type = Subscription::EVENT_TYPE_USER_REPLIED;
                break;
            case 'App\Events\UserAddedNote':
                $caused_by_user_id = $event->thread->created_by_user_id;
                $event_type = Subscription::EVENT_TYPE_USER_ADDED_NOTE;
                break;
            case 'App\Events\UserCreatedConversation':
                $caused_by_user_id = $event->conversation->created_by_user_id;
                $event_type = Subscription::EVENT_TYPE_NEW;
                break;
            case 'App\Events\CustomerCreatedConversation':
                $event_type = Subscription::EVENT_TYPE_NEW;
                break;
            case 'App\Events\ConversationUserChanged':
                $caused_by_user_id = $event->user->id;
                $event_type = Subscription::EVENT_TYPE_ASSIGNED;
                break;
            case 'App\Events\CustomerReplied':
                $event_type = Subscription::EVENT_TYPE_CUSTOMER_REPLIED;
                break;
        }
        if (empty($event->conversation) || !$event_type) {
            return;
        }
        $conversation = $event->conversation;

        if (!$conversation->imported) {
            // Using the last argument you can make event to be processed immediately
            Subscription::registerEvent($event_type, $conversation, $caused_by_user_id/*, true*/);
        }
    }
}
