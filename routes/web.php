<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'SecureController@dashboard')->name('dashboard');

Route::get('/users', 'UsersController@users')->name('users');
Route::get('/users/profile/{id}', 'UsersController@profile')->name('users.profile');
Route::post('/users/profile/{id}', 'UsersController@profileSave');
Route::post('/users/permissions/{id}', 'UsersController@permissionsSave');
Route::get('/users/permissions/{id}', 'UsersController@permissions')->name('users.permissions');
Route::post('/users/permissions/{id}', 'UsersController@permissionsSave');

Route::get('/settings/mailboxes', 'MailboxesController@mailboxes')->name('mailboxes');
Route::get('/settings/mailbox-new', 'MailboxesController@create')->name('mailboxes.create');
Route::post('/settings/mailbox-new', 'MailboxesController@createSave');
Route::get('/settings/mailbox/{id}', 'MailboxesController@update')->name('mailboxes.update');
Route::post('/settings/mailbox/{id}', 'MailboxesController@updateSave');
Route::get('/settings/permissions/{id}', 'MailboxesController@permissions')->name('mailboxes.permissions');
Route::post('/settings/permissions/{id}', 'MailboxesController@permissionsSave');
Route::get('/mailbox/{id}', 'MailboxesController@update')->name('mailboxes.view');
Route::get('/settings/connection-settings/{id}/outgoing', 'MailboxesController@connectionOutgoing')->name('mailboxes.connection');
Route::get('/settings/connection-settings/{id}/incoming', 'MailboxesController@connectionIncoming')->name('mailboxes.connection.incoming');