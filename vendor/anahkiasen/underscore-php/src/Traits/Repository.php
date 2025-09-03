<?php
namespace Underscore\Traits;

use BadMethodCallException;
use Underscore\Dispatch;
use Underscore\Method;
use Underscore\Methods\ArraysMethods;
use Underscore\Methods\StringsMethods;
use Underscore\Parse;

/**
 * Base abstract class for repositories.
 */
abstract class Repository
{
    /**
     * The subject of the repository.
     *
     * @type mixed
     */
    protected $subject;

    /**
     * Custom functions.
     *
     * @type array
     */
    protected static $macros = [];

    /**
     * The method used to convert new subjects.
     *
     * @type string
     */
    protected $typecaster;

    ////////////////////////////////////////////////////////////////////
    /////////////////////////// PUBLIC METHODS /////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Create a new instance of the repository.
     *
     * @param mixed $subject The repository subject
     */
    public function __construct($subject = null)
    {
        // Assign subject
        $this->subject = $subject ?: $this->getDefault();

        // Convert it if necessary
        $typecaster = $this->typecaster;
        if ($typecaster) {
            $this->$typecaster();
        }

        return $this;
    }

    /**
     * Transform subject to Strings on toString.
     *
     * @return string
     */
    public function __toString()
    {
        return Parse::toString($this->subject);
    }

    /**
     * Create a new Repository.
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Create a new Repository from a subject.
     */
    public static function from($subject)
    {
        return new static($subject);
    }

    /**
     * Get a key from the subject.
     */
    public function __get($key)
    {
        return ArraysMethods::get($this->subject, $key);
    }

    /**
     * Set a value on the subject.
     */
    public function __set($key, $value)
    {
        $this->subject = ArraysMethods::set($this->subject, $key, $value);
    }

    /**
     * Check if the subject is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->subject);
    }

    /**
     * Replace the Subject while maintaining chain.
     *
     * @param mixed $value
     */
    public function setSubject($value)
    {
        $this->subject = $value;

        return $this;
    }

    /**
     * Get the subject from the object.
     *
     * @return mixed
     */
    public function obtain()
    {
        return $this->subject;
    }

    /**
     * Extend the class with a custom function.
     *
     * @param string   $method  The macro's name
     * @param Callable $closure The macro
     */
    public static function extend($method, $closure)
    {
        static::$macros[get_called_class()][$method] = $closure;
    }

    ////////////////////////////////////////////////////////////////////
    //////////////////////// METHODS DISPATCHING ///////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Catch aliases and reroute them to the right methods.
     */
    public static function __callStatic($method, $parameters)
    {
        // Get base class and methods class
        $callingClass = static::computeClassToCall(get_called_class(), $method, $parameters);
        $methodsClass = Method::getMethodsFromType($callingClass);

        // Defer to Methods class
        if (method_exists($methodsClass, $method)) {
            return self::callMethod($methodsClass, $method, $parameters);
        }

        // Check for an alias
        if ($alias = Method::getAliasOf($method)) {
            return self::callMethod($methodsClass, $alias, $parameters);
        }

        // Check for parsers
        if (method_exists('Underscore\Parse', $method)) {
            return self::callMethod('Underscore\Parse', $method, $parameters);
        }

        // Defered methods
        if ($defered = Dispatch::toNative($callingClass, $method)) {
            return call_user_func_array($defered, $parameters);
        }

        // Look in the macros
        if ($macro = ArraysMethods::get(static::$macros, $callingClass.'.'.$method)) {
            return call_user_func_array($macro, $parameters);
        }

        throw new BadMethodCallException('The method '.$callingClass.'::'.$method.' does not exist');
    }

    /**
     * Allow the chained calling of methods.
     */
    public function __call($method, $arguments)
    {
        // Get correct class
        $class = Dispatch::toClass($this->subject);

        // Check for unchainable methods
        if (Method::isUnchainable($class, $method)) {
            throw new BadMethodCallException('The method '.$class.'::'.$method.' can\'t be chained');
        }

        // Prepend subject to arguments and call the method
        if (!Method::isSubjectless($method)) {
            array_unshift($arguments, $this->subject);
        }
        $result = $class::__callStatic($method, $arguments);

        // If the method is a breaker, return just the result
        if (Method::isBreaker($method)) {
            return $result;
        } else {
            $this->subject = $result;
        }

        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// HELPERS //////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Tries to find the right class to call.
     *
     * @param string $callingClass The original class
     * @param string $method       The method
     * @param array  $arguments    The arguments
     *
     * @return string The correct class
     */
    protected static function computeClassToCall($callingClass, $method, $arguments)
    {
        if (!StringsMethods::find($callingClass, 'Underscore\Types')) {
            if (isset($arguments[0])) {
                $callingClass = Dispatch::toClass($arguments[0]);
            } else {
                $callingClass = Method::findInClasses($callingClass, $method);
            }
        }

        return $callingClass;
    }

    /**
     * Simpler version of call_user_func_array (for performances).
     *
     * @param string $class      The class
     * @param string $method     The method
     * @param array  $parameters The arguments
     */
    protected static function callMethod($class, $method, $parameters)
    {
        switch (count($parameters)) {
            case 0:
                return $class::$method();
            case 1:
                return $class::$method($parameters[0]);
            case 2:
                return $class::$method($parameters[0], $parameters[1]);
            case 3:
                return $class::$method($parameters[0], $parameters[1], $parameters[2]);
            case 4:
                return $class::$method($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
            case 5:
                return $class::$method($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
        }
    }

    /**
     * Get a default value for a new repository.
     *
     * @return mixed
     */
    protected function getDefault()
    {
        return '';
    }
}
