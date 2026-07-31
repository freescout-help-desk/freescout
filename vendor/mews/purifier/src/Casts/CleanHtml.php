<?php

namespace Mews\Purifier\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CleanHtml implements CastsAttributes
{
    use WithConfig;

    /**
     * Clean the HTML when casting the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        return clean($value, $this->config);
    }

    /**
     * Prepare the given value for storage by cleaning the HTML.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return clean($value, $this->config);
    }
}
