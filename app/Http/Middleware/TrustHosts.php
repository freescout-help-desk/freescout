<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

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
     *
     */
    protected static $trusted_hosts;

    /**
     * Indicates whether subdomains of the application URL should be trusted.
     *
     * @var bool|null
     */
    protected static $subdomains;

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
     * Get the host patterns that should be trusted.
     *
     * @return array
     */
    public function hosts()
    {
        $hosts = [];

        // Pre-populate trusted hosts only if current host does not match host in APP_URL.
        list($current_host) = explode(':', request()->getHttpHost());
        if (mb_strtolower($current_host) == mb_strtolower(\Helper::getDomain())) {
            return $hosts;
        }

        if (!self::$trusted_hosts) {
            // First add hosts from APP_TRUSTED_HOSTS.
            $trusted_hosts = explode(',', config('app.trusted_hosts'));
            foreach ($trusted_hosts as $host) {
                $host = trim($host);
                if ($host) {
                    self::$trusted_hosts[] = $host;
                }
            }
            // Add hosts via hook.
            self::$trusted_hosts = \Eventy::filter('app.trusted_hosts', self::$trusted_hosts);
        }

        if (!self::$trusted_hosts) {
            return [$this->allSubdomainsOfAppUrl()];
        }

        $hosts = self::$trusted_hosts;

        if (self::$subdomains) {
            $hosts[] = $this->allSubdomainsOfAppUrl();
        }

        return $hosts;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, $next)
    {
        if ($this->shouldSpecifyTrustedHosts()) {
            Request::setTrustedHosts(array_filter($this->hosts()));
        }

        return $next($request);
    }

    /**
     * Specify the hosts that should always be trusted.
     *
     * @param  array<int, string>|(callable(): array<int, string>)  $hosts
     * @param  bool  $subdomains
     * @return void
     */
    public static function at(array|callable $hosts, bool $subdomains = true)
    {
        self::$trusted_hosts = $hosts;
        self::$subdomains = $subdomains;
    }

    /**
     * Determine if the application should specify trusted hosts.
     *
     * @return bool
     */
    protected function shouldSpecifyTrustedHosts()
    {
        return //! $this->app->environment('local') &&
               ! $this->app->runningUnitTests();
    }

    /**
     * Get a regular expression matching the application URL and all of its subdomains.
     *
     * @return string|null
     */
    protected function allSubdomainsOfAppUrl()
    {
        if ($host = parse_url($this->app['config']->get('app.url'), PHP_URL_HOST)) {
            return '^(.+\.)?'.preg_quote($host).'$';
        }
    }

    /**
     * Flush the state of the middleware.
     *
     * @return void
     */
    public static function flushState()
    {
        self::$trusted_hosts = null;
        self::$subdomains = null;
    }
}
