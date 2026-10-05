<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * True while an exception is being reported. Protects against
     * recursive reporting (an exception thrown while reporting another one).
     *
     * @var bool
     */
    protected $is_reporting = false;

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {
        // If an exception occurs while another one is being reported
        // (for example the database is down and something in the reporting
        // chain queries it), do not report it again to avoid an infinite
        // loop and gigantic log files.
        if ($this->is_reporting) {
            return;
        }

        $this->is_reporting = true;

        try {
            parent::report($exception);
        } finally {
            $this->is_reporting = false;
        }
    }

    /**
     * Get the default context variables for logging.
     *
     * Same as in Laravel 5.8+: only the user ID is added (no email).
     *
     * @return array
     */
    protected function context()
    {
        // Try to avoid reading user from DB.
        // https://github.com/freescout-help-desk/freescout/issues/5707
        $guard = Auth::guard();
        $user_id = $guard->getSession()->get($guard->getName());
        /*if (!$user_id) {
            $user_id = Auth::id();
        }*/

        try {
            return array_filter([
                'userId' => $user_id,
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
