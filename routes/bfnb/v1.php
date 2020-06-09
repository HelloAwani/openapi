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
    Route::post('branchs', 'Master@branchs');
    Route::post('categories', 'Master@categories');
    Route::post('items', 'Master@items');
    Route::post('modifiers', 'Master@modifiers');
    Route::post('multi_prices', 'Master@multi_prices');
    Route::post('promotions', 'Master@promotions');
    Route::post('payment_methods', 'Master@payment_methods');
});

Route::group(['prefix' => $version.'/transaction'], function () {
    Route::post('fetch', 'Transaction@fetch');
    Route::post('summary', 'Transaction@summary');
});

Route::group(['prefix' => $version.'/inventory'], function () {
    Route::post('ingredient_usages', 'Inventory@ingredient_usages');
});
