<?php

Route::group(['middleware' => 'web', 'prefix' => 'slack', 'namespace' => 'Modules\Slack\Http\Controllers'], function()
{
    Route::get('/', 'SlackController@index');
});
