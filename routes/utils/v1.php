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


Route::group(['prefix' => $version.'/keys'], function () {
    Route::post('generate', 'Keys@generate');
    Route::post('pair/{qty}', 'Keys@pair');
});
