<?php

// Webhook.
Route::group([/*'middleware' => 'web', */'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Facebook\Http\Controllers'], function()
{
    //Route::get('/', 'FacebookController@index');
    Route::match(['get', 'post'], '/facebook/webhook/{mailbox_id}/{mailbox_secret}', 'FacebookController@webhooks')->name('facebook.webhook');
});

// Admin.
Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Facebook\Http\Controllers'], function()
{
    Route::get('/mailbox/{mailbox_id}/facebook', ['uses' => 'FacebookController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.facebook.settings');
    Route::post('/mailbox/{mailbox_id}/facebook', ['uses' => 'FacebookController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
});