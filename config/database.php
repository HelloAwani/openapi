



























































<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_OBJ,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DEF_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'oap' => [
            'driver' => 'pgsql',
            'host' => env('OAP_DB_HOST'),
            'port' => env('OAP_DB_PORT'),
            'database' => env('OAP_DB_DATABASE'),
            'username' => env('OAP_DB_USERNAME'),
            'password' => env('OAP_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
        'OpenTransaction' => [
            'driver' => 'pgsql',
            'host' => env('OAP_DB_HOST'),
            'port' => env('OAP_DB_PORT'),
            'database' => env('OAP_DB_DATABASE'),
            'username' => env('OAP_DB_USERNAME'),
            'password' => env('OAP_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'opentrans',
            'sslmode' => 'prefer',
        ],
        'RES' => [
            'driver' => 'pgsql',
            'host' => env('FNB_DB_HOST'),
            'port' => env('FNB_DB_PORT'),
            'database' => env('FNB_DB_DATABASE'),
            'username' => env('FNB_DB_USERNAME'),
            'password' => env('FNB_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
        'HQF' => [
            'driver' => 'pgsql',
            'host' => env('FNB_DB_HOST'),
            'port' => env('FNB_DB_PORT'),
            'database' => env('FNB_DB_DATABASE'),
            'username' => env('FNB_DB_USERNAME'),
            'password' => env('FNB_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
        'RET' => [
            'driver' => 'pgsql',
            'host' => env('RETAIL_DB_HOST'),
            'port' => env('RETAIL_DB_PORT'),
            'database' => env('RETAIL_DB_DATABASE'),
            'username' => env('RETAIL_DB_USERNAME'),
            'password' => env('RETAIL_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'log_activity' => [
           'driver'   => 'mongodb',
           'host' => env('LOG_ACTIVITY_DB_HOST'),
           'port' => env('LOG_ACTIVITY_DB_PORT'),
           'database' => env('LOG_ACTIVITY_DB_DATABASE'),
           'username' => env('LOG_ACTIVITY_DB_USERNAME'),
           'password' => env('LOG_ACTIVITY_DB_PASSWORD'),
           'options'  => ['database' => 'admin'],
           'debug' => env('APP_DEBUG', false),

        ],
        

    ],
    
    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],

    ],

];