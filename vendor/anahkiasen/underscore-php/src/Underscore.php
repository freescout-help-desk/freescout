<?php
namespace Underscore;

use Underscore\Methods\ArraysMethods;
use Underscore\Traits\Repository;

/**
 * The base class and wrapper around all other classes.
 */
class Underscore extends Repository
{
    /**
     * The current config.
     *
     * @type array
     */
    protected static $options;

    ////////////////////////////////////////////////////////////////////
    //////////////////////////// INTERFACE /////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Dispatch to the correct Repository class.
     *
     * @param mixed $subject The subject
     *
     * @return Repository
     */
    public static function from($subject)
    {
        $class = Dispatch::toClass($subject);

        return $class::from($subject);
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// HELPERS //////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Get an option from the config file.
     *
     * @param string $option The key of the option
     *
     * @return mixed Its value
     */
    public static function option($option)
    {
        // Get config file
        if (!static::$options) {
            static::$options = include __DIR__.'/../config/config.php';
        }

        return ArraysMethods::get(static::$options, $option);
    }
}
