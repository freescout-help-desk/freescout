<?php

namespace App\Http\Middleware;

use Closure;

class ResponseHeaders
{
    private $unwanted_headers = [
        'X-Powered-By',
        'Server',
    ];

    public function handle($request, Closure $next)
    {
        $this->removeUnwantedHeaders($this->unwanted_headers);

        $response = $next($request);

        // Secure headers.
        //$response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // $response->headers->set('X-XSS-Protection', '1; mode=block');
        // $response->headers->set('X-Frame-Options', 'DENY');
        //$response->headers->set('Content-Security-Policy', "style-src 'self'");
        
        // Instruct browsers to always use HTTPS for all future requests.
        if (\Helper::isHttps()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Disable caching.
        if (method_exists($response, 'header')) {
            $response->header('Pragma', 'no-cache');
            $response->header('Cache-Control', 'no-cache, max-age=0, must-revalidate, no-store');
        }

        return $response;
    }

    private function removeUnwantedHeaders($headerList)
    {
        // Once output has started (for example a PHP notice was printed), header_remove()
        // can no longer change anything and only raises a warning, which would turn the
        // whole request into an error.
        if (headers_sent()) {
            return;
        }

        foreach ($headerList as $header) {
            header_remove($header);
        }
    }
}
