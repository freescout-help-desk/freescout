<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Cache\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Lockout;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    // Max reset password attempts per period.
    const THROTTLE_ATTEMPTS = 5;

    // Minutes
    const THROTTLE_PERIOD = 1;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

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
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        if ($this->hasTooManyResetAttempts($request)) {
            event(new Lockout($request));
            return $this->sendLockoutResponse($request);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            $this->clearResetAttempts($request);
        } else {
            $this->incrementResetAttempts($request);
            // When user not found and token is invalid return same error for security reasons.
            $response = Password::INVALID_USER;
        }

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        //$this->guard()->login($user);
    }

    protected function hasTooManyResetAttempts(Request $request)
    {
        return app(RateLimiter::class)->tooManyAttempts(
            $this->throttleKey($request), 
            self::THROTTLE_ATTEMPTS
        );
    }

    protected function incrementResetAttempts(Request $request)
    {
        app(RateLimiter::class)->hit(
            $this->throttleKey($request), 
            self::THROTTLE_PERIOD
        );
    }

    protected function clearResetAttempts(Request $request)
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
