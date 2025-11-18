<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración IMAP para Office 365
    |--------------------------------------------------------------------------
    |
    | Configuración específica para conectar con Office 365/Outlook
    |
    */

    'imap' => [
        'host' => env('IMAP_HOST', 'outlook.office365.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => env('IMAP_VALIDATE_CERT', false),
        'options' => [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración SMTP para Office 365
    |--------------------------------------------------------------------------
    |
    | Configuración específica para enviar correos con Office 365
    |
    */

    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.office365.com'),
        'port' => env('MAIL_PORT', 587),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Autenticación Moderna
    |--------------------------------------------------------------------------
    |
    | Para Office 365, puede ser necesario usar autenticación moderna
    |
    */

    'modern_auth' => [
        'enabled' => env('OFFICE365_MODERN_AUTH', false),
        'client_id' => env('OFFICE365_CLIENT_ID'),
        'client_secret' => env('OFFICE365_CLIENT_SECRET'),
        'tenant_id' => env('OFFICE365_TENANT_ID'),
    ],
];
