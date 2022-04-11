<?php

/*Route::group(['middleware' => 'web', 'prefix' => 'savedreplies', 'namespace' => 'Modules\SavedReplies\Http\Controllers'], function()
{
    Route::get('/', 'SavedRepliesController@index');
});*/
Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SavedReplies\Http\Controllers'], function()
{
    Route::get('/mailbox/saved-replies/{id}', 'SavedRepliesController@index')->name('mailboxes.saved_replies');
    Route::get('/mailbox/saved-replies/ajax-html/{action}/{param?}', ['uses' => 'SavedRepliesController@ajaxHtml', 'laroute' => true])->name('mailboxes.saved_replies.ajax_html');
    Route::post('/mailbox/saved-replies/ajax', ['uses' => 'SavedRepliesController@ajax', 'laroute' => true])->name('mailboxes.saved_replies.ajax');
});