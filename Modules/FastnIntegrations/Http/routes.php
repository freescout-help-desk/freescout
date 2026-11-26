<?php

Route::group([
    'middleware' => ['web', 'auth', 'roles'],
    'roles'      => ['admin'],
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\FastnIntegrations\Http\Controllers',
], function () {
    // Returns a short-lived fastn embed token for the Integrations page.
    // The fastn API key stays on the server and never reaches the browser.
    Route::post('/fastn-integrations/token', 'FastnIntegrationsController@token')->name('fastnintegrations.token');
    // Sends a sample ticket through the three ticket workflows.
    Route::post('/fastn-integrations/test', 'FastnIntegrationsController@test')->name('fastnintegrations.test');
});

// Changes coming back from the apps (two-way). fastn calls this with a signed
// request, so it has no login or CSRF; the signature is checked instead.
Route::group([
    'prefix'    => \Helper::getSubdirectory(),
    'namespace' => 'Modules\FastnIntegrations\Http\Controllers',
], function () {
    Route::post('/fastn-integrations/events', 'FastnEventsController@receive')->name('fastnintegrations.events');
});
