<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

//agregamos el modelo de permisos de spatie
use Spatie\Permission\Models\Permission;

class SeederTablaPermisos extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permisos = [

            //Operaciones sobre tabla unidadesdenegocio
            'ver-unidadesdenegocio',
            'crear-unidadesdenegocio',
            'editar-unidadesdenegocio',
            'borrar-unidadesdenegocio',

            //Operaciones sobre tabla gerencias
            'ver-gerencias',
            'crear-gerencias',
            'editar-gerencias',
            'borrar-gerencias',

            //Operaciones sobre tabla obras
            'ver-obras',
            'crear-obras',
            'editar-obras',
            'borrar-obras',

            //Operaciones sobre tabla departamentos
            'ver-departamentos',
            'crear-departamentos',
            'editar-departamentos',
            'borrar-departamentos',

            //Operaciones sobre tabla puestos
            'ver-puestos',
            'crear-puestos',
            'editar-puestos',
            'borrar-puestos',

            //Operaciones sobre tabla empleados
            'ver-empleados',
            'crear-empleados',
            'editar-empleados',
            'borrar-empleados',

            //Operaciones sobre tabla Lineastelefonicas
            'ver-Lineastelefonicas',
            'crear-Lineastelefonicas',
            'editar-Lineastelefonicas',
            'borrar-Lineastelefonicas',

            //Operaciones sobre tabla equipos
            'ver-equipos',
            'crear-equipos',
            'editar-equipos',
            'borrar-equipos',

            //Operaciones sobre tabla insumos
            'ver-insumos',
            'crear-insumos',
            'editar-insumos',
            'borrar-insumos',

            //Operaciones sobre tabla categorias
            'ver-categorias',
            'crear-categorias',
            'editar-categorias',
            'borrar-categorias',

            //Operaciones sobre tabla planes
            'ver-planes',
            'crear-planes',
            'editar-planes',
            'borrar-planes',

            //Operaciones sobre tabla inventario
            'transferir-inventario',
            'cartas-inventario',
            'asignar-inventario',

            //Operaciones sobre tabla usuarios
            'ver-usuarios',
            'crear-usuarios',
            'editar-usuarios',
            'borrar-usuarios',

            //Operaciones sobre tabla roles
            'ver-rol',
            'crear-rol',
            'editar-rol',
            'borrar-rol',

         
        ];

        foreach($permisos as $permiso) {
            Permission::create(['name'=>$permiso]);
        }
    }
}
