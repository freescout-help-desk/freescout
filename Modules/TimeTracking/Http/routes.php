<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\TimeTracking\Http\Controllers'], function()
{
    Route::post('/time-tracking/ajax', ['uses' => 'TimeTrackingController@ajax', 'laroute' => true])->name('time_tracking.ajax');
});
