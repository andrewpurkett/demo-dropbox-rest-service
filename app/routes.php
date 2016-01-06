<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::group(['before' => 'force.ssl'], function(){

    // Subscription Controller components

    // Context.IO
    Route::get('/context.io/authorization', ['uses' => 'ContextIOSubscriptionController@get']);
    Route::put('/context.io/authorization/{contract_guid}', ['uses' => 'ContextIOSubscriptionController@put']);
    Route::delete('/context.io/authorization/{contract_guid}', ['uses' => 'ContextIOSubscriptionController@delete']);

    // Dropbox
    Route::get('/authorization', ['uses' => 'SubscriptionController@get']);
    Route::put('/authorization/{contract_guid}', ['uses' => 'SubscriptionController@put']);
    Route::delete('/authorization/{contract_guid}', ['uses' => 'SubscriptionController@delete']);

    // Dropbox components

    // Route::get('/sync/{contract_id}', ['uses' => 'DropboxCrawlerController@sync']);
    Route::get('/sync/{contract_guid}', ['uses' => 'DropboxCrawlerController@enqueue']);
    Route::get('/download/{entry_guid}', ['uses' => 'DropboxCrawlerController@download']);
    Route::get('/localize/{entry_guid}', ['uses' => 'DropboxCrawlerController@localize']);
    Route::get('/list/{contract_guid}', ['uses' => 'DropboxCrawlerController@files']);
    Route::get('/info/{contract_guid}', ['uses' => 'DropboxCrawlerController@info']);
    Route::get('/status/{contract_guid}', ['uses' => 'DropboxCrawlerController@status']);

    // Context.IO components

    Route::get('/context.io/sync', ['uses' => 'ContextIOCrawlerController@sync']); // temporary route for testing
    Route::get('/context.io/sync/{contract_guid}', ['uses' => 'ContextIOCrawlerController@sync']);
    Route::get('/context.io/download/{entry_guid}', ['uses' => 'ContextIOCrawlerController@download']);
    Route::get('/2/localize/{entry_guid}', ['uses' => 'ContextIOCrawlerController@localize']);
    Route::get('/2/info/{contract_guid}', ['uses' => 'ContextIOCrawlerController@info']);
    Route::get('/2/list/{contract_guid}', ['uses' => 'ContextIOCrawlerController@files']);
    Route::get('/2/enqueue/{contract_guid}', ['uses' => 'ContextIOCrawlerController@enqueue']);

    Route::get('/test', ['uses' => 'DropboxCrawlerController@requeue_deadbeats']);

});

Route::resource('mailboxes', 'MailboxesController');

Route::resource('attachments', 'AttachmentsController');