<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SpamFilter\Http\Controllers'], function()
{
    Route::get('/customer/{id}/spam-filter/{action}/{conversation_id}', ['uses' => 'SpamFilterController@action'/*, 'laroute' => true*/])->name('customers.spam_filter.action');
    Route::post('/app-settings/spamfilter/ajax', ['uses' => 'SpamFilterController@ajax', 'laroute' => true, 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('spam_filter.ajax');
});