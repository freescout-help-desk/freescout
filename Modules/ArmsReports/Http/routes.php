<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => 'arms-reports'], function () {
    Route::get('/kpis', ['uses' => '\Modules\ArmsReports\Http\Controllers\ArmsReportsController@kpis'])->name('armsreports.kpis');
    Route::get('/agents', ['uses' => '\Modules\ArmsReports\Http\Controllers\ArmsReportsController@agents'])->name('armsreports.agents');
});

// Broader than the group above (user + admin, not admin-only): this button
// renders on the native Reports pages, which any user with report access
// can view, not just admins.
Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['user', 'admin'], 'prefix' => 'arms-reports'], function () {
    Route::post('/native-export-pdf', ['uses' => '\Modules\ArmsReports\Http\Controllers\ArmsReportsController@nativeExportPdf'])->name('armsreports.native_export_pdf');
});
