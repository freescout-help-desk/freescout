<?php

Route::group(['middleware' => ['web'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\CustomFolders\Http\Controllers'], function()
{
    Route::get('/mailbox/{id}/custom-folders', 'CustomFoldersController@index')->name('mailboxes.custom_folders');
    Route::get('/mailbox/custom-folders/ajax-html/{mailbox_id}/{action}', ['uses' => 'CustomFoldersController@ajaxHtml', 'laroute' => true])->name('mailboxes.custom_folders.ajax_html');
    Route::post('/mailbox/custom-folders/ajax', ['uses' => 'CustomFoldersController@ajax', 'laroute' => true])->name('mailboxes.custom_folders.ajax');
});
