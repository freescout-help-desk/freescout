<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\ExportConversations\Http\Controllers'], function()
{
    Route::get('/export-conversations/ajax-html/{action}', ['uses' => 'ExportConversationsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('exportconversations.ajax_html');
    Route::post('/export-conversations/export', ['uses' => 'ExportConversationsController@export', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('exportconversations.export');
    // Route::get('/export-conversations/export','ExportConversationsController@generate');
});
