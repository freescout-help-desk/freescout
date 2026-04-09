<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CheckBrowser
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        // Make sure that browser supports CSP (Content Security Policy).
        if (!\Helper::isCspSupported($request->server('HTTP_USER_AGENT'))) {
            abort(403, __('Your browser does not support Content Security Policy (CSP) which is required for security. Please upgrade to a modern browser.[display]'));
        }

        return $next($request);
    }
}
