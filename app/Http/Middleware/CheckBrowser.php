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
        $user_agent = $request->server('HTTP_USER_AGENT') ?? '';

        $allowed_user_agents = explode('|', strtolower(config('app.allowed_user_agents') ?? ''));

        if (in_array(strtolower($user_agent), $allowed_user_agents)) {
            return $next($request);
        }

        // Make sure that browser supports CSP (Content Security Policy).
        if (!\Helper::isCspSupported($user_agent)) {
            \Log::error('It was detected that your browser does not support Content Security Policy (CSP). Please provide here https://github.com/freescout-help-desk/freescout/issues/5331 your User-Agent: '.$user_agent);
            //abort(403, __('Your browser does not support Content Security Policy (CSP) which is required for security. Please upgrade to a modern browser.[display]'));
        }

        return $next($request);
    }
}
