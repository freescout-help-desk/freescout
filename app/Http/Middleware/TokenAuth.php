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
        // This is needed to restore authentication when app session expires.
        if (!$request->user() && !empty($request->auth_token) && \Helper::isInApp($request)) {
        
            // Decode token (format: urlencode(base64_encode(user_id:expiry:hash)))
            $parts = explode(':', urldecode(base64_decode($request->auth_token)));
            if (count($parts) !== 3) {
                return $next($request);
            }
            list($user_id, $expiry, $token_hash) = $parts;

            // Check expiration.
            if (time() > (int)$expiry) {
                return $next($request);
            }

            // Get user.
            $user = User::find($user_id);
            if (!$user) {
                return $next($request);
            }

            // Verify hash.
            $hash = hash_hmac('sha256', $user_id.':'.$expiry, config('app.key').$user->password);

            if (hash_equals($hash, $token_hash)) {
                \Auth::login($user);
            }
        }
        return $next($request);
    }
}
