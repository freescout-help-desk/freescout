<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\User::class         => \App\Policies\UserPolicy::class,
        \App\Mailbox::class      => \App\Policies\MailboxPolicy::class,
        \App\Folder::class       => \App\Policies\FolderPolicy::class,
        \App\Conversation::class => \App\Policies\ConversationPolicy::class,
        \App\Thread::class       => \App\Policies\ThreadPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
