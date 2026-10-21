<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleados;
use App\Models\Equipos;
use App\Models\Insumos;
use App\Models\LineasTelefonicas;
use App\Models\Obras;
use App\Models\CentroCosto;
use App\Models\Gerencia;
use App\Models\UnidadesDeNegocio;
use App\Models\TicketMantenimiento;
use App\Models\Tickets;
use App\Models\TicketTarea;
use App\Models\Solicitud;
use App\Models\Auditoria;
use App\Models\Facturas;
use App\Models\PresupuestoConfiguracion;
use App\Models\PresupuestoInterno;
use App\Helpers\PresupuestoAsignacion;
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
                $statsCompras = $this->buildComprasStats((int) ($request->input('anio') ?: now()->year));
            }

            if ($request->ajax()) {
                return view('partials.insumos-licencia', compact('stats'))->render();
            }

            $filtrosDash = $this->filtrosDash($request);
            $catalogoDash = $this->catalogoDash();

            return view('home', compact('stats', 'statsCompras', 'tipoDashboard', 'filtrosDash', 'catalogoDash'));
        } catch (\Exception $e) {
            $stats = $this->statsInformaticaVacios();
            $statsCompras = $this->statsComprasVacios();

            return view('home', [
                'stats' => $stats,
                'statsCompras' => $statsCompras,
                'tipoDashboard' => $tipoDashboard,
                'filtrosDash' => $this->filtrosDash($request),
                'catalogoDash' => $this->catalogoDash(),
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

    /**
     * COMPRAS y DIRECTOR_VIEW tienen los mismos permisos, así que el tablero se decide por rol:
     * DIRECTOR_VIEW, PRESUPUESTO (y TORDONEZ) ven las dos pestañas, COMPRAS solo la suya
     * y el resto Informática.
     */
    private function resolverTipoDashboard($user): string
    {
        if ($user->username === 'TORDONEZ' || $user->hasAnyRole(['DIRECTOR_VIEW', 'PRESUPUESTO'])) {
            return 'completo';
        }

        if ($user->hasRole('COMPRAS')) {
            return 'compras';
        }

        return 'informatica';
    }

    private function buildInformaticaStats(Request $request): array
    {
        $tiposOperativos = PresupuestoAsignacion::tiposPersonaInventario();
        $idsFiltro = $this->idsEmpleadosFiltrados($request);

        $baseEmpleados = Empleados::query()->whereIn('tipo_persona', $tiposOperativos);
        if ($idsFiltro !== null) {
            $baseEmpleados->whereIn('EmpleadoID', $idsFiltro === [] ? [0] : $idsFiltro);
        }
        $totalEmpleados = (clone $baseEmpleados)->count();
        $empleadosActivos = (clone $baseEmpleados)->where('Estado', true)->count();

        $totalEquipos = Equipos::count();
        $equiposAsignados = $this->limitarPorEmpleados(
            $this->queryAsignadosOperativos('inventarioequipo', PresupuestoAsignacion::COLUMNA_EQUIPOS),
            $idsFiltro
        )->count();

        $totalInsumos = Insumos::count();
        $insumosAsignados = $this->limitarPorEmpleados(
            $this->queryAsignadosOperativos('inventarioinsumo', PresupuestoAsignacion::COLUMNA_DEFAULT),
            $idsFiltro
        )->count();

        $totalLineas = LineasTelefonicas::where('Activo', true)->count();
        $lineasLibres = LineasTelefonicas::where('Activo', true)->where('Disponible', 1)->count();
        $lineasReferenciados = $this->lineasAsignadasOperativas('REFERENCIADO', $idsFiltro)->count();
        $lineasAsignadasPersonaFisica = $this->lineasAsignadasOperativas('FISICA', $idsFiltro)->count();
        $lineasAsignadas = $lineasAsignadasPersonaFisica + $lineasReferenciados;
        $lineasDisponibles = $lineasLibres;

        $totalObras = Obras::where('Estado', true)->count();
        $totalGerencias = Gerencia::where('Estado', true)->count();
        $totalUnidadesNegocio = UnidadesDeNegocio::where('Estado', true)->count();

        $anioActual = now()->year;
        $mantenimientosAnio = ['pendientes' => 0, 'realizados' => 0, 'anio' => $anioActual];

        if (Schema::hasTable('mantenimientos') && Schema::hasColumn('mantenimientos', 'AnioProgramacion')) {
            $mantenimientosBase = DB::table('mantenimientos as m')
                ->leftJoin('inventarioequipo as ie', 'ie.InventarioID', '=', 'm.InventarioID')
                ->leftJoin('empleados as e', 'e.EmpleadoID', '=', 'ie.EmpleadoID')
                ->where('m.AnioProgramacion', $anioActual)
                ->where('e.Estado', true)
                ->whereIn('e.tipo_persona', $tiposOperativos)
                ->where(function ($q) {
                    $q->whereIn('ie.' . PresupuestoAsignacion::COLUMNA_EQUIPOS, array_map('strval', PresupuestoAsignacion::valoresOperativos()))
                        ->orWhereNull('ie.' . PresupuestoAsignacion::COLUMNA_EQUIPOS);
                });

            $mantenimientosAnio['realizados'] = (clone $mantenimientosBase)
                ->where('m.Estatus', 'Realizado')
                ->count();

            $mantenimientosAnio['pendientes'] = (clone $mantenimientosBase)
                ->where('m.Estatus', 'Pendiente')
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
            ->whereIn('empleados.tipo_persona', $tiposOperativos)
            ->when($idsFiltro !== null, function ($query) use ($idsFiltro) {
                $query->whereIn('empleados.EmpleadoID', $idsFiltro === [] ? [0] : $idsFiltro);
            })
            ->groupBy('gerencia.GerenciaID', 'gerencia.NombreGerencia')
            ->having('total_empleados', '>', 0)
            ->orderBy('total_empleados', 'desc')
            ->limit(7)
            ->get();

        $estadisticasPorPresupuesto = DB::table('presupuestos_internos')
            ->leftJoin('empleados', 'presupuestos_internos.PresupuestoInternoID', '=', 'empleados.PresupuestoInternoID')
            ->select('presupuestos_internos.Nombre as NombrePresupuesto')
            ->selectRaw('COUNT(DISTINCT empleados.EmpleadoID) as total_empleados')
            ->whereNull('presupuestos_internos.deleted_at')
            ->where('empleados.Estado', true)
            ->whereIn('empleados.tipo_persona', $tiposOperativos)
            ->when($idsFiltro !== null, function ($query) use ($idsFiltro) {
                $query->whereIn('empleados.EmpleadoID', $idsFiltro === [] ? [0] : $idsFiltro);
            })
            ->groupBy('presupuestos_internos.PresupuestoInternoID', 'presupuestos_internos.Nombre')
            ->having('total_empleados', '>', 0)
            ->orderBy('total_empleados', 'desc')
            ->limit(8)
            ->get();

        $vacantes = Empleados::where('tipo_persona', 'EXTRAORDINARIO')->where('Estado', true)->count();
        $equiposPropios = $this->contarSeguro(fn () => PresupuestoAsignacion::restringirPropios(DB::table('inventarioequipo'))->count());
        $presupuestados = [
            'equipos' => $this->contarSeguro(fn () => PresupuestoAsignacion::restringirPresupuesto(DB::table('inventarioequipo'), 'inventarioequipo', PresupuestoAsignacion::COLUMNA_EQUIPOS)->count()),
            'insumos' => $this->contarSeguro(fn () => PresupuestoAsignacion::restringirPresupuesto(DB::table('inventarioinsumo'), 'inventarioinsumo', PresupuestoAsignacion::COLUMNA_DEFAULT)->count()),
            'lineas' => $this->contarSeguro(fn () => PresupuestoAsignacion::restringirPresupuesto(DB::table('inventariolineas'), 'inventariolineas', PresupuestoAsignacion::COLUMNA_DEFAULT)->count()),
        ];
        $presupuestados['total'] = $presupuestados['equipos'] + $presupuestados['insumos'] + $presupuestados['lineas'];

        $anio = now()->year;
        $mes = now()->month;
        $operacion = [
            'tickets_abiertos' => $this->contarSeguro(fn () => Tickets::whereIn('Estatus', ['Pendiente', 'En progreso'])->count()),
            'tickets_pendientes' => $this->contarSeguro(fn () => Tickets::where('Estatus', 'Pendiente')->count()),
            'tickets_progreso' => $this->contarSeguro(fn () => Tickets::where('Estatus', 'En progreso')->count()),
            'tareas_pendientes' => $this->contarSeguro(fn () => TicketTarea::pendientes()->count()),
            'tareas_hoy' => $this->contarSeguro(fn () => TicketTarea::deHoy()->count()),
            'tareas_atrasadas' => $this->contarSeguro(fn () => TicketTarea::atrasadas()->count()),
            'tareas_proximas' => $this->contarSeguro(fn () => TicketTarea::proximas()->count()),
            'tareas_criticas' => $this->contarSeguro(fn () => TicketTarea::pendientes()->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count()),
            'solicitudes_activas' => $this->contarSeguro(fn () => Solicitud::whereNotIn('Estatus', ['Cancelada', 'Cancelado', 'Rechazada', 'Rechazado', 'Aprobada', 'Aprobado'])->count()),
            'auditorias_anio' => $this->contarSeguro(fn () => Auditoria::whereYear('created_at', $anio)->count()),
            'facturas_mes' => $this->contarSeguro(fn () => Facturas::where('Anio', $anio)->where('Mes', $mes)->count()),
            'facturas_importe' => $this->contarSeguro(fn () => (float) Facturas::where('Anio', $anio)->where('Mes', $mes)->sum('Costo'), 0.0),
        ];

        return [
            'empleados' => [
                'total' => $totalEmpleados,
                'activos' => $empleadosActivos,
                'vacantes' => $vacantes,
            ],
            'inventario' => [
                'equipos' => [
                    'total' => $totalEquipos,
                    'asignados' => $equiposAsignados,
                    'propios' => $equiposPropios,
                ],
                'insumos' => [
                    'total' => $totalInsumos,
                    'asignados' => $insumosAsignados,
                ],
                'lineas' => [
                    'total' => $totalLineas,
                    'asignadas' => $lineasAsignadas,
                    'disponibles' => $lineasDisponibles,
                    'libres' => $lineasLibres,
                    'referenciados' => $lineasReferenciados,
                    'fisicas' => $lineasAsignadasPersonaFisica,
                ],
            ],
            'presupuestados' => $presupuestados,
            'operacion' => $operacion,
            'organizacion' => [
                'obras' => $totalObras,
                'gerencias' => $totalGerencias,
                'unidades_negocio' => $totalUnidadesNegocio,
            ],
            'mantenimientos' => $mantenimientosAnio,
            'insumos_por_licencia' => $this->getInsumosPorLicencia($request),
            'equipos_por_categoria' => $this->getEquiposPorCategoria($idsFiltro),
            'estadisticas_gerencia' => $estadisticasPorGerencia,
            'estadisticas_presupuesto' => $estadisticasPorPresupuesto,
        ];
    }

    private function buildComprasStats(int $anio = 0): array
    {
        $anio = $anio > 0 ? $anio : (int) now()->year;
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
            'empleados' => ['total' => 0, 'activos' => 0, 'vacantes' => 0],
            'inventario' => [
                'equipos' => ['total' => 0, 'asignados' => 0, 'propios' => 0],
                'insumos' => ['total' => 0, 'asignados' => 0],
                'lineas' => ['total' => 0, 'asignadas' => 0, 'disponibles' => 0, 'libres' => 0, 'referenciados' => 0, 'fisicas' => 0],
            ],
            'presupuestados' => ['equipos' => 0, 'insumos' => 0, 'lineas' => 0, 'total' => 0],
            'operacion' => [
                'tickets_abiertos' => 0,
                'tickets_pendientes' => 0,
                'tickets_progreso' => 0,
                'tareas_pendientes' => 0,
                'tareas_hoy' => 0,
                'tareas_atrasadas' => 0,
                'tareas_proximas' => 0,
                'tareas_criticas' => 0,
                'solicitudes_activas' => 0,
                'auditorias_anio' => 0,
                'facturas_mes' => 0,
                'facturas_importe' => 0,
            ],
            'organizacion' => ['obras' => 0, 'gerencias' => 0, 'unidades_negocio' => 0],
            'mantenimientos' => ['pendientes' => 0, 'realizados' => 0, 'anio' => now()->year],
            'insumos_por_licencia' => collect(),
            'equipos_por_categoria' => collect(),
            'estadisticas_gerencia' => collect(),
            'estadisticas_presupuesto' => collect(),
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
        $query = DB::table('inventarioinsumo')
            ->select('NombreInsumo')
            ->selectRaw('COUNT(InventarioID) as total_inventario');

        PresupuestoAsignacion::restringirOperativo($query, 'inventarioinsumo', PresupuestoAsignacion::COLUMNA_DEFAULT);
        PresupuestoConfiguracion::aplicarWhereIn($query, 'CateogoriaInsumo', 'licencias');

        return $query
            ->groupBy('NombreInsumo')
            ->orderBy('total_inventario', 'desc')
            ->paginate(7);
    }

    private function getEquiposPorCategoria(?array $ids = null)
    {
        $query = DB::table('inventarioequipo')
            ->select('CategoriaEquipo')
            ->selectRaw('COUNT(InventarioID) as total_inventario');
        if ($ids !== null) {
            $query->whereIn('EmpleadoID', $ids === [] ? [0] : $ids);
        }

        PresupuestoAsignacion::restringirOperativo($query, 'inventarioequipo', PresupuestoAsignacion::COLUMNA_EQUIPOS);
        PresupuestoConfiguracion::aplicarWhereIn($query, 'CategoriaEquipo', 'hardware');

        return $query
            ->groupBy('CategoriaEquipo')
            ->orderBy('total_inventario', 'desc')
            ->get();
    }

    private function idsEmpleadosFiltrados(Request $request): ?array
    {
        $gerencia = (int) $request->input('gerencia_id');
        $centro = (int) $request->input('centro_id');
        $presupuesto = (int) $request->input('presupuesto_id');
        if (!$gerencia && !$centro && !$presupuesto) {
            return null;
        }

        $query = Empleados::query();
        if ($presupuesto) {
            $query->where('PresupuestoInternoID', $presupuesto);
        }
        if ($centro) {
            $query->where('CentroCostoID', $centro);
        }
        if ($gerencia) {
            $query->whereHas('puestos.departamentos', function ($departamentos) use ($gerencia) {
                $departamentos->where('GerenciaID', $gerencia);
            });
        }

        return $query->pluck('EmpleadoID')->all();
    }

    private function filtrosDash(Request $request): array
    {
        return [
            'gerencia_id' => (int) $request->input('gerencia_id'),
            'centro_id' => (int) $request->input('centro_id'),
            'presupuesto_id' => (int) $request->input('presupuesto_id'),
            'anio' => (int) ($request->input('anio') ?: now()->year),
        ];
    }

    private function catalogoDash(): array
    {
        $asignados = DB::table('presupuesto_interno_usuarios')
            ->where('users_id', auth()->id())
            ->pluck('PresupuestoInternoID');

        return [
            'gerencias' => Gerencia::query()->orderBy('NombreGerencia')->pluck('NombreGerencia', 'GerenciaID'),
            'centros' => CentroCosto::query()->orderBy('NombreCentro')->pluck('NombreCentro', 'CentroCostoID'),
            'presupuestos' => PresupuestoInterno::query()
                ->when($asignados->isNotEmpty(), fn ($q) => $q->whereIn('PresupuestoInternoID', $asignados))
                ->orderBy('Nombre')
                ->pluck('Nombre', 'PresupuestoInternoID'),
        ];
    }

    private function limitarPorEmpleados($query, ?array $ids)
    {
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('EmpleadoID', $ids === [] ? [0] : $ids);
    }

    private function queryAsignadosOperativos(string $tabla, string $columna, string $empleadoCol = 'EmpleadoID')
    {
        return PresupuestoAsignacion::restringirOperativo(DB::table($tabla), $tabla, $columna, $empleadoCol);
    }

    private function contarSeguro(callable $consulta, $fallback = 0)
    {
        try {
            return $consulta();
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function lineasAsignadasOperativas(?string $tipoPersona = null, ?array $ids = null)
    {
        $tipos = $tipoPersona
            ? [$tipoPersona]
            : PresupuestoAsignacion::tiposPersonaInventario();

        return LineasTelefonicas::where('lineastelefonicas.Activo', true)
            ->where('lineastelefonicas.Disponible', 0)
            ->whereExists(function ($q) use ($tipos, $ids) {
                $q->select(DB::raw(1))
                    ->from('inventariolineas as il')
                    ->join('empleados as e', 'e.EmpleadoID', '=', 'il.EmpleadoID')
                    ->whereColumn('il.LineaID', 'lineastelefonicas.LineaID')
                    ->where('e.Estado', true)
                    ->whereIn('e.tipo_persona', $tipos)
                    ->when($ids !== null, function ($query) use ($ids) {
                        $query->whereIn('e.EmpleadoID', $ids === [] ? [0] : $ids);
                    })
                    ->where(function ($w) {
                        $w->whereIn('il.Presupuestado', array_map('strval', PresupuestoAsignacion::valoresOperativos()))
                            ->orWhereNull('il.Presupuestado');
                    });
            });
    }
}
