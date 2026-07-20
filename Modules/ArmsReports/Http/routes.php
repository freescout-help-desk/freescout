<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => 'arms-reports'], function () {
    Route::get('/kpis', ['uses' => '\Modules\ArmsReports\Http\Controllers\ArmsReportsController@kpis'])->name('armsreports.kpis');
    Route::get('/agents', ['uses' => '\Modules\ArmsReports\Http\Controllers\ArmsReportsController@agents'])->name('armsreports.agents');
});
