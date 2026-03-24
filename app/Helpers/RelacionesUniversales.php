<?php

namespace App\Helpers;

/**
 * REGLAS IMPORTANTES (DIRECCIONALIDAD INTELIGENTE):
 * Gracias al nuevo JoinHelper con Dijkstra, las rutas ahora pueden ser 
 * bidireccionales. El sistema penaliza rutas por 'unidadesdenegocio' u 'obras' 
 * para evitar el producto cartesiano, pero permite el acceso si es necesario.
 */
class RelacionesUniversales
{
    public static function get(): array
    {
        return [
            // ─── 1. NIVEL INVENTARIO FÍSICO ───
            'inventarioequipo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioequipo.EmpleadoID'],
            ],
            'inventarioinsumo' => [
                'empleados' => ['empleados.EmpleadoID', '=', 'inventarioinsumo.EmpleadoID'],
                'insumos'   => ['insumos.InsumoID',     '=', 'inventarioinsumo.InsumoID'],
            ],
            'inventariolineas' => [
                'empleados'         => ['empleados.EmpleadoID',      '=', 'inventariolineas.EmpleadoID'],
                'lineastelefonicas' => ['lineastelefonicas.LineaID', '=', 'inventariolineas.LineaID'],
                'obras'             => ['obras.ObraID',              '=', 'inventariolineas.ObraID'],
            ],

            // ─── 2. NIVEL CATÁLOGOS DE INVENTARIO ───
            'equipos' => [
            'categorias'       => ['categorias.ID', '=', 'equipos.CategoriaID'],
            ],
            'insumos' => [
                'categorias'       => ['categorias.ID', '=', 'insumos.CategoriaID'],
                'inventarioinsumo' => ['inventarioinsumo.InsumoID', '=', 'insumos.InsumoID'],
            ],
            'lineastelefonicas' => [
                'obras'            => ['obras.ObraID', '=', 'lineastelefonicas.ObraID'],
                'planes'           => ['planes.ID',    '=', 'lineastelefonicas.PlanID'],
                'inventariolineas' => ['inventariolineas.LineaID', '=', 'lineastelefonicas.LineaID'],
            ],
            'planes' => [
                'companiaslineastelefonicas' => ['companiaslineastelefonicas.ID', '=', 'planes.CompaniaID'],
                'lineastelefonicas'          => ['lineastelefonicas.PlanID', '=', 'planes.ID'],
            ],
            'categorias' => [
                'tiposdecategorias' => ['tiposdecategorias.ID', '=', 'categorias.TipoID'],
                'equipos'           => ['equipos.CategoriaID', '=', 'categorias.ID'],
                'insumos'           => ['insumos.CategoriaID', '=', 'categorias.ID'],
            ],
            'tiposdecategorias' => [
                'categorias' => ['categorias.TipoID', '=', 'tiposdecategorias.ID'],
            ],
            'companiaslineastelefonicas' => [
                'planes' => ['planes.CompaniaID', '=', 'companiaslineastelefonicas.ID'],
            ],

            // ─── 3. NIVEL RECURSOS HUMANOS Y JERARQUÍA ───
            'empleados' => [
                'puestos'          => ['puestos.PuestoID', '=', 'empleados.PuestoID'],
                'obras'            => ['obras.ObraID',     '=', 'empleados.ObraID'],
                'inventarioequipo' => ['inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID'],
                'inventarioinsumo' => ['inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID'],
                'inventariolineas' => ['inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID'],
            ],
            'puestos' => [
                'departamentos' => ['departamentos.DepartamentoID', '=', 'puestos.DepartamentoID'],
                'empleados'     => ['empleados.PuestoID', '=', 'puestos.PuestoID'],
            ],
            'departamentos' => [
                'gerencia' => ['gerencia.GerenciaID', '=', 'departamentos.GerenciaID'],
                'puestos'  => ['puestos.DepartamentoID', '=', 'departamentos.DepartamentoID'],
            ],
            'gerencia' => [
                'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID'],
                'departamentos'     => ['departamentos.GerenciaID', '=', 'gerencia.GerenciaID'],
            ],
            'obras' => [
                'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'obras.UnidadNegocioID'],
                'empleados'         => ['empleados.ObraID', '=', 'obras.ObraID'],
                'lineastelefonicas' => ['lineastelefonicas.ObraID', '=', 'obras.ObraID'],
            ],
            'unidadesdenegocio' => [
                'obras'    => ['obras.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID'],
                'gerencia' => ['gerencia.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID'],
            ],
        ];
    }
}
