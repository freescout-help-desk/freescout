<?php

namespace App\Http\Middleware;

use Closure;

class CustomHandle
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
        // Enable/disable chat mode
        if ($request->exists('chat_mode')) {
            \Helper::setChatMode((int)$request->chat_mode);
        }

        // Hook.
        \Eventy::action('middleware.web.custom_handle', $request);

        return \Eventy::filter('middleware.web.custom_handle.response', $next($request), $request, $next);
    }
}
