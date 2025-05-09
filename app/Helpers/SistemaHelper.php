<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Spatie\Permission\PermissionRegistrar;

class SistemaHelper
{
    public static function establecerSistema(string $sistema)
    {
        $conexion = $sistema === 'presupuesto' ? 'mysql_presupuesto' : 'mysql_inventario';
        Session::put('sistema_activo', $sistema);

        // Limpia la caché de permisos de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Guarda conexión activa como referencia
        config(['database.default_sistema' => $conexion]);
    }

    public static function obtenerConexion(): string
    {
        return Session::get('sistema_activo') === 'presupuesto' ? 'mysql_presupuesto' : 'mysql_inventario';
    }
}

?>