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

Route::group(['prefix' => $version.'/master'], function () {
    Route::post('categories', 'Master@categories');
    Route::post('items', 'Master@items');
    Route::post('tags', 'Master@tags');
    Route::post('multi_prices', 'Master@multi_prices');
    Route::post('payment_methods', 'Master@payment_methods');
    Route::post('shifts', 'Master@shifts');
    Route::post('users', 'Master@users');
    Route::post('customers', 'Master@customers');
    Route::post('promotions', 'Master@promotions');
});

Route::group(['prefix' => $version.'/transaction'], function () {
    Route::post('fetch', 'Transaction@fetch');
});
