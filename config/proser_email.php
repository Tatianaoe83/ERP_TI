<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración para Servidor Proser.com.mx
    |--------------------------------------------------------------------------
    |
    | Configuración específica para el servidor de correo proser.com.mx
    |
    */

    'proser' => [
        'imap' => [
            'host' => 'proser.com.mx',
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => false,
        ],
        'smtp' => [
            'host' => 'proser.com.mx',
            'port' => 465,
            'encryption' => 'ssl',
        ],
        'pop3' => [
            'host' => 'proser.com.mx',
            'port' => 995,
            'encryption' => 'ssl',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Autenticación
    |--------------------------------------------------------------------------
    |
    | Configuración de autenticación para el servidor
    |
    */

    'auth' => [
        'username' => env('MAIL_USERNAME', 'tordonez@proser.com.mx'),
        'password' => env('MAIL_PASSWORD'),
        'require_auth' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de SSL/TLS
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad SSL/TLS
    |
    */

    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'cafile' => null,
        'capath' => null,
    ],
];
