<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
|
| When a module starts, this file is executed automatically. Routes are
| loaded only when they are not cached and Http/routes.php exists.
|
*/

if (!app()->routesAreCached()) {
    $routes = __DIR__.'/Http/routes.php';

    if (file_exists($routes)) {
        require $routes;
    }
}
