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
use Service\Http\Controllers\Device\Page;

$version = 'v6';

Route::get('/', [Page::class,'forbidden']);
Route::get($version, [Page::class,'forbidden']);


Route::group(['prefix' => $version.'/auth'], function () {
    Route::post('login', 'Auth@login');
    Route::post('attach', 'Auth@attach');
    Route::post('logout', 'Auth@logout');
});

Route::group(['prefix' => $version.'/sync'], function () {
    Route::post('master', 'Sync@master');
    Route::post('user', 'Sync@user');
    Route::post('customer', 'Sync@customer');
    Route::post('product', 'Sync@product');
    Route::post('promotion', 'Sync@promotion');
    Route::post('room', 'Sync@room');
});

Route::group(['prefix' => $version.'/transaction'], function () {
    Route::post('sales', 'Transaction@sales');
    Route::post('daily_history', 'Transaction@daily_history');
    //Route::post('generate_data', 'Transaction@generate_data');
    Route::post('void', 'Transaction@void');
    Route::post('save_reservation', 'Transaction@save_reservation');
    Route::post('get_reservation', 'Transaction@get_reservation');
    Route::post('delete_reservation', 'Transaction@delete_reservation');
});


Route::group(['prefix' => $version.'/device'], function () {
    Route::post('save_printer', 'Device@save_printer');
    Route::post('delete_printer', 'Device@delete_printer');
});

Route::group(['prefix' => $version.'/space'], function () {
    Route::post('save_section', 'Device@save_space_section');
});
