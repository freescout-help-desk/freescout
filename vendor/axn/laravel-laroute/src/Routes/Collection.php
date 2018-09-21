<?php

namespace Axn\Laroute\Routes;

use Illuminate\Routing\Route;
use Lord\Laroute\Routes\Collection as BaseCollection;

class Collection extends BaseCollection
{
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

        return array_only($data, ['uri', 'name']);
    }
}
