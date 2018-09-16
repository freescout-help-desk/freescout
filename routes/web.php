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
| Every time you change routes, run the following command to make them available in JS:
|     php artisan freescout:build
*/

Auth::routes();

// Redirects
Route::redirect('/home', '/', 301);

// Public routes
Route::get('/user-setup/{hash}', 'PublicController@userSetup')->name('user_setup');
Route::post('/user-setup/{hash}', 'PublicController@userSetupSave');

// General routes for logged in users
Route::get('/', 'SecureController@dashboard')->name('dashboard');
Route::get('/logs/{name?}', ['uses' => 'SecureController@logs', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('logs');
Route::post('/logs/{name?}', ['uses' => 'SecureController@logsSubmit', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
Route::get('/system', ['uses' => 'SecureController@system', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('system');

// Settings
Route::get('/app-settings/{section?}', ['uses' => 'SettingsController@view', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('settings');
Route::post('/app-settings/{section?}', ['uses' => 'SettingsController@save', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);

// Users
Route::get('/users', 'UsersController@users')->name('users');
Route::get('/users/wizard', 'UsersController@create')->name('users.create');
Route::post('/users/wizard', 'UsersController@createSave');
Route::get('/users/profile/{id}', 'UsersController@profile')->name('users.profile');
Route::post('/users/profile/{id}', 'UsersController@profileSave');
Route::post('/users/permissions/{id}', 'UsersController@permissionsSave');
Route::get('/users/permissions/{id}', 'UsersController@permissions')->name('users.permissions');
Route::post('/users/permissions/{id}', 'UsersController@permissionsSave');
Route::get('/users/notifications/{id}', 'UsersController@notifications')->name('users.notifications');
Route::post('/users/notifications/{id}', 'UsersController@notificationsSave');
Route::get('/users/password/{id}', 'UsersController@password')->name('users.password');
Route::post('/users/password/{id}', 'UsersController@passwordSave');
Route::post('/users/ajax', ['uses' => 'UsersController@ajax', 'laroute' => true])->name('users.ajax');

// Conversations
Route::get('/conversation/{id}', ['uses' => 'ConversationsController@view', 'laroute' => true])->name('conversations.view');
Route::post('/conversation/ajax', ['uses' => 'ConversationsController@ajax', 'laroute' => true])->name('conversations.ajax');
Route::post('/conversation/upload', ['uses' => 'ConversationsController@upload', 'laroute' => true])->name('conversations.upload');
Route::get('/mailbox/{mailbox_id}/new-ticket', 'ConversationsController@create')->name('conversations.create');
//Route::get('/conversation/draft/{id}', 'ConversationsController@draft')->name('conversations.draft');
Route::get('/conversation/ajax-html/{action}', ['uses' => 'ConversationsController@ajaxHtml', 'laroute' => true])->name('conversations.ajax_html');
Route::get('/search', 'ConversationsController@search')->name('conversations.search');
Route::get('/conversation/undo-reply/{thread_id}', 'ConversationsController@undoReply')->name('conversations.undo');

// Mailboxes
Route::get('/settings/mailboxes', 'MailboxesController@mailboxes')->name('mailboxes');
Route::get('/settings/mailbox-new', 'MailboxesController@create')->name('mailboxes.create');
Route::post('/settings/mailbox-new', 'MailboxesController@createSave');
Route::get('/settings/mailbox/{id}', 'MailboxesController@update')->name('mailboxes.update');
Route::post('/settings/mailbox/{id}', 'MailboxesController@updateSave');
Route::get('/settings/permissions/{id}', 'MailboxesController@permissions')->name('mailboxes.permissions');
Route::post('/settings/permissions/{id}', 'MailboxesController@permissionsSave');
Route::get('/mailbox/{id}', 'MailboxesController@view')->name('mailboxes.view');
Route::get('/mailbox/{id}/{folder_id}', 'MailboxesController@view')->name('mailboxes.view.folder');
Route::get('/settings/connection-settings/{id}/outgoing', 'MailboxesController@connectionOutgoing')->name('mailboxes.connection');
Route::post('/settings/connection-settings/{id}/outgoing', 'MailboxesController@connectionOutgoingSave');
Route::get('/settings/connection-settings/{id}/incoming', 'MailboxesController@connectionIncoming')->name('mailboxes.connection.incoming');
Route::post('/settings/connection-settings/{id}/incoming', 'MailboxesController@connectionIncomingSave');
Route::get('/settings/mailbox/{id}/auto-reply', 'MailboxesController@autoReply')->name('mailboxes.auto_reply');
Route::post('/settings/mailbox/{id}/auto-reply', 'MailboxesController@autoReplySave');

// Customers
Route::get('/customer/{id}/edit', 'CustomersController@update')->name('customers.update');
Route::post('/customer/{id}/edit', 'CustomersController@updateSave');
Route::get('/customer/{id}/', 'CustomersController@conversations')->name('customers.conversations');
Route::get('/customer/ajax-search', ['uses' => 'CustomersController@ajaxSearch', 'laroute' => true])->name('customers.ajax_search');