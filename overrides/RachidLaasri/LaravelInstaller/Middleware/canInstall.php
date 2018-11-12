<?php

namespace RachidLaasri\LaravelInstaller\Middleware;

use Closure;
use DB;
use Redirect;

class canInstall
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param Redirector               $redirect
     *
     * @return mixed
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if ($this->alreadyInstalled()) {
            $installedRedirect = config('installer.installedAlreadyAction');

            switch ($installedRedirect) {

                case 'route':
                    $routeName = config('installer.installed.redirectOptions.route.name');
                    $data = config('installer.installed.redirectOptions.route.message');

                    return redirect()->route($routeName)->with(['data' => $data]);
                    break;

                case 'abort':
                    abort(config('installer.installed.redirectOptions.abort.type'));
                    break;

                case 'dump':
                    $dump = config('installer.installed.redirectOptions.dump.data');
                    dd($dump);
                    break;

                case '404':
                case 'default':
                default:
                    abort(404);
                    break;
            }
        }

        return $next($request);
    }

    /**
     * If application is already installed.
     *
     * @return bool
     */
    public function alreadyInstalled()
    {
        // If file exists, the app is 100% installed
        if (file_exists(storage_path('.installed'))) {
            return true;
        }

        // If there is no file, make extra checks
        // If config is cached env() will always return empty
        if (config('app.url') && config('app.key')
            && config('database.default') && config('database.connections.mysql.host')
            && config('database.connections.mysql.port') && config('database.connections.mysql.database')
            && config('database.connections.mysql.username') && config('database.connections.mysql.password')
        ) {
            // Check DB connection
            try {
                \DB::connection()->getPdo();
            } catch (\Exception $e) {
                return false;
            }

            // Allow to access the last installation page
            if (\Request::is('install/database') || \Request::is('install/final')) {
                return false;
            }

            return true;
        } else {
            return false;
        }
        //return file_exists(storage_path('installed'));
    }
}
