<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Reports\Http\Controllers'], function()
{
    Route::get('/reports/conversations', ['uses' => 'ReportsController@conversationsReport', 'middleware' => ['auth']])->name('reports.conversations');
    Route::get('/reports/productivity', ['uses' => 'ReportsController@productivityReport', 'middleware' => ['auth']])->name('reports.productivity');
    Route::get('/reports/satisfaction', ['uses' => 'ReportsController@satisfactionReport', 'middleware' => ['auth']])->name('reports.satisfaction');
    Route::get('/reports/time-tracking', ['uses' => 'ReportsController@timeReport', 'middleware' => ['auth']])->name('reports.time');
    Route::post('/reports/ajax', ['uses' => 'ReportsController@ajax', 'middleware' => ['auth'], 'laroute' => true])->name('reports.ajax');
});
