<?php

$attributes = [
    'middleware' => ['web', 'auth'],
];

$subdirectory = \Helper::getSubdirectory();
if ($subdirectory) {
    $attributes['prefix'] = $subdirectory;
}

$controller = \Modules\Workflows\Http\Controllers\WorkflowsController::class;

// create and sort are registered before {workflow} so those words are not captured as ids.
Route::group($attributes, function () use ($controller) {
    Route::get('mailboxes/{id}/workflows', $controller.'@index')->name('mailboxes.workflows');
    Route::get('mailboxes/{id}/workflows/create', $controller.'@create')->name('mailboxes.workflows.create');
    Route::post('mailboxes/{id}/workflows', $controller.'@store')->name('mailboxes.workflows.store');
    Route::post('mailboxes/{id}/workflows/sort', $controller.'@sort')->name('mailboxes.workflows.sort');
    Route::get('mailboxes/{id}/workflows/{workflow}', $controller.'@edit')->name('mailboxes.workflows.edit');
    Route::post('mailboxes/{id}/workflows/{workflow}', $controller.'@update')->name('mailboxes.workflows.update');
    Route::post('mailboxes/{id}/workflows/{workflow}/delete', $controller.'@delete')->name('mailboxes.workflows.delete');
    Route::post('conversation/{id}/workflow/{workflow}', $controller.'@run')->name('conversations.workflow.run');
});
