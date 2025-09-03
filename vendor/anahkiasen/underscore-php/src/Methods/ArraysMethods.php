<?php
namespace Underscore\Methods;

use Closure;

/**
 * Methods to manage arrays.
 */
class ArraysMethods extends CollectionMethods
{
    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// GENERATE /////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Generate an array from a range.
     *
     * @param int $_base The base number
     * @param int $stop  The stopping point
     * @param int $step  How many to increment of
     *
     * @return array
     */
    public static function range($_base, $stop = null, $step = 1)
    {
        // Dynamic arguments
        if (!is_null($stop)) {
            $start = $_base;
        } else {
            $start = 1;
            $stop = $_base;
        }

        return range($start, $stop, $step);
    }

    /**
     * Fill an array with $times times some $data.
     *
     * @param mixed $data
     * @param int   $times
     *
     * @return array
     */
    public static function repeat($data, $times)
    {
        $times = abs($times);
        if ($times === 0) {
            return [];
        }

        return array_fill(0, $times, $data);
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// ANALYZE //////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Search for the index of a value in an array.
     *
     * @param array  $array
     * @param string $value
     *
     * @return mixed
     */
    public static function search($array, $value)
    {
        return array_search($value, $array, true);
    }

    /**
     * Check if all items in an array match a truth test.
     *
     * @param array    $array
     * @param callable $closure
     *
     * @return bool
     */
    public static function matches($array, Closure $closure)
    {
        // Reduce the array to only booleans
        $array = (array) static::each($array, $closure);

        // Check the results
        if (count($array) === 0) {
            return true;
        }
        $array = array_search(false, $array, false);

        return is_bool($array);
    }

    /**
     * Check if any item in an array matches a truth test.
     *
     * @param array    $array
     * @param callable $closure
     *
     * @return bool
     */
    public static function matchesAny($array, Closure $closure)
    {
        // Reduce the array to only booleans
        $array = (array) static::each($array, $closure);

        // Check the results
        if (count($array) === 0) {
            return true;
        }
        $array = array_search(true, $array, false);

        return is_int($array);
    }

    /**
     * Check if an item is in an array.
     */
    public static function contains($array, $value)
    {
        return in_array($value, $array, true);
    }

    /**
     * Returns the average value of an array.
     *
     * @param array $array    The source array
     * @param int   $decimals The number of decimals to return
     *
     * @return int The average value
     */
    public static function average($array, $decimals = 0)
    {
        return round((array_sum($array) / count($array)), $decimals);
    }

    /**
     * Get the size of an array.
     */
    public static function size($array)
    {
        return count($array);
    }

    /**
     * Get the max value from an array.
     *
     * @param array        $array
     * @param Closure|null $closure
     *
     * @return mixed
     */
    public static function max($array, $closure = null)
    {
        // If we have a closure, apply it to the array
        if ($closure) {
            $array = static::each($array, $closure);
        }

        return max($array);
    }

    /**
     * Get the min value from an array.
     *
     * @param array        $array
     * @param Closure|null $closure
     *
     * @return mixed
     */
    public static function min($array, $closure = null)
    {
        // If we have a closure, apply it to the array
        if ($closure) {
            $array = static::each($array, $closure);
        }

        return min($array);
    }

    ////////////////////////////////////////////////////////////////////
    //////////////////////////// FETCH FROM ////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Find the first item in an array that passes the truth test.
     */
    public static function find($array, Closure $closure)
    {
        foreach ($array as $key => $value) {
            if ($closure($value, $key)) {
                return $value;
            }
        }

        return;
    }

    /**
     * Clean all falsy values from an array.
     */
    public static function clean($array)
    {
        return static::filter($array, function ($value) {
            return (bool) $value;
        });
    }

    /**
     * Get a random string from an array.
     */
    public static function random($array, $take = null)
    {
        if (!$take) {
            return $array[array_rand($array)];
        }

        shuffle($array);

        return static::first($array, $take);
    }

    /**
     * Return an array without all instances of certain values.
     */
    public static function without()
    {
        $arguments = func_get_args();
        $array = array_shift($arguments);
        // if singular argument and is an array treat this AS the array to run without agains
        if (is_array($arguments[0]) && count($arguments) === 1) {
            $arguments = $arguments[0];
        }

        return static::filter($array, function ($value) use ($arguments) {
            return !in_array($value, $arguments, true);
        });
    }

    /**
     * Return an array with all elements found in both input arrays.
     */
    public static function intersection($a, $b)
    {
        $a = (array) $a;
        $b = (array) $b;

        return array_values(array_intersect($a, $b));
    }

    /**
     * Return a boolean flag which indicates whether the two input arrays have any common elements.
     */
    public static function intersects($a, $b)
    {
        $a = (array) $a;
        $b = (array) $b;

        return count(self::intersection($a, $b)) > 0;
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// SLICERS //////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Get the first value from an array.
     */
    public static function first($array, $take = null)
    {
        if (!$take) {
            return array_shift($array);
        }

        return array_splice($array, 0, $take, true);
    }

    /**
     * Get the last value from an array.
     */
    public static function last($array, $take = null)
    {
        if (!$take) {
            return array_pop($array);
        }

        return static::rest($array, -$take);
    }

    /**
     * Get everything but the last $to items.
     */
    public static function initial($array, $to = 1)
    {
        $slice = count($array) - $to;

        return static::first($array, $slice);
    }

    /**
     * Get the last elements from index $from.
     */
    public static function rest($array, $from = 1)
    {
        return array_splice($array, $from);
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// ACT UPON /////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Iterate over an array and execute a callback for each loop.
     */
    public static function at($array, Closure $closure)
    {
        foreach ($array as $key => $value) {
            $closure($value, $key);
        }

        return $array;
    }

    ////////////////////////////////////////////////////////////////////
    ////////////////////////////// ALTER ///////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Replace a value in an array.
     *
     * @param array  $array   The array
     * @param string $replace The string to replace
     * @param string $with    What to replace it with
     *
     * @return array
     */
    public static function replaceValue($array, $replace, $with)
    {
        return static::each($array, function ($value) use ($replace, $with) {
            return str_replace($replace, $with, $value);
        });
    }

    /**
     * Replace the keys in an array with another set.
     *
     * @param array $array The array
     * @param array $keys  An array of keys matching the array's size
     *
     * @return array
     */
    public static function replaceKeys($array, $keys)
    {
        $values = array_values($array);

        return array_combine($keys, $values);
    }

    /**
     * Iterate over an array and modify the array's value.
     */
    public static function each($array, Closure $closure)
    {
        foreach ($array as $key => $value) {
            $array[$key] = $closure($value, $key);
        }

        return $array;
    }

    /**
     * Shuffle an array.
     */
    public static function shuffle($array)
    {
        shuffle($array);

        return $array;
    }

    /**
     * Sort an array by key.
     */
    public static function sortKeys($array, $direction = 'ASC')
    {
        $direction = (strtolower($direction) === 'desc') ? SORT_DESC : SORT_ASC;
        if ($direction === SORT_ASC) {
            ksort($array);
        } else {
            krsort($array);
        }

        return $array;
    }

    /**
     * Implodes an array.
     *
     * @param array  $array The array
     * @param string $with  What to implode it with
     *
     * @return String
     */
    public static function implode($array, $with = '')
    {
        return implode($with, $array);
    }

    /**
     * Find all items in an array that pass the truth test.
     */
    public static function filter($array, $closure = null)
    {
        if (!$closure) {
            return static::clean($array);
        }

        return array_filter($array, $closure);
    }

    /**
     * Flattens an array to dot notation.
     *
     * @param array  $array     An array
     * @param string $separator The characater to flatten with
     * @param string $parent    The parent passed to the child (private)
     *
     * @return array Flattened array to one level
     */
    public static function flatten($array, $separator = '.', $parent = null)
    {
        if (!is_array($array)) {
            return $array;
        }

        $_flattened = [];

        // Rewrite keys
        foreach ($array as $key => $value) {
            if ($parent) {
                $key = $parent.$separator.$key;
            }
            $_flattened[$key] = static::flatten($value, $separator, $key);
        }

        // Flatten
        $flattened = [];
        foreach ($_flattened as $key => $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $value);
            } else {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Invoke a function on all of an array's values.
     */
    public static function invoke($array, $callable, $arguments = [])
    {
        // If one argument given for each iteration, create an array for it
        if (!is_array($arguments)) {
            $arguments = static::repeat($arguments, count($array));
        }

        // If the callable has arguments, pass them
        if ($arguments) {
            return array_map($callable, $array, $arguments);
        }

        return array_map($callable, $array);
    }

    /**
     * Return all items that fail the truth test.
     */
    public static function reject($array, Closure $closure)
    {
        $filtered = [];

        foreach ($array as $key => $value) {
            if (!$closure($value, $key)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Remove the first value from an array.
     */
    public static function removeFirst($array)
    {
        array_shift($array);

        return $array;
    }

    /**
     * Remove the last value from an array.
     */
    public static function removeLast($array)
    {
        array_pop($array);

        return $array;
    }

    /**
     * Removes a particular value from an array (numeric or associative).
     *
     * @param string $array
     * @param string $value
     *
     * @return array
     */
    public static function removeValue($array, $value)
    {
        $isNumericArray = true;
        foreach ($array as $key => $item) {
            if ($item === $value) {
                if (!is_int($key)) {
                    $isNumericArray = false;
                }
                unset($array[$key]);
            }
        }
        if ($isNumericArray) {
            $array = array_values($array);
        }

        return $array;
    }

    /**
     * Prepend a value to an array.
     */
    public static function prepend($array, $value)
    {
        array_unshift($array, $value);

        return $array;
    }

    /**
     * Append a value to an array.
     */
    public static function append($array, $value)
    {
        array_push($array, $value);

        return $array;
    }
}
