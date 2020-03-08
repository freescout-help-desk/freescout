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
Route::get('/storage/attachment/{dir_1}/{dir_2}/{dir_3}/{file_name}', 'PublicController@downloadAttachment')->name('attachment.download');

// General routes for logged in users
Route::get('/', 'SecureController@dashboard')->name('dashboard');
Route::get('/app-logs/app', ['uses' => '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('logs.app');
Route::get('/app-logs/{name?}', ['uses' => 'SecureController@logs', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('logs');
Route::post('/app-logs/{name?}', ['uses' => 'SecureController@logsSubmit', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('logs.action');

// Settings
Route::post('/app-settings/ajax', ['uses' => 'SettingsController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['admin'], 'laroute' => true])->name('settings.ajax');
Route::get('/app-settings/{section?}', ['uses' => 'SettingsController@view', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('settings');
Route::post('/app-settings/{section?}', ['uses' => 'SettingsController@save', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('settings.save');

// Users
Route::get('/users', ['uses' => 'UsersController@users', 'laroute' => true])->name('users');
Route::get('/users/wizard', 'UsersController@create')->name('users.create');
Route::post('/users/wizard', 'UsersController@createSave');
Route::get('/users/profile/{id}', 'UsersController@profile')->name('users.profile');
Route::post('/users/profile/{id}', 'UsersController@profileSave')->name('users.profile.save');
Route::post('/users/permissions/{id}', 'UsersController@permissionsSave');
Route::get('/users/permissions/{id}', 'UsersController@permissions')->name('users.permissions');
Route::post('/users/permissions/{id}', 'UsersController@permissionsSave')->name('users.permissions.save');
Route::get('/users/notifications/{id}', 'UsersController@notifications')->name('users.notifications');
Route::post('/users/notifications/{id}', 'UsersController@notificationsSave')->name('users.notifications.save');
Route::get('/users/password/{id}', 'UsersController@password')->name('users.password');
Route::post('/users/password/{id}', 'UsersController@passwordSave')->name('users.password.save');
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
Route::get('/mailboxes', ['uses' => 'MailboxesController@mailboxes', 'laroute' => true])->name('mailboxes');
Route::get('/mailbox/new', 'MailboxesController@create')->name('mailboxes.create');
Route::post('/mailbox/new', 'MailboxesController@createSave');
Route::get('/mailbox/settings/{id}', 'MailboxesController@update')->name('mailboxes.update');
Route::post('/mailbox/settings/{id}', 'MailboxesController@updateSave')->name('mailboxes.update.save');
Route::get('/mailbox/permissions/{id}', 'MailboxesController@permissions')->name('mailboxes.permissions');
Route::post('/mailbox/permissions/{id}', 'MailboxesController@permissionsSave')->name('mailboxes.permissions.save');
Route::get('/mailbox/{id}', 'MailboxesController@view')->name('mailboxes.view');
Route::get('/mailbox/{id}/{folder_id}', 'MailboxesController@view')->name('mailboxes.view.folder');
Route::get('/mailbox/connection-settings/{id}/outgoing', 'MailboxesController@connectionOutgoing')->name('mailboxes.connection');
Route::post('/mailbox/connection-settings/{id}/outgoing', 'MailboxesController@connectionOutgoingSave')->name('mailboxes.connection.save');
Route::get('/mailbox/connection-settings/{id}/incoming', 'MailboxesController@connectionIncoming')->name('mailboxes.connection.incoming');
Route::post('/mailbox/connection-settings/{id}/incoming', 'MailboxesController@connectionIncomingSave')->name('mailboxes.connection.incoming.save');
Route::get('/mailbox/settings/{id}/auto-reply', 'MailboxesController@autoReply')->name('mailboxes.auto_reply');
Route::post('/mailbox/settings/{id}/auto-reply', 'MailboxesController@autoReplySave')->name('mailboxes.auto_reply.save');
Route::post('/mailbox/ajax', ['uses' => 'MailboxesController@ajax', 'laroute' => true])->name('mailboxes.ajax');

// Customers
Route::get('/customers/{id}/edit', 'CustomersController@update')->name('customers.update');
Route::post('/customers/{id}/edit', 'CustomersController@updateSave');
Route::get('/customers/{id}/', 'CustomersController@conversations')->name('customers.conversations');
Route::get('/customers/ajax-search', ['uses' => 'CustomersController@ajaxSearch', 'laroute' => true])->name('customers.ajax_search');
Route::post('/customers/ajax', ['uses' => 'CustomersController@ajax', 'laroute' => true])->name('customers.ajax');

// Translate
Route::post('/translations/send', ['uses' => 'TranslateController@postSend', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('translations.send');
Route::post('/translations/removeUnpublished', ['uses' => 'TranslateController@postRemoveUnpublished', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('translations.remove_unpublished');
Route::post('/translations/download', ['uses' => 'TranslateController@postDownload', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('translations.download');

// Modules
// There is a /public/modules folder, so route must have a different name
Route::get('/modules/list', ['uses' => 'ModulesController@modules', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('modules');
Route::post('/modules/ajax', ['uses' => 'ModulesController@ajax', 'laroute' => true, 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('modules.ajax');

// System
Route::get('/system/status', ['uses' => 'SystemController@status', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('system');
Route::get('/system/tools', ['uses' => 'SystemController@tools', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('system.tools');
Route::post('/system/tools', ['uses' => 'SystemController@toolsExecute', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('system.tools.action');
Route::post('/system/ajax', ['uses' => 'SystemController@ajax', 'laroute' => true, 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('system.ajax');
Route::post('/system/action', ['uses' => 'SystemController@action', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('system.action');

// Open tracking
Route::get('/thread/read/{conversation_id}/{thread_id}', 'PublicController@setThreadAsRead')->name('open_tracking.set_read');

// Uploads
Route::post('/uploads/upload', ['uses' => 'SecureController@upload', 'laroute' => true])->name('uploads.upload');
