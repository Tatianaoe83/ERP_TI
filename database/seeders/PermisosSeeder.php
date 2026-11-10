<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermisosSeeder extends Seeder
{
    public const PERMISOS = [
        'asignar-inventario',
        'borrar-auditorias',
        'borrar-categorias',
        'borrar-departamentos',
        'borrar-empleados',
        'borrar-equipos',
        'borrar-gerencias',
        'borrar-insumos',
        'borrar-Lineastelefonicas',
        'borrar-obras',
        'borrar-planes',
        'borrar-puestos',
        'borrar-reportes',
        'borrar-rol',
        'borrar-unidadesdenegocio',
        'borrar-usuarios',
        'buscar-informe',
        'cartas-inventario',
        'crear-auditorias',
        'crear-categorias',
        'crear-departamentos',
        'crear-empleados',
        'crear-equipos',
        'crear-facturas',
        'crear-gerencias',
        'crear-insumos',
        'crear-Lineastelefonicas',
        'crear-obras',
        'crear-planes',
        'crear-puestos',
        'crear-reportes',
        'crear-rol',
        'crear-unidadesdenegocio',
        'crear-usuarios',
        'editar-auditorias',
        'editar-categorias',
        'editar-conf-presupuesto',
        'editar-departamentos',
        'editar-empleados',
        'editar-equipos',
        'editar-gerencias',
        'editar-insumos',
        'editar-Lineastelefonicas',
        'editar-mantenimientos',
        'editar-obras',
        'editar-planes',
        'editar-puestos',
        'editar-reportes',
        'editar-rol',
        'editar-unidadesdenegocio',
        'editar-usuarios',
        'exportar-reportes',
        'exportar-reportes-especificos',
        'generar-cortes',
        'tickets.ajustar-metricas',
        'tickets.pausar',
        'tickets.gestionar-tareas',
        'tickets.notificaciones',
        'tickets.ver-creador-tarea',
        'tickets.ver-productividad',
        'tickets.ver-tareas',
        'transferir-inventario',
        'ver-auditorias',
        'ver-categorias',
        'ver-comparativa',
        'ver-compras',
        'ver-control',
        'ver-cortes',
        'ver-dashboard',
        'ver-departamentos',
        'ver-empleados',
        'ver-equipos',
        'ver-facturas',
        'ver-gerencias',
        'ver-informe',
        'ver-insumos',
        'ver-inventario',
        'ver-Lineastelefonicas',
        'ver-mantenimientos',
        'ver-mantenimientos-compras',
        'ver-obras',
        'ver-planes',
        'ver-presupuesto',
        'ver-presupuestos',
        'ver-puestos',
        'ver-reportes',
        'ver-reportes-especificos',
        'ver-rol',
        'ver-soporte',
        'ver-unidadesdenegocio',
        'ver-usuarios',
    ];

    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISOS as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
    }
}
