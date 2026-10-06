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
     * Classes/namespaces whose stack frame arguments may contain DB credentials
     * (host, username and password are passed as arguments when connecting).
     *
     * @var array
     */
    protected static $sensitive_frame_prefixes = [
        'Illuminate\Database\Connectors\\',
        'Doctrine\DBAL\Driver\\',
        'PDO',
    ];

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
            // Do not write DB credentials from the stack trace to the logs.
            $this->maskSensitiveTraceArgs($exception);

            parent::report($exception);
        } finally {
            $this->is_reporting = false;
        }
    }

    /**
     * Replace string arguments (which may contain DB host, username and
     * password) of the sensitive stack frames with asterisks (the first
     * 2 symbols are left untouched), so that they
     * are not written to the logs. Applies to the whole chain of previous
     * exceptions.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    protected function maskSensitiveTraceArgs($exception)
    {
        try {
            $property = new \ReflectionProperty(\Exception::class, 'trace');
            $property->setAccessible(true);

            $depth = 0;
            while ($exception && $depth < 20) {
                $trace = $property->getValue($exception);

                if (is_array($trace)) {
                    $changed = false;
                    foreach ($trace as $i => $frame) {
                        if (empty($frame['args']) || !is_array($frame['args'])) {
                            continue;
                        }
                        $class = isset($frame['class']) ? $frame['class'] : '';
                        foreach (self::$sensitive_frame_prefixes as $prefix) {
                            if ($class !== '' && strpos($class, $prefix) === 0) {
                                foreach ($frame['args'] as $arg_index => $arg) {
                                    if (is_string($arg)) {
                                        $trace[$i]['args'][$arg_index] = $this->maskString($arg);
                                    }
                                }
                                $changed = true;
                                break;
                            }
                        }
                    }
                    if ($changed) {
                        $property->setValue($exception, $trace);
                    }
                }

                $exception = $exception->getPrevious();
                $depth++;
            }
        } catch (Throwable $e) {
            // Do nothing.
        }
    }

    /**
     * Mask a string: keep the first 2 symbols and replace the rest with asterisks.
     * Strings of 2 symbols or less are masked completely.
     *
     * @param string $string
     *
     * @return string
     */
    protected function maskString($string)
    {
        if (mb_strlen($string) <= 2) {
            return '***';
        }

        return mb_substr($string, 0, 2).'***';
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
