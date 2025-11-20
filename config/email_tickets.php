<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración IMAP para correos entrantes
    |--------------------------------------------------------------------------
    |
    | Configuración para conectar con el servidor IMAP y procesar correos
    | entrantes automáticamente.
    |
    */

    'imap' => [
        'host' => env('IMAP_HOST', 'imap-mail.outlook.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => env('IMAP_VALIDATE_CERT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de threading de correos
    |--------------------------------------------------------------------------
    |
    | Configuración para mantener conversaciones de correos en el mismo hilo
    | usando Message-ID y Thread-ID.
    |
    */

    'threading' => [
        'enabled' => env('EMAIL_THREADING_ENABLED', true),
        'domain' => env('APP_URL', 'localhost'),
        'prefix' => env('EMAIL_THREAD_PREFIX', 'ticket'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de procesamiento automático
    |--------------------------------------------------------------------------
    |
    | Configuración para el procesamiento automático de correos entrantes.
    |
    */

    'processing' => [
        'auto_process' => env('EMAIL_AUTO_PROCESS', true),
        'process_interval' => env('EMAIL_PROCESS_INTERVAL', 300), // 5 minutos
        'max_emails_per_batch' => env('EMAIL_MAX_BATCH', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de notificaciones
    |--------------------------------------------------------------------------
    |
    | Configuración para las notificaciones por correo.
    |
    */

    'notifications' => [
        'send_confirmations' => env('EMAIL_SEND_CONFIRMATIONS', true),
        'send_updates' => env('EMAIL_SEND_UPDATES', true),
        'send_reminders' => env('EMAIL_SEND_REMINDERS', false),
    ],
];
