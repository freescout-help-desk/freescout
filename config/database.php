<?php

return [

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

    'default' => env('DB_CONNECTION', 'mysql'),

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

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'   => env('DB_TABLE_PREFIX', ''),
        ],

        'mysql' => [
            'driver'      => 'mysql',
            'host'        => env('DB_HOST', '127.0.0.1'),
            'port'        => env('DB_PORT', '3306'),
            'database'    => env('DB_DATABASE', 'forge'),
            'username'    => env('DB_USERNAME', 'forge'),
            'password'    => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => env('DB_CHARSET', 'utf8mb4'),
            'collation'   => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'      => env('DB_TABLE_PREFIX', ''),
            'strict'      => false,
            'engine'      => null,
            'options'     => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('DB_MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_SSL_CERT => env('DB_MYSQL_ATTR_SSL_CERT'),
            ]) : [],
        ],

        'testing' => [
            'driver'         => 'mysql',
            //'url'            => env('DB_TEST_DATABASE_URL'),
            'host'           => '127.0.0.1',
            'database'       => 'freescout-test',
            'username'       => env('DB_TEST_USERNAME', 'freescout-test'),
            'password'       => env('DB_TEST_PASSWORD', 'freescout-test'),
            //'port'           => env('DB_TEST_PORT', '3306'),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => env('DB_TABLE_PREFIX', ''),
            //'prefix_indexes' => true,
            'strict'         => false,
            'engine'      => null,
        ],

        'testing_pgsql' => [
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'port'     => '5432',
            'database' => 'freescout-test',
            'username' => env('DB_TEST_USERNAME', 'freescout-test'),
            'password' => env('DB_TEST_PASSWORD', 'freescout-test'),
            'charset'  => 'utf8',
            'prefix'   => env('DB_TABLE_PREFIX', ''),
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => env('DB_TABLE_PREFIX', ''),
            'schema'   => 'public',
            'sslmode'  => env('DB_PGSQL_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => env('DB_TABLE_PREFIX', ''),
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

        'client' => 'predis',

        'default' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 0,
        ],

    ],

];
