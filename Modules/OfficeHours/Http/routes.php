<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\OfficeHours\Http\Controllers'], function()
{
    Route::get('/', 'OfficeHoursController@index');
});
