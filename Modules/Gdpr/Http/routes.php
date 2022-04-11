<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Gdpr\Http\Controllers'], function()
{
    Route::get('/gdpr/ajax-html/{action}/{param?}', ['uses' => 'GdprController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('gdpr.ajax_html');
    Route::post('/gdpr/ajax', ['uses' => 'GdprController@ajaxAdmin', 'laroute' => true])->name('gdpr.ajax');
});
