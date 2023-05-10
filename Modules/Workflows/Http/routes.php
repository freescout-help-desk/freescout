<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\Workflows\Http\Controllers'], function()
{
    Route::get('/mailbox/{mailbox_id}/workflows', ['uses' => 'WorkflowsController@index'])->name('mailboxes.workflows');
    Route::get('/mailbox/{mailbox_id}/workflows/new', ['uses' => 'WorkflowsController@create'])->name('mailboxes.workflows.create');
    Route::post('/mailbox/{mailbox_id}/workflows/new', ['uses' => 'WorkflowsController@createSave'])->name('mailboxes.workflows.new.save');
    Route::get('/mailbox/{mailbox_id}/workflows/{id}', ['uses' => 'WorkflowsController@update'])->name('mailboxes.workflows.update');
    Route::post('/mailbox/{mailbox_id}/workflows/{id}', ['uses' => 'WorkflowsController@updateSave'])->name('mailboxes.workflows.update.save');

    Route::post('/mailbox/workflows/ajax', ['uses' => 'WorkflowsController@ajax', 'laroute' => true])->name('mailboxes.workflows.ajax');
    Route::get('/mailbox/workflows/ajax-html/{action}/{mailbox_id}', ['uses' => 'WorkflowsController@ajaxHtml'])->name('mailboxes.workflows.ajax_html');
});
