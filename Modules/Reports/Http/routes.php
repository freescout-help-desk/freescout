<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Reports\Http\Controllers'], function()
{
    Route::get('/reports/conversations', ['uses' => 'ReportsController@conversationsReport', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('reports.conversations');
    Route::get('/reports/productivity', ['uses' => 'ReportsController@productivityReport', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('reports.productivity');
    Route::get('/reports/satisfaction', ['uses' => 'ReportsController@satisfactionReport', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('reports.satisfaction');
    Route::get('/reports/time-tracking', ['uses' => 'ReportsController@timeReport', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('reports.time');
    Route::post('/reports/ajax', ['uses' => 'ReportsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('reports.ajax');
});
