<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default IMAP Account
    |--------------------------------------------------------------------------
    |
    | This is the default account that will be used for IMAP operations.
    | You can specify multiple accounts in the 'accounts' array below.
    |
    */

    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | IMAP Accounts
    |--------------------------------------------------------------------------
    |
    | Here you can configure multiple IMAP accounts. Each account should
    | have a unique identifier and the necessary connection parameters.
    |
    */

    'accounts' => [
        'default' => [
            'host'          => env('IMAP_HOST', 'proser.com.mx'),
            'port'          => env('IMAP_PORT', 993),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_VALIDATE_CERT', false),
            'username'      => env('IMAP_USERNAME', 'tordonez@proser.com.mx'),
            'password'      => env('IMAP_PASSWORD'),
            'protocol'      => 'imap',
        ],

        'gmail' => [
            'host'          => 'imap.gmail.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => env('GMAIL_USERNAME'),
            'password'      => env('GMAIL_PASSWORD'),
            'protocol'      => 'imap',
        ],

        'outlook' => [
            'host'          => 'outlook.office365.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => env('OUTLOOK_USERNAME'),
            'password'      => env('OUTLOOK_PASSWORD'),
            'protocol'      => 'imap',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IMAP Options
    |--------------------------------------------------------------------------
    |
    | Additional options for IMAP connections.
    |
    */

    'options' => [
        'delimiter' => '/',
        'fetch' => \Webklex\PHPIMAP\IMAP::FT_PEEK,
        'sequence' => \Webklex\PHPIMAP\IMAP::ST_UID,
        'fetch_body' => true,
        'fetch_flags' => true,
        'open' => [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for IMAP operations to improve performance.
    |
    */

    'cache' => [
        'enabled' => env('IMAP_CACHE_ENABLED', true),
        'driver' => env('IMAP_CACHE_DRIVER', 'file'),
        'key' => env('IMAP_CACHE_KEY', 'laravel-imap'),
        'ttl' => env('IMAP_CACHE_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for IMAP operations.
    |
    */

    'logging' => [
        'enabled' => env('IMAP_LOGGING_ENABLED', true),
        'level' => env('IMAP_LOGGING_LEVEL', 'info'),
    ],
];
