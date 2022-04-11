<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\TicketTranslator\Http\Controllers'], function()
{
    Route::get('/ticket-translator/modal/{thread_id}', ['uses' => 'TicketTranslatorController@modal', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin']])->name('ticket_translator.modal');
    Route::post('/ticket-translator/ajax', ['uses' => 'TicketTranslatorController@ajax', 'middleware' => ['auth', 'roles'], 'roles' => ['user', 'admin'], 'laroute' => true])->name('ticket_translator.ajax');
});
