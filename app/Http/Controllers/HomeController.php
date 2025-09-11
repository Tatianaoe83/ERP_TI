<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleados;
use App\Models\Equipos;
use App\Models\Insumos;
use App\Models\LineasTelefonicas;
use App\Models\InventarioEquipo;
use App\Models\InventarioInsumo;
use App\Models\InventarioLineas;
use App\Models\Obras;
use App\Models\Gerencia;
use App\Models\UnidadesDeNegocio;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Estadísticas de empleados
        $totalEmpleados = Empleados::count();
        $empleadosActivos = Empleados::where('Estado', true)->count();
        $empleadosInactivos = Empleados::where('Estado', false)->count();

        // Estadísticas de inventario
        $totalEquipos = Equipos::count();
        $equiposAsignados = InventarioEquipo::count();
        

        $totalInsumos = Insumos::count();
        $insumosAsignados = InventarioInsumo::count();
        

        $totalLineas = LineasTelefonicas::count();
        $lineasAsignadas = InventarioLineas::count();
        $lineasDisponibles = $totalLineas - $lineasAsignadas;

        // Estadísticas de obras y gerencias
        $totalObras = Obras::count();
        $totalGerencias = Gerencia::count();
        $totalUnidadesNegocio = UnidadesDeNegocio::count();

        // Empleados con más inventario asignado
        $empleadosConInventario = DB::table('empleados')
            ->select('empleados.NombreEmpleado', 'empleados.EmpleadoID')
            ->selectRaw('
                (SELECT COUNT(*) FROM inventarioequipo WHERE inventarioequipo.EmpleadoID = empleados.EmpleadoID) +
                (SELECT COUNT(*) FROM inventarioinsumo WHERE inventarioinsumo.EmpleadoID = empleados.EmpleadoID) +
                (SELECT COUNT(*) FROM inventariolineas WHERE inventariolineas.EmpleadoID = empleados.EmpleadoID) as total_inventario
            ')
            ->where('empleados.Estado', 1)
            ->having('total_inventario', '>', 0)
            ->orderBy('total_inventario', 'desc')
            ->limit(5)
            ->get();

        // Estadísticas por gerencia (optimizada)
        $estadisticasPorGerencia = DB::table('gerencia')
            ->leftJoin('departamentos', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
            ->leftJoin('puestos', 'departamentos.DepartamentoID', '=', 'puestos.DepartamentoID')
            ->leftJoin('empleados', 'puestos.PuestoID', '=', 'empleados.PuestoID')
            ->select('gerencia.NombreGerencia')
            ->selectRaw('COUNT(DISTINCT empleados.EmpleadoID) as total_empleados')
            ->selectRaw('COUNT(CASE WHEN empleados.Estado = 1 THEN 1 END) as empleados_activos')
            ->groupBy('gerencia.GerenciaID', 'gerencia.NombreGerencia')
            ->having('total_empleados', '>', 0)
            ->orderBy('total_empleados', 'desc')
            ->limit(4)
            ->get();

        $stats = [
            'empleados' => [
                'total' => $totalEmpleados,
                'activos' => $empleadosActivos,
                'inactivos' => $empleadosInactivos,
            ],
            'inventario' => [
                'equipos' => [
                    'total' => $totalEquipos,
                    'asignados' => $equiposAsignados
                ],
                'insumos' => [
                    'total' => $totalInsumos,
                    'asignados' => $insumosAsignados,
                ],
                'lineas' => [
                    'total' => $totalLineas,
                    'asignadas' => $lineasAsignadas,
                    'disponibles' => $lineasDisponibles,
                ],
            ],
            'organizacion' => [
                'obras' => $totalObras,
                'gerencias' => $totalGerencias,
                'unidades_negocio' => $totalUnidadesNegocio,
            ],
            'empleados_con_inventario' => $empleadosConInventario,
            'estadisticas_gerencia' => $estadisticasPorGerencia,
        ];

        return view('home', compact('stats'));
    }
}
