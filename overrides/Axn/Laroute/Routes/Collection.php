<?php
/**
 * Overriding vendor class to generate routes for modules.
 */
namespace Axn\Laroute\Routes;

use Illuminate\Routing\Route;
use Lord\Laroute\Routes\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public $module = null;

    public function __construct(\Illuminate\Routing\RouteCollection $routes, $filter, $namespace, $module = null)
    {
        $this->module = $module;
        $this->items = $this->parseRoutes($routes, $filter, $namespace);
    }

    /**
     * Get the collection of items as JSON **pretty printed**.
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        $options = JSON_PRETTY_PRINT | $options;

        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the route information for a given route.
     *
     * @param $route     \Illuminate\Routing\Route
     * @param $filter    string
     * @param $namespace string
     *
     * @return array
     */
    protected function getRouteInformation(Route $route, $filter, $namespace)
    {
        $data = parent::getRouteInformation($route, $filter, $namespace);

        if (!$data || empty($data['name'])) {
            return null;
        }

        // If `module` parameter is set for the route we choose only routes from this module
        $action = $route->getAction();

        if ($action && !empty($action['controller'])) {

            preg_match('/^Modules\\\([^\\\]+)\\\/', $action['controller'], $m);

            if (!empty($m[1])) {
                if (!$this->module) {
                    // We are generating routes for the main application,
                    // missing route of the module
                    return null;
                } else {
                    // Route belongs to another module
                    if ($this->module != strtolower($m[1])) {
                        return null;
                    }
                }
            } elseif ($this->module) {
                // Include only module routes into module JS file
                return null;
            }
        }

        return array_only($data, ['uri', 'name']);
    }
}
