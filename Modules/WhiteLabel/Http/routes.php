<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WhiteLabel\Http\Controllers'], function()
{
    Route::get('/', 'WhiteLabelController@index');
});
