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

Route::get('/settings/mailboxes', 'MailboxesController@mailboxes')->name('mailboxes');
Route::get('/settings/mailbox/{id}', 'MailboxesController@update')->name('mailboxes.update');
Route::post('/settings/mailbox/{id}', 'MailboxesController@updateSave');
Route::get('/settings/mailbox-new', 'MailboxesController@create')->name('mailboxes.create');
Route::post('/settings/mailbox-new', 'MailboxesController@createSave');