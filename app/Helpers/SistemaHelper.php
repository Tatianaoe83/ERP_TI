<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SistemaHelper
{
    public static function establecerSistema(string $sistema)
    {
        Session::put('sistema_activo', $sistema);

        
        $conexion = match ($sistema) {
            'presupuesto' => 'mysql_presupuesto',
            default => 'mysql_inventario',
        };

       
        Config::set('database.default_sistema', $conexion);

        // Opcional: si usas esa conexión explícitamente, también puedes hacer:
        // DB::purge($conexion);
        // DB::reconnect($conexion);

        
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function obtenerConexion(): string
    {
        return Session::get('sistema_activo') === 'presupuesto'
            ? 'mysql_presupuesto'
            : 'mysql_inventario';
    }
}

?>