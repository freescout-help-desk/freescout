<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Tags\Http\Controllers'], function()
{
    Route::any('/tags/ajax', ['uses' => 'TagsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user'], 'laroute' => true])->name('tags.ajax');
    Route::any('/tags', ['uses' => 'TagsController@tags', 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user']])->name('tags.tags');
    Route::get('/tags/ajax-html/{action}/{param}', ['uses' => 'TagsController@ajaxHtml', 'middleware' => ['auth', 'roles'], 'roles' => ['admin', 'user']])->name('tags.ajax_html');
});
