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
        $result = \Helper::checkBrowser($request);
        if ($result['msg']) {
            if (config('app.disable_browser_check')) {
                //\Log::error($result['msg']);
            } else {
                abort(403, __($result['msg'].'[display]'));
            }
        }

        return $next($request);
    }
}
