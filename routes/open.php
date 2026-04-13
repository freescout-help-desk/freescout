<?php

/*
|--------------------------------------------------------------------------
| Open Routes
|--------------------------------------------------------------------------
|
| Here is where you can register open routes for your application. These
|
| Every time you change routes, run the following command to make them available in JS:
|     php artisan freescout:build
*/

// Download attachments
Route::get('/storage/attachment/{dir_1}/{dir_2}/{dir_3}/{file_name}', 'OpenController@downloadAttachment')->name('attachment.download');
// Open tracking
Route::get('/thread/read/{conversation_id}/{thread_id}', 'OpenController@setThreadAsRead')->name('open_tracking.set_read');