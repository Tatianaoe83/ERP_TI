<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\File;

class SistemaHelper
{
    public static function establecerSistema(string $sistema)
    {
        Session::put('sistema_activo', $sistema);

        // Actualizar el archivo .env dinámicamente
        self::actualizarEnv($sistema);

        // Refrescar caché de permisos de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function obtenerSistema(): string
    {
        return Session::get('sistema_activo', 'inventario'); // valor por defecto
    }

    public static function obtenerConexion(): string
    {
        $sistema = self::obtenerSistema();
        
        return match ($sistema) {
            'presupuesto' => 'mysql_presupuesto',
            'presupuesto 2026' => 'mysql_presupuesto2026',
            default => 'mysql_inventario'
        };
    }

    /**
     * Actualizar el archivo .env con la base de datos correspondiente al sistema
     */
    public static function actualizarEnv(string $sistema): bool
    {
        $envPath = base_path('.env');
        
        // Si no existe .env, crear uno básico
        if (!File::exists($envPath)) {
            self::crearEnvBase();
        }

        $envContent = File::get($envPath);
        
        // Determinar qué base de datos usar según el sistema
        $database = match ($sistema) {
            'presupuesto' => 'unidplay_presupuestoscontrol',
            'presupuesto 2026' => 'unidplay_presupuestoscontrol2026',
            default => 'unidplay_controlinventarioti'
        };

        $connection = match ($sistema) {
            'presupuesto' => 'mysql_presupuesto',
            'presupuesto 2026' => 'mysql_presupuesto2026',
            default => 'mysql_inventario'
        };

        // Actualizar las variables de entorno
        $envContent = self::updateEnvVariable($envContent, 'DB_CONNECTION', $connection);
        $envContent = self::updateEnvVariable($envContent, 'DB_DATABASE', $database);

        // Escribir el archivo actualizado
        return File::put($envPath, $envContent) !== false;
    }

    /**
     * Crear un archivo .env base si no existe
     */
    private static function crearEnvBase(): void
    {
        $envContent = "APP_NAME=ERP_TI
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql_inventario
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=unidplay_controlinventarioti
DB_USERNAME=
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME=\"\${APP_NAME}\"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY=\"\${PUSHER_APP_KEY}\"
MIX_PUSHER_APP_CLUSTER=\"\${PUSHER_APP_CLUSTER}\"
";
        
        File::put(base_path('.env'), $envContent);
    }

    /**
     * Actualizar una variable específica en el contenido del .env
     */
    private static function updateEnvVariable(string $envContent, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*$/m";
        $replacement = "{$key}={$value}";
        
        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, $replacement, $envContent);
        } else {
            // Si la variable no existe, agregarla al final
            return $envContent . "\n{$replacement}";
        }
    }

    /**
     * Obtener el nombre de la base de datos según el sistema activo
     */
    public static function obtenerBaseDatos(): string
    {
        $sistema = self::obtenerSistema();
        
        return match ($sistema) {
            'presupuesto' => 'unidplay_presupuestoscontrol',
            'presupuesto 2026' => 'unidplay_presupuestoscontrol2026',
            default => 'unidplay_controlinventarioti'
        };
    }
}
