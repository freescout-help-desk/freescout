<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\ExtendedAttachments\Http\Controllers'], function()
{
    Route::any('/thread/{thread_id}/download-attachments', ['uses' => 'ExtendedAttachmentsController@downloadThreadAttachments', 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user']])->name('extendedattachments.download_thread_attachments');
    Route::post('/attachments/ajax', ['uses' => 'ExtendedAttachmentsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('extendedattachments.ajax');
    Route::get('/attachments/ajax_html', ['uses' => 'ExtendedAttachmentsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('extendedattachments.ajax_html');
});
