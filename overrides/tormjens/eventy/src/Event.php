<?php

namespace TorMorten\Eventy;

abstract class Event
{
    /**
     * Holds the event listeners.
     *
     * @var array
     */
    protected $listeners = [];

    public function __construct()
    {

    }

    /**
     * Adds a listener.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the action
     * @param int    $arguments Number of arguments to accept
     */
    public function listen($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->listeners[$hook][] = [
            'callback'  => $callback,
            'priority'  => $priority,
            'arguments' => $arguments,
        ];
        usort($this->listeners[$hook], function ($a, $b) {
            return (int)$a['priority'] - (int)$b['priority'];
        });

        return $this;
    }

    /**
     * Removes a listener.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the action
     */
    public function remove($hook, $callback, $priority = 20)
    {
        if (isset($this->listeners[$hook])) {
            foreach ($this->listeners[$hook] as $key => $listener) {
                if ($listener['callback'] == $callback && $listener['priority'] == $priority) {
                    unset($this->listeners[$hook][$key]);
                }
            }
        }
    }

    /**
     * Remove all listeners with given hook in collection. If no hook, clear all listeners.
     *
     * @param string $hook Hook name
     */
    public function removeAll($hook = null)
    {
        if ($hook) {
            if (isset($this->listeners[$hook])) {
                unset($this->listeners[$hook]);
            }
        } else {
            $this->listeners = [];
        }
    }

    /**
     * Gets a sorted list of all listeners.
     *
     * @return array
     */
    public function getListeners($hook)
    {
        return $this->listeners[$hook] ?? [];
    }

    /**
     * Gets the function.
     *
     * @param mixed $callback Callback
     *
     * @return mixed A closure, an array if "class@method" or a string if "function_name"
     */
    protected function getFunction($callback)
    {
        if (is_string($callback) && strpos($callback, '@')) {
            $callback = explode('@', $callback);

            return [app('\\'.$callback[0]), $callback[1]];
        } elseif (is_callable($callback)) {
            return $callback;
        } else {
            throw new Exception('$callback is not a Callable', 1);
        }
    }

    /**
     * Fires a new action.
     *
     * @param string $action Name of action
     * @param array  $args   Arguments passed to the action
     */
    abstract public function fire($action, $args);
}
