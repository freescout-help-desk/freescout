<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class TokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->user() && !empty($request->auth_token) && $request->cookie('in_app')) {
            try {
                $user = User::where(\DB::raw('md5(CONCAT(id, "'.config('app.key').'"))') , $request->auth_token)
                    ->first();
            } catch (\Exception $e) {
                \Helper::logException($e, '[TokenAuth]');
            }
            if (!empty($user)) {
                \Auth::login($user);
            }
        }
        return $next($request);
    }
}
