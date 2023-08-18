<?php

namespace TorMorten\Eventy;

class Filter extends Event
{
    protected $value = '';

    /**
     * Filters a value.
     *
     * @param string $action Name of filter
     * @param array  $args   Arguments passed to the filter
     *
     * @return string Always returns the value
     */
    public function fire($action, $args)
    {
        $this->value = isset($args[0]) ? $args[0] : ''; // get the value, the first argument is always the value
        foreach ($this->getListeners($action) as $listener) {
            $parameters = [];
            $args[0] = $this->value;
            for ($i = 0; $i < $listener['arguments']; $i++) {
                $value = $args[$i] ?? null;
                $parameters[] = $value;
            }
            $this->value = call_user_func_array($this->getFunction($listener['callback']), $parameters);
        }

        return $this->value;
    }
}
