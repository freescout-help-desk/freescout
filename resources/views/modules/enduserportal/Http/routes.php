<?php

// Admin Settings
Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\EndUserPortal\Http\Controllers'], function()
{
    Route::get('/mailbox/{mailbox_id}/end-user-portal', ['uses' => 'EndUserPortalController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('enduserportal.settings');
    Route::post('/mailbox/{mailbox_id}/end-user-portal', ['uses' => 'EndUserPortalController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
});

// Portal
Route::group(['middleware' => [\App\Http\Middleware\EncryptCookies::class, \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \App\Http\Middleware\VerifyCsrfToken::class, \Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\HttpsRedirect::class, \App\Http\Middleware\Localize::class], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\EndUserPortal\Http\Controllers'], function()
{
	// Portal.
    Route::get('/help/{mailbox_id}/auth', 'EndUserPortalController@login')->name('enduserportal.login');
    Route::post('/help/{mailbox_id}/auth/logout', 'EndUserPortalController@logout')->name('enduserportal.logout');
    Route::post('/help/{mailbox_id}/auth', 'EndUserPortalController@loginProcess');
    Route::get('/help/{mailbox_id}/auth/{customer_id}', 'EndUserPortalController@loginFromEmail')->name('enduserportal.login_from_email');
    Route::get('/help/{mailbox_id}/tickets', 'EndUserPortalController@tickets')->name('enduserportal.tickets');
    Route::get('/help/{mailbox_id}/ticket/{conversation_id}', 'EndUserPortalController@ticket')->name('enduserportal.ticket');
    Route::post('/help/{mailbox_id}/ticket/{conversation_id}', 'EndUserPortalController@submitReply');
    Route::get('/help/{mailbox_id}', 'EndUserPortalController@submit')->name('enduserportal.submit');
    Route::post('/help/{mailbox_id}', 'EndUserPortalController@submitProcess');
    Route::get('/help/{mailbox_id}/ajax-html/{action}', 'EndUserPortalController@ajaxHtml')->name('enduserportal.ajax_html');
});


// Form
Route::group(['middleware' => [\App\Http\Middleware\EncryptCookies::class, \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, /*\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,*/ /*\App\Http\Middleware\VerifyCsrfToken::class,*/ \Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\HttpsRedirect::class, \App\Http\Middleware\Localize::class], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\EndUserPortal\Http\Controllers'], function()
{
    Route::post('/help/{mailbox_id}/upload', ['uses' => 'EndUserPortalController@upload', 'laroute' => true])->name('enduserportal.upload');

    // Widget.
    Route::get('/help/widget/form/{mailbox_id}', 'EndUserPortalController@widgetForm')->name('enduserportal.widget_form');
    Route::post('/help/widget/form/{mailbox_id}', 'EndUserPortalController@widgetFormProcess');
});
