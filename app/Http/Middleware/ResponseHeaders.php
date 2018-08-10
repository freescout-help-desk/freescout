<?php

namespace App\Http\Middleware;

use Closure;

class ResponseHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Disable caching
        $response->header('Pragma', 'no-cache');
        $response->header('Cache-Control', 'no-cache, max-age=0, must-revalidate, no-store');

        return $response;
    }
}
