<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleados;
use App\Models\Equipos;
use App\Models\Insumos;
use App\Models\LineasTelefonicas;
use App\Models\InventarioEquipo;
use App\Models\InventarioInsumo;
use App\Models\Obras;
use App\Models\Gerencia;
use App\Models\UnidadesDeNegocio;
use App\Models\TicketMantenimiento;
use DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-dashboard|ver-compras');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $tipoDashboard = $this->resolverTipoDashboard($user);

        try {
            $stats = null;
            $statsCompras = null;

            if (in_array($tipoDashboard, ['informatica', 'completo'], true)) {
                $stats = $this->buildInformaticaStats($request);
            }

            if (in_array($tipoDashboard, ['compras', 'completo'], true)) {
                $statsCompras = $this->buildComprasStats();
            }

            if ($request->ajax()) {
                return view('partials.insumos-licencia', compact('stats'))->render();
            }

            return view('home', compact('stats', 'statsCompras', 'tipoDashboard'));
        } catch (\Exception $e) {
            $stats = $this->statsInformaticaVacios();
            $statsCompras = $this->statsComprasVacios();

            return view('home', [
                'stats' => $stats,
                'statsCompras' => $statsCompras,
                'tipoDashboard' => $tipoDashboard,
            ]);
        }
    }

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

    private function resolverTipoDashboard($user): string
    {
        $tieneInformatica = $user->can('ver-dashboard');
        $tieneCompras = $user->can('ver-compras');

        if ($tieneInformatica && $tieneCompras) {
            return 'completo';
        }

        if ($tieneCompras) {
            return 'compras';
        }

        return 'informatica';
    }

    private function buildInformaticaStats(Request $request): array
    {
        $totalEmpleados = Empleados::count();
        $empleadosActivos = Empleados::where('Estado', true)->where('tipo_persona', 'FISICA')->count();

        $totalEquipos = Equipos::count();
        $equiposAsignados = InventarioEquipo::count();

        $totalInsumos = Insumos::count();
        $insumosAsignados = InventarioInsumo::count();

        $totalLineas = LineasTelefonicas::where('Activo', true)->count();
        $lineasLibres = LineasTelefonicas::where('Activo', true)->where('Disponible', 1)->count();
        $lineasReferenciados = LineasTelefonicas::where('lineastelefonicas.Activo', true)
            ->where('lineastelefonicas.Disponible', 0)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('inventariolineas as il')
                    ->join('empleados as e', 'e.EmpleadoID', '=', 'il.EmpleadoID')
                    ->whereColumn('il.LineaID', 'lineastelefonicas.LineaID')
                    ->where('e.tipo_persona', 'REFERENCIADO');
            })
            ->count();
        $lineasAsignadasPersonaFisica = LineasTelefonicas::where('lineastelefonicas.Activo', true)
            ->where('lineastelefonicas.Disponible', 0)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('inventariolineas as il')
                    ->join('empleados as e', 'e.EmpleadoID', '=', 'il.EmpleadoID')
                    ->whereColumn('il.LineaID', 'lineastelefonicas.LineaID')
                    ->where('e.tipo_persona', 'FISICA');
            })
            ->count();
        $lineasDisponibles = $lineasLibres + $lineasReferenciados;

        $totalObras = Obras::where('Estado', true)->count();
        $totalGerencias = Gerencia::where('Estado', true)->count();
        $totalUnidadesNegocio = UnidadesDeNegocio::where('Estado', true)->count();

        $anioActual = now()->year;
        $mantenimientosAnio = ['pendientes' => 0, 'realizados' => 0, 'anio' => $anioActual];

        if (Schema::hasTable('mantenimientos') && Schema::hasColumn('mantenimientos', 'AnioProgramacion')) {
            $mantenimientosBase = DB::table('mantenimientos as m')
                ->leftJoin('inventarioequipo as ie', 'ie.InventarioID', '=', 'm.InventarioID')
                ->leftJoin('empleados as e', 'e.EmpleadoID', '=', 'ie.EmpleadoID')
                ->where('m.AnioProgramacion', $anioActual);

            $mantenimientosAnio['realizados'] = (clone $mantenimientosBase)
                ->where('m.Estatus', 'Realizado')
                ->count();

            $mantenimientosAnio['pendientes'] = (clone $mantenimientosBase)
                ->where('m.Estatus', 'Pendiente')
                ->where('e.Estado', true)
                ->whereRaw("UPPER(COALESCE(e.tipo_persona, '')) = 'FISICA'")
                ->count();
        }

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

        return [
            'empleados' => [
                'total' => $totalEmpleados,
                'activos' => $empleadosActivos,
            ],
            'inventario' => [
                'equipos' => [
                    'total' => $totalEquipos,
                    'asignados' => $equiposAsignados,
                ],
                'insumos' => [
                    'total' => $totalInsumos,
                    'asignados' => $insumosAsignados,
                ],
                'lineas' => [
                    'total' => $totalLineas,
                    'asignadas' => $lineasAsignadasPersonaFisica,
                    'disponibles' => $lineasDisponibles,
                    'libres' => $lineasLibres,
                    'referenciados' => $lineasReferenciados,
                ],
            ],
            'organizacion' => [
                'obras' => $totalObras,
                'gerencias' => $totalGerencias,
                'unidades_negocio' => $totalUnidadesNegocio,
            ],
            'mantenimientos' => $mantenimientosAnio,
            'insumos_por_licencia' => $this->getInsumosPorLicencia($request),
            'equipos_por_categoria' => $this->getEquiposPorCategoria(),
            'estadisticas_gerencia' => $estadisticasPorGerencia,
        ];
    }

    private function buildComprasStats(): array
    {
        $anio = now()->year;
        $mes = now()->month;

        $porEstatus = [];
        foreach (TicketMantenimiento::ESTATUS as $estatus) {
            $porEstatus[$estatus] = TicketMantenimiento::where('Estatus', $estatus)->count();
        }

        $activos = ($porEstatus['Pendiente'] ?? 0)
            + ($porEstatus['En proceso'] ?? 0)
            + ($porEstatus['Pausado'] ?? 0);

        $porCategoria = TicketMantenimiento::query()
            ->select('Categoria', DB::raw('count(*) as total'))
            ->whereNotIn('Estatus', ['Atendido', 'Cancelado'])
            ->groupBy('Categoria')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $porPrioridad = TicketMantenimiento::query()
            ->select('Prioridad', DB::raw('count(*) as total'))
            ->whereNotIn('Estatus', ['Atendido', 'Cancelado'])
            ->groupBy('Prioridad')
            ->orderByDesc('total')
            ->get();

        return [
            'anio' => $anio,
            'mes' => $mes,
            'total' => TicketMantenimiento::count(),
            'activos' => $activos,
            'por_estatus' => $porEstatus,
            'creados_mes' => TicketMantenimiento::whereYear('created_at', $anio)->whereMonth('created_at', $mes)->count(),
            'atendidos_mes' => TicketMantenimiento::where('Estatus', 'Atendido')
                ->whereYear('FechaFinProgreso', $anio)
                ->whereMonth('FechaFinProgreso', $mes)
                ->count(),
            'por_categoria' => $porCategoria,
            'por_prioridad' => $porPrioridad,
        ];
    }

    private function statsInformaticaVacios(): array
    {
        return [
            'empleados' => ['total' => 0, 'activos' => 0],
            'inventario' => [
                'equipos' => ['total' => 0, 'asignados' => 0],
                'insumos' => ['total' => 0, 'asignados' => 0],
                'lineas' => ['total' => 0, 'asignadas' => 0, 'disponibles' => 0, 'libres' => 0, 'referenciados' => 0],
            ],
            'organizacion' => ['obras' => 0, 'gerencias' => 0, 'unidades_negocio' => 0],
            'mantenimientos' => ['pendientes' => 0, 'realizados' => 0, 'anio' => now()->year],
            'insumos_por_licencia' => collect(),
            'equipos_por_categoria' => collect(),
            'estadisticas_gerencia' => collect(),
        ];
    }

    private function statsComprasVacios(): array
    {
        $porEstatus = [];
        foreach (TicketMantenimiento::ESTATUS as $estatus) {
            $porEstatus[$estatus] = 0;
        }

        return [
            'anio' => now()->year,
            'mes' => now()->month,
            'total' => 0,
            'activos' => 0,
            'por_estatus' => $porEstatus,
            'creados_mes' => 0,
            'atendidos_mes' => 0,
            'por_categoria' => collect(),
            'por_prioridad' => collect(),
        ];
    }

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
