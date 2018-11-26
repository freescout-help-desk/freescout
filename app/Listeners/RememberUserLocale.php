<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class RememberUserLocale
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
     *
     * @param Login $event
     *
     * @return void
     */
    public function handle(Login $event)
    {
        // Save user locale to session to show user app in his chosen language.
        session()->put('user_locale', $event->user->getLocale());
    }
}
