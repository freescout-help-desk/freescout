<?php

namespace App\Http\Middleware;

use App\Subscription;
use Closure;

class TerminateHandler
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        // Process events which occurred
        Subscription::processEvents();
    }
}
