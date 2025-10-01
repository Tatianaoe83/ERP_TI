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
    public function index(Request $request)
    {
        try {
            // Estadísticas de empleados
            $totalEmpleados = Empleados::count();
            $empleadosActivos = Empleados::where('Estado', true)->where('tipo_persona', 'FISICA')->count();

        // Estadísticas de inventario
        $totalEquipos = Equipos::count();
        $equiposAsignados = InventarioEquipo::count();
        

        $totalInsumos = Insumos::count();
        $insumosAsignados = InventarioInsumo::count();
        

        $totalLineas = LineasTelefonicas::where('Activo',true)->count();
        $lineasAsignadas = InventarioLineas::count();
        $lineasDisponibles = $totalLineas - $lineasAsignadas;

        // Estadísticas de obras y gerencias
        $totalObras = Obras::where('Estado', true)->count();
        $totalGerencias = Gerencia::where('Estado', true)->count();
        $totalUnidadesNegocio = UnidadesDeNegocio::where('Estado', true)->count();

        // Insumos por categoría 'licencia' agrupados por nombre con paginación
        $insumosPorLicencia = $this->getInsumosPorLicencia($request);

        // Equipos por categoría específica (LAPTOP, PC ESCRITORIO, IMPRESORA)
        $equiposPorCategoria = $this->getEquiposPorCategoria();

        // Estadísticas por gerencia (optimizada)
        $estadisticasPorGerencia = DB::table('gerencia')
            ->leftJoin('departamentos', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
            ->leftJoin('puestos', 'departamentos.DepartamentoID', '=', 'puestos.DepartamentoID')
            ->leftJoin('empleados', 'puestos.PuestoID', '=', 'empleados.PuestoID')
            ->select('gerencia.NombreGerencia')
            ->selectRaw('COUNT(DISTINCT empleados.EmpleadoID) as total_empleados')
            ->selectRaw('COUNT(CASE WHEN empleados.Estado = 1 THEN 1 END) as empleados_activos')
            ->where('gerencia.Estado', true)
            ->where('empleados.Estado', true)
            ->where('empleados.tipo_persona', 'FISICA')
            ->groupBy('gerencia.GerenciaID', 'gerencia.NombreGerencia')
            ->having('total_empleados', '>', 0)
            ->orderBy('total_empleados', 'desc')
            ->limit(7)
            ->get();

        $stats = [
            'empleados' => [
                'total' => $totalEmpleados,
                'activos' => $empleadosActivos,
               
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
            'insumos_por_licencia' => $insumosPorLicencia,
            'equipos_por_categoria' => $equiposPorCategoria,
            'estadisticas_gerencia' => $estadisticasPorGerencia,
        ];

            // Si es request AJAX, retornar solo la vista parcial
            if ($request->ajax()) {
                return view('partials.insumos-licencia', compact('stats'))->render();
            }
            
            return view('home', compact('stats'));
        } catch (\Exception $e) {
            // En caso de error, devolver valores por defecto
            $stats = [
                'empleados' => ['total' => 0, 'activos' => 0],
                'inventario' => [
                    'equipos' => ['total' => 0, 'asignados' => 0],
                    'insumos' => ['total' => 0, 'asignados' => 0],
                    'lineas' => ['total' => 0, 'asignadas' => 0, 'disponibles' => 0],
                ],
                'organizacion' => ['obras' => 0, 'gerencias' => 0, 'unidades_negocio' => 0],
                'insumos_por_licencia' => collect(),
                'equipos_por_categoria' => collect(),
                'estadisticas_gerencia' => collect(),
                'error' => true,
                'error_message' => 'Error al cargar las estadísticas del dashboard.'
            ];
            
            return view('home', compact('stats'));
        }
    }

    /**
     * Obtener insumos por licencia con paginación
     */
    private function getInsumosPorLicencia(Request $request)
    {
        return DB::table('inventarioinsumo')
            ->select('NombreInsumo')
            ->selectRaw('COUNT(InventarioID) as total_inventario')
            ->where('CateogoriaInsumo', 'LICENCIA')
            ->groupBy('NombreInsumo')
            ->orderBy('total_inventario', 'desc')
            ->paginate(7);
    }

    /**
     * Paginación AJAX para insumos por licencia
     */
    public function insumosLicenciaPagination(Request $request)
    {
        try {
            $insumosPorLicencia = $this->getInsumosPorLicencia($request);
            $stats = ['insumos_por_licencia' => $insumosPorLicencia];
            
            return view('partials.insumos-licencia', compact('stats'))->render();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar los datos'], 500);
        }
    }

    /**
     * Obtener equipos por categoría específica
     */
    private function getEquiposPorCategoria()
    {
        return DB::table('inventarioequipo')
            ->select('CategoriaEquipo')
            ->selectRaw('COUNT(InventarioID) as total_inventario')
            ->whereIn('CategoriaEquipo', ['LAPTOP', 'PC ESCRITORIO', 'IMPRESORA'])
            ->groupBy('CategoriaEquipo')
            ->orderBy('total_inventario', 'desc')
            ->get();
    }
}
