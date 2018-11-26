<?php

namespace App\Http\Middleware;

use Closure;

class Localize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Interface language is set automatically, as locale is stored in .env file.
        
        // Set user language if user logged in.
        if (session('user_locale')) {
            // Keep in mind that this also dynamically changes config('app.locale'),
            // so we have to remember current locale in session in case we need it.
            session()->put('app_locale', config('app.locale'));
            app()->setLocale(session('user_locale'));
        }

        return $next($request);
    }
}
