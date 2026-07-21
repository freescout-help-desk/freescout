<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

/**
 * On regular pages CSP is added via <meta> tag.
 * For /ajax-html/ requests CSP is added as a response header.
 */
class ContentSecurityPolicy
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
        $response = $next($request);

        if (str_contains($request->path(), '/ajax-html/')) {
            // ->header() only exists on Laravel's response wrappers, not a
            // plain Symfony Response/BinaryFileResponse - ->headers->set()
            // works on any response type this middleware might see
            // (gemini-code-assist review, PR #23).
            $response->headers->set('Content-Security-Policy', \Helper::getCspValue());
        }

        return $response;
    }
}
