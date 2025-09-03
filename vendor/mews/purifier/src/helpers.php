<?php

if (!function_exists('clean')) {
    function clean($dirty, $config = null)
    {
        return app('purifier')->clean($dirty, $config);
    }
}
