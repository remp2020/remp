<?php

if (!function_exists('configure_redis')) {
    function configure_redis($database)
    {
        // If the app uses Redis Sentinel, different configuration is necessary.
        //
        // Default database and password need to be set within options.parameters to be actually used.
        if ($sentinelService = env('REDIS_SENTINEL_SERVICE')) {
            $redisClient = env('REDIS_CLIENT', 'predis');
            if ($redisClient !== 'predis') {
                throw new \Exception("Unable to configure Redis Sentinel for client '{$redisClient}', only 'predis' is supported. You can configure the client via 'REDIS_CLIENT' environment variable.");
            }

            $redisUrl = env('REDIS_URL');
            if ($redisUrl === null) {
                throw new \Exception("Unable to configure Redis Sentinel. Use 'REDIS_URL' environment variable to configure comma-separated sentinel instances.");
            }

            $config = explode(',', $redisUrl);
            $config['options'] = [
                'replication' => 'sentinel',
                'service' => $sentinelService,
                'parameters' => [
                    'database' => $database,
                ],
            ];
            if ($password = env('REDIS_PASSWORD')) {
                $config['options']['parameters']['password'] = $password;
            }
            return $config;
        }

        // default configuration supporting both url-based and host-port-database-based config.
        return [
            'url' => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => $database,
        ];
    }
}

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
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'timezone'  => '+00:00',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
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

        // Default Laravel 6+ is phpredis, but to avoid adding additional dependency (PHP extension)
        // we still use predis
        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'predis'),
            'prefix' => env('REDIS_PREFIX', ''),
        ],

        'default' => configure_redis(env('REDIS_DEFAULT_DATABASE', '0')),
        'session' => configure_redis(env('REDIS_SESSION_DATABASE', '1')),
        'cache' => configure_redis(env('REDIS_CACHE_DATABASE', '2')),
        'queue' => configure_redis(env('REDIS_QUEUE_DATABASE', '3')),

    ],

];
