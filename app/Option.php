<?php
/**
 * todo: implement caching by saving all options in one cache variable on register_shutdown_function
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    // todo: cache in file (see WordPress)
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
     * @param   string $name
     * @param   string $value
     * @return  boolean
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

        $option = Option::firstOrCreate(
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
     * @param  string  $name
     * @return string
     */
    public static function get($name, $default = false, $decode = true)
    {
        // if ($cache && isset(self::$cache[$name])) {
        //     return self::$cache[$name];
        // }

        $option = Option::where('name', (string)$name)->first();
        if ($option) {
            if ($decode) {
                $value = self::maybeUnserialize($option->value);
            } else {
                $value = $option->value;
            }
        } else {
            $value = $default;
        }

        // if ($cache) {
        //     self::$cache[$name] = $value;
        // }

        return $value;
    }

    public static function remove($name)
    {
        Option::where('name', (string)$name)->delete();
    }

    /**
     * Serialize data, if needed.
     */
    public static function maybeSerialize($data) {
        if (is_array($data) || is_object($data)) {
            return serialize( $data );
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
    public static function isSerialized($data, $strict = true) {
        // if it isn't a string, it isn't serialized.
        if (!is_string($data )) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (strlen( $data ) < 4) {
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
            $brace     = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace)
                return false;
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3)
                return false;
            if (false !== $brace && $brace < 4)
                return false;
        }
        $token = $data[0];
        switch ($token) {
            case 's' :
                if ($strict) {
                    if ('"' !== substr( $data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos( $data, '"')) {
                    return false;
                }
                // or else fall through
            case 'a' :
            case 'O' :
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
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
