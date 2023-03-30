<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['user', 'admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Crm\Http\Controllers'], function()
{
    Route::get('/customers/new', 'CrmController@createCustomer')->name('crm.create_customer');
    Route::post('/customers/new', 'CrmController@createCustomerSave');
    Route::get('/crm/ajax-html/{action}/{param?}', ['uses' => 'CrmController@ajaxHtml'])->name('crm.ajax_html');
    Route::get('/customers/fields/ajax-search', ['uses' => 'CrmController@ajaxSearch', 'laroute' => true])->name('crm.ajax_search');
    Route::post('/crm/ajax', ['uses' => 'CrmController@ajax', 'laroute' => true])->name('crm.ajax');
});

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Crm\Http\Controllers'], function()
{
    Route::post('/customers/export', ['uses' => 'CrmController@export'])->name('crm.export');
    Route::post('/crm/ajax-admin', ['uses' => 'CrmController@ajaxAdmin', 'laroute' => true])->name('crm.ajax_admin');
});
