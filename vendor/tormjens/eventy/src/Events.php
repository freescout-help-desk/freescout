<?php

namespace TorMorten\Eventy;

class Events
{
    /**
     * Holds all registered actions.
     *
     * @var TorMorten\Events\Action
     */
    protected $action;

    /**
     * Holds all registered filters.
     *
     * @var TorMorten\Events\Filter
     */
    protected $filter;

    /**
     * Construct the class.
     */
    public function __construct()
    {
        $this->action = new Action();
        $this->filter = new Filter();
    }

    /**
     * Get the action instance.
     *
     * @return TorMorten\Events\Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the action instance.
     *
     * @return TorMorten\Events\Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Add an action.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the action
     * @param int    $arguments Number of arguments to accept
     */
    public function addAction($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->action->listen($hook, $callback, $priority, $arguments);
    }

    /**
     * Remove an action.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the action
     */
    public function removeAction($hook, $callback, $priority = 20)
    {
        $this->action->remove($hook, $callback, $priority);
    }

    /**
     * Remove all actions.
     *
     * @param string $hook Hook name
     */
    public function removeAllActions($hook = null)
    {
        $this->action->removeAll($hook);
    }

    /**
     * Adds a filter.
     *
     * @param string $hook      Hook name
     * @param mixed  $callback  Function to execute
     * @param int    $priority  Priority of the action
     * @param int    $arguments Number of arguments to accept
     */
    public function addFilter($hook, $callback, $priority = 20, $arguments = 1)
    {
        $this->filter->listen($hook, $callback, $priority, $arguments);
    }

    /**
     * Remove a filter.
     *
     * @param string $hook     Hook name
     * @param mixed  $callback Function to execute
     * @param int    $priority Priority of the action
     */
    public function removeFilter($hook, $callback, $priority = 20)
    {
        $this->filter->remove($hook, $callback, $priority);
    }

    /**
     * Remove all filters.
     *
     * @param string $hook Hook name
     */
    public function removeAllFilters($hook = null)
    {
        $this->filter->removeAll($hook);
    }

    /**
     * Set a new action.
     *
     * Actions never return anything. It is merely a way of executing code at a specific time in your code.
     *
     * You can add as many parameters as you'd like.
     *
     * @param string $action     Name of hook
     * @param mixed  $parameter1 A parameter
     * @param mixed  $parameter2 Another parameter
     *
     * @return void
     */
    public function action()
    {
        $args = func_get_args();
        $hook = $args[0];
        unset($args[0]);
        $args = array_values($args);
        $this->action->fire($hook, $args);
    }

    /**
     * Set a new filter.
     *
     * Filters should always return something. The first parameter will always be the default value.
     *
     * You can add as many parameters as you'd like.
     *
     * @param string $action     Name of hook
     * @param mixed  $value      The original filter value
     * @param mixed  $parameter1 A parameter
     * @param mixed  $parameter2 Another parameter
     *
     * @return void
     */
    public function filter()
    {
        $args = func_get_args();
        $hook = $args[0];
        unset($args[0]);
        $args = array_values($args);

        return $this->filter->fire($hook, $args);
    }
}
