<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Noreply\Http\Controllers'], function()
{
    Route::get('/', 'NoreplyController@index');
});
