<?php

namespace App\Http\Middleware;

use Closure;

class FrameGuard
{
    /**
     * Handle the given request and get the response.
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $x_frame_options = config('app.x_frame_options');

        if (false !== $x_frame_options 
            && $x_frame_options !== 'false'
            && $x_frame_options !== '0'
        ) {
            $value = 'SAMEORIGIN';

            if (is_string($x_frame_options) && preg_match("#(DENY|SAMEORIGIN|ALLOW-FROM)#i", $x_frame_options)) {
                $value = $x_frame_options;
            }
            $response->headers->set('X-Frame-Options', $value, false);
        }

        return $response;
    }
}
