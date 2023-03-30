<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\DarkMode\Http\Controllers'], function()
{
    Route::get('/', 'DarkModeController@index');
});
