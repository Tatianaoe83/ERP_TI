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
}
