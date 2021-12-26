<?php

namespace App\Http\Middleware;

use Closure;

class Localize
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Interface language is set automatically, as locale is stored in .env file.

        // Set user language if user logged in.
        $user_locale = \Eventy::filter('locale', session('user_locale'));
        if ($user_locale) {
            \Helper::setLocale($user_locale);
        }

        return $next($request);
    }
}
