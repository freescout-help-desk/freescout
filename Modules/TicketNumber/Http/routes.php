<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\TicketNumber\Http\Controllers'], function()
{
    Route::get('/', 'TicketNumberController@index');
});
