<?php

return [

    /*
    |-------------------
    | Default Notification Subscriptions
    |----------------------
    |
    | This file is for configuring the default notification subscriptions for new users.
    |
    */

    'defaults' => [
        \App\Subscription::MEDIUM_EMAIL => [
            \App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
            \App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED,
            //\App\Subscription::EVENT_MY_TEAM_MENTIONED,
            \App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
            \App\Subscription::EVENT_USER_REPLIED_TO_MY,
        ],
        \App\Subscription::MEDIUM_BROWSER => [
            \App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
            \App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED,
            //\App\Subscription::EVENT_MY_TEAM_MENTIONED,
            \App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
            \App\Subscription::EVENT_USER_REPLIED_TO_MY,
        ],
    ],

];
