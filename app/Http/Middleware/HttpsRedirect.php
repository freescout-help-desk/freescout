<?php

/**
 * Redirect to HTTPS if force_redirect is enabled.
 * 
 * https://stackoverflow.com/questions/28402726/laravel-5-redirect-to-https
 */
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

class HttpsRedirect {

    /**
     * The current proxy header mappings.
     *
     * @var array
     */
    // protected $headers = [
    //     Request::HEADER_FORWARDED         => 'FORWARDED',
    //     Request::HEADER_X_FORWARDED_FOR   => 'X_FORWARDED_FOR',
    //     Request::HEADER_X_FORWARDED_HOST  => 'X_FORWARDED_HOST',
    //     Request::HEADER_X_FORWARDED_PORT  => 'X_FORWARDED_PORT',
    //     Request::HEADER_X_FORWARDED_PROTO => 'X_FORWARDED_PROTO',
    // ];

    public function handle($request, Closure $next)
    {
        if (\Helper::isHttps()) {
            //$request->setTrustedProxies( [ $request->getClientIp() ], array_keys($this->headers)); 

            if (//!$request->secure()
                !in_array(strtolower($_SERVER['X_FORWARDED_PROTO'] ?? ''), array('https', 'on', 'ssl', '1'), true)
                && strtolower($_SERVER['HTTPS'] ?? '') != 'on' 
                && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') != 'https'
                && ($_SERVER['HTTP_CF_VISITOR'] ?? '') != '{"scheme":"https"}'
            ) {
                return redirect()->secure($request->getRequestUri());
            }
        }

        // Correct protocol in $_SERVER
        if (\Helper::isHttps() 
            //&& !$request->secure() 
            && strtolower($_SERVER['HTTPS'] ?? '') != 'on'
        ) {
            $_SERVER['HTTPS'] = 'on';
        }
        return $next($request);
    }
}