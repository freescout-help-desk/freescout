<?php

namespace TorMorten\Eventy;

class Action extends Event
{
    /**
     * Filters a value.
     *
     * @param string $action Name of action
     * @param array  $args   Arguments passed to the filter
     *
     * @return string Always returns the value
     */
    public function fire($action, $args)
    {
        foreach ($this->getListeners($action) as $listener) {
            $parameters = [];
            for ($i = 0; $i < $listener['arguments']; $i++) {
                if (isset($args[$i])) {
                    $parameters[] = $args[$i];
                } else {
                    $parameters[] = null;
                }
            }
            call_user_func_array($this->getFunction($listener['callback']), $parameters);
        }
    }
}
