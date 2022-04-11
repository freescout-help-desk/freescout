<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\SatRatings\Http\Controllers'], function()
{
    Route::get('/mailbox/satisfaction-ratings/{id}', ['uses' => 'SatRatingsController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.sat_ratings');
    Route::post('/mailbox/satisfaction-ratings/{id}', ['uses' => 'SatRatingsController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.sat_ratings.save');
    Route::get('/mailbox/satisfaction-ratings/{id}/translations', ['uses' => 'SatRatingsController@trans', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.sat_ratings_trans');
    Route::post('/mailbox/satisfaction-ratings/{id}/translations', ['uses' => 'SatRatingsController@transSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('mailboxes.sat_ratings_trans');

    // Public
    Route::get('/feedback/{thread_id}/record/{hash}/{rating?}', ['uses' => 'SatRatingsController@record'])->name('sat_ratings.record');
    Route::post('/feedback/{thread_id}/record/{hash}/{rating?}', ['uses' => 'SatRatingsController@recordSave']);
    Route::get('/feedback/{thread_id}/thanks', ['uses' => 'SatRatingsController@thanks'])->name('sat_ratings.thanks');
});