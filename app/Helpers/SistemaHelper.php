<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Spatie\Permission\PermissionRegistrar;

class SistemaHelper
{
    public static function establecerSistema(string $sistema)
    {
        Session::put('sistema_activo', $sistema);

        // Refrescar caché de permisos de Spatie
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