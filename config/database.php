<?php

use Illuminate\Support\Str;

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

    'default' => env('DB_CONNECTION', 'manager'),

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
            'search_path' => 'public',
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
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

        'manager' => [
            'driver' => 'mysql',
            'url' => env('MANAGER_DATABASE_URL'),
            'host' => env('MANAGER_DB_HOST', '127.0.0.1'),
            'port' => env('MANAGER_DB_PORT', '3306'),
            'database' => env('MANAGER_DB_DATABASE', 'forge'),
            'username' => env('MANAGER_DB_USERNAME', 'forge'),
            'password' => env('MANAGER_DB_PASSWORD', ''),
            'unix_socket' => env('MANAGER_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => 'nm_',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MANAGER_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'discord_links' => [
            'driver' => 'mysql',
            'url' => env('DISCORD_LINKS_DATABASE_URL'),
            'host' => env('DISCORD_LINKS_DB_HOST', '127.0.0.1'),
            'port' => env('DISCORD_LINKS_DB_PORT', '3306'),
            'database' => env('DISCORD_LINKS_DB_DATABASE', 'discord_links'),
            'username' => env('DISCORD_LINKS_DB_USERNAME', 'forge'),
            'password' => env('DISCORD_LINKS_DB_PASSWORD', ''),
            'unix_socket' => env('DISCORD_LINKS_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_520_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('DISCORD_LINKS_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'zutils' => [
            'driver' => 'mysql',
            'url' => env('ZUTILS_DATABASE_URL'),
            'host' => env('ZUTILS_DB_HOST', '127.0.0.1'),
            'port' => env('ZUTILS_DB_PORT', '3306'),
            'database' => env('ZUTILS_DB_DATABASE', 'zutils'),
            'username' => env('ZUTILS_DB_USERNAME', 'forge'),
            'password' => env('ZUTILS_DB_PASSWORD', ''),
            'unix_socket' => env('ZUTILS_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('ZUTILS_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'survivaldb' => [
            'driver' => 'mysql',
            'url' => env('SURVIVALDB_DATABASE_URL'),
            'host' => env('SURVIVALDB_DB_HOST', '127.0.0.1'),
            'port' => env('SURVIVALDB_DB_PORT', '3306'),
            'database' => env('SURVIVALDB_DB_DATABASE', 'survivaldb'),
            'username' => env('SURVIVALDB_DB_USERNAME', 'forge'),
            'password' => env('SURVIVALDB_DB_PASSWORD', ''),
            'unix_socket' => env('SURVIVALDB_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_520_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('SURVIVALDB_MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_TIMEOUT => 60, // Increase connection timeout
            ]) : [],
        ],

        'ervoto' => [
            'driver' => 'mysql',
            'url' => env('ERVOTO_DATABASE_URL'),
            'host' => env('ERVOTO_DB_HOST', '127.0.0.1'),
            'port' => env('ERVOTO_DB_PORT', '3306'),
            'database' => env('ERVOTO_DB_DATABASE', 'ervoto'),
            'username' => env('ERVOTO_DB_USERNAME', 'forge'),
            'password' => env('ERVOTO_DB_PASSWORD', ''),
            'unix_socket' => env('ERVOTO_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_520_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('ERVOTO_MYSQL_ATTR_SSL_CA'),
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
