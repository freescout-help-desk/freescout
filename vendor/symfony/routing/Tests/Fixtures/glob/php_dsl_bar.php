<?php

namespace Symfony\Component\Routing\Loader\Configurator;

return function (RoutingConfigurator $routes) {
    $collection = $routes->collection();

    $collection->add('bar_route', '/bar')
        ->defaults(array('_controller' => 'AppBundle:Bar:view'));

    return $collection;
};
