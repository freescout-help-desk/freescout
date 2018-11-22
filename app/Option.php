<?php
/**
 * todo: implement caching by saving all options in one cache variable on register_shutdown_function.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    // todo: cache like in WordPress (fetch all options from DB each time)
    public static $cache = [];

    public $timestamps = false;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Set an option.
     *
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    public static function set($name, $value)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }

        // Sanitize option
        if (is_null($value)) {
            $value = '';
        }

        if (is_object($value)) {
            $value = clone $value;
        }

        $serialized_value = self::maybeSerialize($value);

        $option = self::firstOrCreate(
            ['name' => $name], ['value' => $serialized_value]
        );

        $old_value = $option['value'];

        if ($value === $old_value || self::maybeSerialize($value) === self::maybeSerialize($old_value)) {
            return false;
        }

        $option->value = $serialized_value;
        $option->save();
    }

    /**
     * Get option.
     *
     * @param string $name
     *
     * @return string
     */
    public static function get($name, $default = false, $decode = true)
    {
        // If not passed, get default value from config
        if (func_num_args() == 1) {
            $default = self::getDefault($name, $default);
        }

        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $option = self::where('name', (string) $name)->first();
        if ($option) {
            if ($decode) {
                $value = self::maybeUnserialize($option->value);
            } else {
                $value = $option->value;
            }
        } else {
            $value = $default;
        }

        self::$cache[$name] = $value;

        return $value;
    }

    public static function getDefault($option_name, $default = false)
    {
        $options = \Config::get('app.options');

        if (isset($options[$option_name]) && isset($options[$option_name]['default'])) {
            return $options[$option_name]['default'];
        } else {
            return $default;
        }
    }

    public static function isDefaultSet($option_name)
    {
        $options = \Config::get('app.options');

        return (isset($options[$option_name]) && isset($options[$option_name]['default']));
    }
    
    /**
     * Get multiple options.
     * @param  [type]  $name    [description]
     * @param  boolean $default [description]
     * @param  boolean $decode  [description]
     * @return [type]           [description]
     */
    public static function getOptions($options, $defaults = [], $decode = [])
    {
        $values = [];

        // Check in cache first
        // Return if we can get all options from cache
        foreach ($options as $name) {
            if (isset(self::$cache[$name])) {
                $values[$name] = self::$cache[$name];
            }
        }
        if (count($values) == count($options)) {
            return $values;
        } else {
            $values = []; 
        }


        $db_options = self::whereIn('name', $options)->get();
        foreach ($options as $name) {
            // If not passed, get default value from config
            if (empty($defaults[$name])) {
                $default = self::getDefault($name);
            }
            $db_option = $db_options->where('name', $name)->first();
            if ($db_option) {
                // todo: decode
                if (1 || $decode) {
                    $value = self::maybeUnserialize($db_option->value);
                } else {
                    $value = $db_option->value;
                }
            } else {
                $value = $default;
            }

            self::$cache[$name] = $value;
            $values[$name] = $value;
        }
        return $values;
    }
    public static function remove($name)
    {
        self::where('name', (string) $name)->delete();
    }

    /**
     * Serialize data, if needed.
     */
    public static function maybeSerialize($data)
    {
        if (is_array($data) || is_object($data)) {
            return serialize($data);
        }

        return $data;
    }

    /**
     * Unserialize data.
     */
    public static function maybeUnserialize($original)
    {
        if (self::isSerialized($original)) {
            try {
                $original = unserialize($original);
            } catch (\Exception $e) {
                // Do nothing
            }

            return $original;
        }

        return $original;
    }

    /**
     * Check value to find if it was serialized.
     * Serialized data is always a string.
     */
    public static function isSerialized($data, $strict = true)
    {
        // if it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
                // or else fall through
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';

                return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }

        return false;
    }

    /**
     * Get company name.
     */
    public static function getCompanyName()
    {
        return self::get('company_name', \Config::get('app.name'));
    }
}
