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

Route::group(['prefix' => $version.'/token'], function () {
    Route::post('generate', 'Token@generate_access_token');
});
