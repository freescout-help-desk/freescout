<?php

/**
 * Redirect to HTTPS if force_redirect is enabled.
 *
 * https://stackoverflow.com/questions/28402726/laravel-5-redirect-to-https
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class HttpsRedirect
{
    public function handle($request, Closure $next)
    {
        if (\Config::get('app.force_https') == 'true') {
            $request->setTrustedProxies([$request->getClientIp()]);
            if (!$request->secure() /*&& App::environment() === 'production'*/) {
                return redirect()->secure($request->getRequestUri());
            }
        }

        return $next($request);
    }
}
