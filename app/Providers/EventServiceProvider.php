<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Auth\Events\Registered' => [
            'App\Listeners\LogRegisteredUser',
        ],

        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
            'App\Listeners\ActivateUser',
        ],

        'Illuminate\Auth\Events\Failed' => [
            'App\Listeners\LogFailedLogin',
        ],

        'Illuminate\Auth\Events\Logout' => [
            'App\Listeners\LogSuccessfulLogout',
        ],

        'Illuminate\Auth\Events\Lockout' => [
            'App\Listeners\LogLockout',
        ],

        'Illuminate\Auth\Events\PasswordReset' => [
            'App\Listeners\LogPasswordReset',
        ],

        'App\Events\ConversationStatusChanged' => [
            'App\Listeners\UpdateMailboxCounters',
        ],

        'App\Events\ConversationUserChanged' => [
            'App\Listeners\UpdateMailboxCounters',
            'App\Listeners\SendNotificationToUsers',
        ],

        'App\Events\UserReplied' => [
             'App\Listeners\SendReplyToCustomer',
             'App\Listeners\SendNotificationToUsers',
        ],

        'App\Events\CustomerReplied' => [
            'App\Listeners\SendNotificationToUsers',
        ],

        'App\Events\UserCreatedConversation' => [
            'App\Listeners\SendReplyToCustomer',
            'App\Listeners\SendNotificationToUsers',
        ],

        'App\Events\CustomerCreatedConversation' => [
            'App\Listeners\SendAutoReply',
            'App\Listeners\SendNotificationToUsers',
        ],

        'App\Events\UserAddedNote' => [
            'App\Listeners\SendNotificationToUsers',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
