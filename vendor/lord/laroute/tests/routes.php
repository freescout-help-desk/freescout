<?php

//Route::get('/hello', function () {
//    return 'hello';
//});
//
//Route::get('/', [
//    'as'   => 'home',
//    'uses' => 'HomeController@index',
//]);
//
//Route::get('/away/{somewhere}', [
//    'as'   => 'away',
//    'uses' => 'AwayController@somewhere',
//]);
//
//Route::get('/away/{somewhere}/very/{exotic}', [
//    'as'   => 'exotic',
//    'uses' => 'AwayController@exotic',
//]);
//
//
//Route::get('/ignored', [
//    'laroute' => false,
//    'as'      => 'ignored',
//    'uses'    => 'IgnoredController@index',
//]);
//
//Route::group(['prefix' => '/group'], function () {
//    Route::get('{group}', 'GroupController@index');
//});
//
//Route::group(['laroute' => false], function () {
//    Route::get('ignored', [
//        'as'   => 'group.ignored',
//        'uses' => 'IgnoredController@index'
//    ]);
//});
//
//Route::group(['prefix' => 'group/{group}'], function () {
//    Route::resource('resource/{resource}', 'GroupResourceController');
//});
