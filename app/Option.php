<?php
/**
 * todo: implement caching by saving all options in one cache variable on register_shutdown_function.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
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
        $option = self::firstOrCreate(
            ['name' => $name], ['value' => $value]
        );
        if ($option['value'] != $value) {
            $option->value = $value;
            $option->save();
        }
    }

    /**
     * Get option.
     *
     * @param string $name
     *
     * @return string
     */
    public static function get($name, $default = false)
    {
        $option = self::where('name', (string) $name)->first();
        if ($option) {
            return $option->value;
        } else {
            return $default;
        }
    }
}
