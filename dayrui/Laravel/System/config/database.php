<?php

use Illuminate\Support\Str;


$config = [

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

    'default' => 'mysql',

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



        'mysql' => [
            'driver' => 'mysql',
            'url' => '',
            'host' => '',
            'port' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
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
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];

!defined('CONFIGPATH') && define('CONFIGPATH', dirname(__FILE__));
if (is_file(CONFIGPATH.'database.php')) {

    $db = [];
    require CONFIGPATH.'database.php';
    $config['connections']['mysql']['host'] = $db['default']['hostname'];
    $config['connections']['mysql']['username'] = $db['default']['username'];
    $config['connections']['mysql']['password'] = $db['default']['password'];
    $config['connections']['mysql']['database'] = $db['default']['database'];
    $config['connections']['mysql']['prefix'] = $db['default']['DBPrefix'];

    unset($db['default']);

    if (isset($db['failover']) && $db['failover']) {
        // 备用库
        unset($db['failover']);
    }


    if ($db) {
        foreach ($db as $name => $t) {
            $config['connections'][$name] = $config['connections']['mysql'];
            $config['connections'][$name]['host'] = $t['hostname'];
            $config['connections'][$name]['username'] = $t['username'];
            $config['connections'][$name]['password'] = $t['password'];
            $config['connections'][$name]['database'] = $t['database'];
            $config['connections'][$name]['prefix'] = $t['DBPrefix'];
        }
    }
}

return $config;
