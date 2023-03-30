<?php


Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\CustomFields\Http\Controllers'], function()
{
    Route::get('/mailbox/custom-fields/{id}', 'CustomFieldsController@index')->name('mailboxes.custom_fields');
    Route::get('/mailbox/custom-fields/ajax-html/{action}', ['uses' => 'CustomFieldsController@ajaxHtml', 'laroute' => true])->name('mailboxes.custom_fields.ajax_html');
    Route::post('/custom-fields/ajax-admin', ['uses' => 'CustomFieldsController@ajaxAdmin', 'laroute' => true])->name('mailboxes.custom_fields.ajax_admin');
});

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['user', 'admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\CustomFields\Http\Controllers'], function()
{
    Route::any('/custom-fields/ajax', ['uses' => 'CustomFieldsController@ajax', 'laroute' => true])->name('mailboxes.custom_fields.ajax');
    Route::get('/custom-fields/ajax-search', ['uses' => 'CustomFieldsController@ajaxSearch', 'laroute' => true])->name('mailboxes.customfields.ajax_search');
});