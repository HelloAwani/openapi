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

Route::get('/', function () {
    print_r("Forbidden");
});
Route::get($version, function () {
    print_r("Forbidden");
});


/////////////////////////////////////////////route for auth route////////////////////////////////
$route = 'auth';
$namespace = 'Auth';

Route::group(['namespace' => $namespace, 'prefix' => $route.'/authenticator'], function () {
    Route::post('generate_access_token', 'Authenticator@generate_access_token');
    Route::post('destroy', 'Authenticator@destroy');
});

/////////////////////////////////////////////route for BTPN route ////////////////////////////////
$route = 'btpn';
$namespace = 'BTPN';

Route::group(['namespace' => $namespace, 'prefix' => $route.'/outlet'], function () {
    Route::post('list', 'Outlet@list');
    Route::post('outlet_current_setting', 'Outlet@outlet_current_setting');
});


//------------------------------------route for BTPN Retail sub route ------------------------------------
$route = 'btpn/retail';
$namespace = 'BTPN\Retail';

Route::group(['namespace' => $namespace, 'prefix' => $route.'/master'], function () {
    Route::post('items', 'Master@items');
    Route::post('payment_methods', 'Master@payment_methods');
    Route::post('discounts', 'Master@discounts');
    Route::post('expense_types', 'Master@expense_types');
});

Route::group(['namespace' => $namespace, 'prefix' => $route.'/report'], function () {
    Route::post('sales/{breakdown}', 'Report@sales');
    Route::post('expenses', 'Report@expenses');
    Route::post('pnl', 'Report@pnl');
    Route::post('void', 'Report@void');
    Route::post('sales_summary', 'Report@sales_summary');
});

Route::group(['namespace' => $namespace, 'prefix' => $route.'/transaction'], function () {
    Route::post('sales', 'Transaction@sales');
});



/////////////////////////////////////////////route for OPEN  Api route ////////////////////////////////
$route = 'openapi';
$namespace = 'OpenAPI';

Route::group(['namespace' => $namespace, 'prefix' => $route.'/outlet'], function () {
    Route::post('detail', 'Outlet@detail');
});

