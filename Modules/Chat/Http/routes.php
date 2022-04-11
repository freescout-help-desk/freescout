<?php
// Settings.
Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Chat\Http\Controllers'], function()
{
    Route::get('/mailbox/{mailbox_id}/chat-settings', ['uses' => 'ChatController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('chat.settings');
    Route::post('/mailbox/{mailbox_id}/chat-settings', ['uses' => 'ChatController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
});

// Frontend.
Route::group(['middleware' => [\App\Http\Middleware\EncryptCookies::class, \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, /*\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,*/ /*\App\Http\Middleware\VerifyCsrfToken::class,*/ \Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\HttpsRedirect::class, \App\Http\Middleware\Localize::class], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Chat\Http\Controllers'], function()
{
    //Route::post('/chat/{mailbox_id}/upload', ['uses' => 'ChatFrontendController@upload', 'laroute' => true])->name('chat.upload');

    // Widget.
    Route::get('/chat/widget/form/{mailbox_id}', 'ChatFrontendController@widgetForm')->name('chat.widget_form');
    Route::post('/chat/widget/form/{mailbox_id}', 'ChatFrontendController@widgetFormProcess');
    //Route::get('/chat/poll/{conversation_id}/{thread_id}',  ['uses' => 'ChatFrontendController@poll', 'laroute' => true])->name('chat.poll');
    Route::any('/chat/ajax', ['uses' => 'ChatFrontendController@ajax', 'laroute' => true])->name('chat.ajax');
});
