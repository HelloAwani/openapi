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

Route::post('test', 'Transaction@test');

// Route::group(['prefix' => $version.'/master'], function () {
//     Route::post('categories', 'Master@categories');
//     Route::post('items', 'Master@items');
//     Route::post('inventory_categories', 'Master@inventory_categories');
//     Route::post('inventories', 'Master@inventories');
//     Route::post('modifiers', 'Master@modifiers');
//     Route::post('ingredients', 'Master@ingredients');
//     Route::post('tags', 'Master@tags');
//     Route::post('multi_prices', 'Master@multi_prices');
//     Route::post('promotions', 'Master@promotions');
//     Route::post('payment_methods', 'Master@payment_methods');
//     Route::post('shifts', 'Master@shifts');
//     Route::post('users', 'Master@users');
//     Route::post('unit_type', 'Master@unit_type');
//     Route::post('customers', 'Master@customers');
// });

Route::group(['prefix' => $version.'/transaction'], function () {
    Route::post('fetch-sales', 'Transaction@fetchSales');
    Route::post('fetch-void-sales', 'Transaction@fetchVoidSales');
    Route::post('fetch-ingredient', 'Transaction@fetchIngredient');
});

// Route::group(['prefix' => $version.'/inventory'], function () {
//     Route::post('ingredient_usages', 'Inventory@ingredient_usages');
// });
