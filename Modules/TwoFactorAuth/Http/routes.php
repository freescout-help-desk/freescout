<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\TwoFactorAuth\Http\Controllers'], function()
{
    Route::get('/users/auth/{id}', ['uses' => 'TwoFactorAuthController@userAuthSettings', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('twofactorauth.user_auth_settings');
    Route::post('/users/auth/{id}', ['uses' => 'TwoFactorAuthController@userAuthSettingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('twofactorauth.user_auth_settings.save');
    Route::get('/users/auth/confirm/{id}', ['uses' => 'TwoFactorAuthController@userAuthSettingsConfirm', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('twofactorauth.user_auth_settings_confirm');
    Route::post('/users/auth/confirm/{id}', ['uses' => 'TwoFactorAuthController@userAuthSettingsConfirmSave', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('twofactorauth.user_auth_settings_confirm.save');

    Route::get('/users/2fa/ajax-html/{action}/{param}', ['uses' => 'TwoFactorAuthController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user']])->name('twofactorauth.ajax_html');
    Route::any('/users/2fa/ajax', ['uses' => 'TwoFactorAuthController@ajax', 'laroute' => true, 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user']])->name('twofactorauth.ajax');
});
