<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

$version = 'v1';

Route::group(['prefix' => $version.'/customer'], function () {
    Route::post('create', 'Customer@create_customer');
    Route::post('fetch_all', 'Customer@fetch_all');
    Route::post('fetch', 'Customer@fetch');
});

Route::group(['prefix' => $version.'/master'], function () {
    Route::post('items', 'Master@get');
    Route::post('outlet', 'Master@outlet');
});

Route::group(['prefix' => $version.'/transaction'], function () {
    Route::post('submit', 'Transaction@submit');
    Route::post('fetch', 'Transaction@fetch');
    Route::post('test_android', 'Transaction@test_android');
});
Route::group(['prefix' => $version.'/tun'], function () {
    Route::post('hellobill', 'Tunnel@fetch_transaction');
    Route::post('hellobill_set_status', 'Tunnel@update_transaction');
});