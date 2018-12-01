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
        $user_locale = session('user_locale');
        if ($user_locale) {
            // app()->setLocale() also dynamically changes config('app.locale'),
            // so we have to remember current locale in real_locale parameter.
            \Config::set('app.real_locale', config('app.locale'));
            app()->setLocale($user_locale);
        }

        return $next($request);
    }
}
