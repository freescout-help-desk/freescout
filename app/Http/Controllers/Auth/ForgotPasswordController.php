<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Cache\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Lockout;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    // Max reset password attempts per period.
    const THROTTLE_ATTEMPTS = 5;

    // Minutes
    const THROTTLE_PERIOD = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-jvmv-2qcp-7855
        if ($this->hasTooManyResetEmailAttempts($request)) {
            event(new Lockout($request));
            return $this->sendLockoutResponse($request);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        if ($response !== Password::RESET_LINK_SENT) {
            $this->incrementResetEmailAttempts($request);
            //return $this->sendResetLinkFailedResponse($request, $response);
        }

        $this->clearResetEmailAttempts($request);

        // For security purposes always return an identical response regardless of whether the email exists.
        // return $response == Password::RESET_LINK_SENT
        //             ? $this->sendResetLinkResponse($response)
        //             : $this->sendResetLinkFailedResponse($request, $response);
        return $this->sendResetLinkResponse(__('If an account exists for this email, you will receive a password reset link.'));
    }

    protected function hasTooManyResetEmailAttempts(Request $request)
    {
        return app(RateLimiter::class)->tooManyAttempts(
            $this->throttleKey($request), 
            self::THROTTLE_ATTEMPTS
        );
    }

    protected function incrementResetEmailAttempts(Request $request)
    {
        app(RateLimiter::class)->hit(
            $this->throttleKey($request), 
            self::THROTTLE_PERIOD
        );
    }

    protected function clearResetEmailAttempts(Request $request)
    {
        app(RateLimiter::class)->clear($this->throttleKey($request));
    }

    protected function throttleKey(Request $request)
    {
        return strtolower($request->ip()) . '|reset_password';
    }

    protected function sendLockoutResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', ['seconds' => self::THROTTLE_PERIOD*60])],
        ])->status(423);
    }
}
