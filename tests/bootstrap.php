<?php

require __DIR__.'/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__.'/'.str_replace('\\', '/', $relative).'.php';
    if (is_file($path)) {
        require $path;
    }
});
