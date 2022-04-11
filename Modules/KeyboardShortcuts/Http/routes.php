<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\KeyboardShortcuts\Http\Controllers'], function()
{
    Route::get('/', 'KeyboardShortcutsController@index');
});
