<?php

namespace DarkGhostHunter\Laraguard\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\ResponseFactory;
use DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable;

class ConfirmTwoFactorCode
{
    /**
     * The response factory instance.
     *
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    /**
     * The URL generator instance.
     *
     * @var \Illuminate\Contracts\Routing\UrlGenerator
     */
    protected $url;

    /**
     * Current user authenticated.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|\DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable
     */
    protected $user;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $response
     * @param  \Illuminate\Contracts\Routing\UrlGenerator  $url
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public function __construct(ResponseFactory $response, UrlGenerator $url, Authenticatable $user = null)
    {
        $this->response = $response;
        $this->url = $url;
        $this->user = $user;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $redirectToRoute
     * @return mixed
     */
    public function handle($request, Closure $next, $redirectToRoute = '2fa.confirm')
    {
        if ($this->userHasNotEnabledTwoFactorAuth() || $this->codeWasValidated($request)) {
            return $next($request);
        }

        return $request->expectsJson()
            ? $this->response->json(['message' => trans('laraguard::messages.required')], 403)
            : $this->response->redirectGuest($this->url->route($redirectToRoute));
    }

    /**
     * Check if the user is using Two Factor Authentication.
     *
     * @return bool
     */
    protected function userHasNotEnabledTwoFactorAuth()
    {
        return ! ($this->user instanceof TwoFactorAuthenticatable && $this->user->hasTwoFactorEnabled());
    }

    /**
     * Determine if the confirmation timeout has expired.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function codeWasValidated($request)
    {
        $confirmedAt = now()->timestamp - $request->session()->get('2fa.totp_confirmed_at', 0);

        return $confirmedAt < config('laraguard.confirm.timeout', 10800);
    }
}