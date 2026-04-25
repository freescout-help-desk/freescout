<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-822g-7rw5-53xj
 */
class TrustHosts
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The trusted hosts that have been configured to always be trusted.
     */
    protected static $trusted_hosts;


    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, $next)
    {
        if (//! $this->app->environment('local') &&
            $this->app->runningUnitTests()
        ) {
            return $next($request);
        }

        // Check if current host matches APP_URL.
        list($current_host) = explode(':', request()->getHttpHost());
        $current_host = mb_strtolower($current_host);

        $app_host = mb_strtolower(\Helper::getDomain());

        if ($current_host == $app_host) {
            return $next($request);
        }

        // Check hosts from APP_TRUSTED_HOSTS.
        $trusted_hosts = explode(',', config('app.trusted_hosts'));
        foreach ($trusted_hosts as $host) {
            $host = mb_strtolower(trim($host));
            if ($host && $current_host == $host) {
                return $next($request);
            }
        }
        
        $is_trusted_host = \Eventy::filter('app.is_trusted_host', false, $current_host);

        if ($is_trusted_host) {
            return $next($request);
        }
 
        // Throws: Untrusted Host...
        //Request::setTrustedHosts([$current_host]);
        //throw new SuspiciousOperationException(sprintf('Untrusted Host "%s".', $host));

        \Helper::denyAccess("Untrusted Host: {$current_host}. Add the following to your .env file and clear cache: APP_TRUSTED_HOSTS ='$current_host'", true);

        //return $next($request);
    }

    /**
     * Get a regular expression matching the application URL and all of its subdomains.
     *
     * @return string|null
     */
    // protected function allSubdomainsOfAppUrl()
    // {
    //     if ($host = parse_url($this->app['config']->get('app.url'), PHP_URL_HOST)) {
    //         return '^(.+\.)?'.preg_quote($host).'$';
    //     }
    // }
}
