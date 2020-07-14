<?php
namespace Underscore\Methods;

/**
 * Methods to manage functions.
 */
class FunctionsMethods
{
    /**
     * An array of functions to be called X times.
     *
     * @type array
     */
    public static $canBeCalledTimes = [];

    /**
     * An array of cached function results.
     *
     * @type array
     */
    public static $cached = [];

    /**
     * An array tracking the last time a function was called.
     *
     * @type array
     */
    public static $throttle = [];

    ////////////////////////////////////////////////////////////////////
    ////////////////////////////// LIMITERS ////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Create a function that can only be called once.
     *
     * @param Callable $function The function
     *
     * @return Closure
     */
    public static function once($function)
    {
        return static::only($function, 1);
    }

    /**
     * Create a function that can only be called $times times.
     *
     * @param Callable $function
     * @param int      $times    The number of times
     *
     * @return Closure
     */
    public static function only($function, $canBeCalledTimes)
    {
        $unique = mt_rand();

        // Create a closure that check if the function was already called
        return function () use ($function, $canBeCalledTimes, $unique) {

            // Generate unique hash of the function
            $arguments = func_get_args();
            $signature = FunctionsMethods::getSignature($unique, $function, $arguments);

            // Get counter
            $numberOfTimesCalled = FunctionsMethods::hasBeenCalledTimes($signature);

            // If the function has been called too many times, cancel
            // Else, increment the count
            if ($numberOfTimesCalled >= $canBeCalledTimes) {
                return false;
            } else {
                FunctionsMethods::$canBeCalledTimes[$signature]++;
            }

            return call_user_func_array($function, $arguments);
        };
    }

    /**
     * Create a function that can only be called after $times times.
     *
     * @param Callable $function
     * @param int      $times
     *
     * @return Closure
     */
    public static function after($function, $times)
    {
        $unique = mt_rand();

        // Create a closure that check if the function was already called
        return function () use ($function, $times, $unique) {

            // Generate unique hash of the function
            $arguments = func_get_args();
            $signature = FunctionsMethods::getSignature($unique, $function, $arguments);

            // Get counter
            $called = FunctionsMethods::hasBeenCalledTimes($signature);

            // Prevent calling before a certain number
            if ($called < $times) {
                FunctionsMethods::$canBeCalledTimes[$signature] += 1;

                return false;
            }

            return call_user_func_array($function, $arguments);
        };
    }

    /**
     * Caches the result of a function and refer to it ever after.
     *
     * @param Callable $function
     *
     * @return Closure
     */
    public static function cache($function)
    {
        $unique = mt_rand();

        return function () use ($function, $unique) {

            // Generate unique hash of the function
            $arguments = func_get_args();
            $signature = FunctionsMethods::getSignature($unique, $function, $arguments);

            if (isset(FunctionsMethods::$cached[$signature])) {
                return FunctionsMethods::$cached[$signature];
            }

            $result = call_user_func_array($function, $arguments);
            FunctionsMethods::$cached[$signature] = $result;

            return $result;
        };
    }

    /**
     * Only allow a function to be called every X ms.
     *
     * @param Callable $function
     * @param int      $ms
     *
     * @return Closure
     */
    public static function throttle($function, $ms)
    {
        $unique = mt_rand();

        return function () use ($function, $ms, $unique) {

            // Generate unique hash of the function
            $arguments = func_get_args();
            $signature = FunctionsMethods::getSignature($unique, $function, $arguments);

            // Check last called time and update it if necessary
            $last = FunctionsMethods::getLastCalledTime($signature);
            $difference = time() - $last;

            // Execute the function if the conditions are here
            if ($last === time() or $difference > $ms) {
                FunctionsMethods::$throttle[$signature] = time();

                return call_user_func_array($function, $arguments);
            }

            return false;
        };
    }

    /**
     * Prefill function arguments.
     *
     * @param Callable $func
     *
     * @return Closure
     *
     * @author Jeremy Ashkenas
     */
    public static function partial(callable $func)
    {
        $boundArgs = array_slice(func_get_args(), 1);

        return function () use ($boundArgs, $func) {
            $args = [];
            $calledArgs = func_get_args();
            $position = 0;

            for ($i = 0, $len = count($boundArgs); $i < $len; $i++) {
                $args[] = $boundArgs[$i] === null ? $calledArgs[$position++] : $boundArgs[$i];
            }

            return call_user_func_array($func, array_merge($args, array_slice($calledArgs, $position)));
        };
    }

    ////////////////////////////////////////////////////////////////////
    ////////////////////////////// HELPERS /////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Get the last time a function was called.
     *
     * @param string $unique The function unique ID
     *
     * @return int
     */
    public static function getLastCalledTime($unique)
    {
        return ArraysMethods::setAndGet(static::$canBeCalledTimes, $unique, time());
    }

    /**
     * Get the number of times a function has been called.
     *
     * @param string $unique The function unique ID
     *
     * @return int
     */
    public static function hasBeenCalledTimes($unique)
    {
        return ArraysMethods::setAndGet(static::$canBeCalledTimes, $unique, 0);
    }

    /**
     * Get a function's signature.
     *
     * @param Closure $function  The function
     * @param array   $arguments Its arguments
     *
     * @return string The unique id
     */
    public static function getSignature($unique, $function, $arguments)
    {
        $function = var_export($function, true);
        $arguments = var_export($arguments, true);

        return md5($unique.'_'.$function.'_'.$arguments);
    }
}
