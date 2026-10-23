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

        $anio = (int) ($request->input('anio') ?: now()->year);
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) now()->year;
        }
        $mantenimientosAnio = ['pendientes' => 0, 'realizados' => 0, 'anio' => $anio];

        if (Schema::hasTable('mantenimientos') && Schema::hasColumn('mantenimientos', 'AnioProgramacion')) {
            $mantenimientosBase = DB::table('mantenimientos as m')
                ->leftJoin('inventarioequipo as ie', 'ie.InventarioID', '=', 'm.InventarioID')
                ->leftJoin('empleados as e', 'e.EmpleadoID', '=', 'ie.EmpleadoID')
                ->where('m.AnioProgramacion', $anio)
                ->where('e.Estado', true)
                ->whereIn('e.tipo_persona', $tiposOperativos)
                ->when($idsFiltro !== null, function ($query) use ($idsFiltro) {
                    $query->whereIn('e.EmpleadoID', $idsFiltro === [] ? [0] : $idsFiltro);
                })
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

        $vacantesQuery = Empleados::where('tipo_persona', 'EXTRAORDINARIO')->where('Estado', true);
        if ($idsFiltro !== null) {
            $vacantesQuery->whereIn('EmpleadoID', $idsFiltro === [] ? [0] : $idsFiltro);
        }
        $vacantes = $vacantesQuery->count();
        $equiposPropios = $this->contarSeguro(fn () => $this->limitarPorEmpleados(
            PresupuestoAsignacion::restringirPropios(DB::table('inventarioequipo')),
            $idsFiltro
        )->count());
        $presupuestados = [
            'equipos' => $this->contarSeguro(fn () => $this->limitarPorEmpleados(PresupuestoAsignacion::restringirPresupuesto(DB::table('inventarioequipo'), 'inventarioequipo', PresupuestoAsignacion::COLUMNA_EQUIPOS), $idsFiltro)->count()),
            'insumos' => $this->contarSeguro(fn () => $this->limitarPorEmpleados(PresupuestoAsignacion::restringirPresupuesto(DB::table('inventarioinsumo'), 'inventarioinsumo', PresupuestoAsignacion::COLUMNA_DEFAULT), $idsFiltro)->count()),
            'lineas' => $this->contarSeguro(fn () => $this->limitarPorEmpleados(PresupuestoAsignacion::restringirPresupuesto(DB::table('inventariolineas'), 'inventariolineas', PresupuestoAsignacion::COLUMNA_DEFAULT), $idsFiltro)->count()),
        ];
        $presupuestados['total'] = $presupuestados['equipos'] + $presupuestados['insumos'] + $presupuestados['lineas'];

        $ticketsBase = Tickets::query();
        $tareasBase = TicketTarea::query();
        $solicitudesBase = Solicitud::query();
        $auditoriasBase = Auditoria::query();
        if ($idsFiltro !== null) {
            $lista = $idsFiltro === [] ? [0] : $idsFiltro;
            $ticketsBase->whereIn('EmpleadoID', $lista);
            $tareasBase->whereIn('asignado_id', $lista);
            $solicitudesBase->whereIn('EmpleadoID', $lista);
            $auditoriasBase->whereIn('EmpleadoID', $lista);
        }

        $facturasBase = Facturas::query()->where('Anio', $anio);
        $idsPresupuesto = $this->idsPresupuestoDecision($request, $idsFiltro);
        if ($idsPresupuesto !== []) {
            $facturasBase->whereIn('PresupuestoInternoID', $idsPresupuesto);
        } else {
            $facturasBase->whereRaw('1 = 0');
        }

        $operacion = [
            'tickets_abiertos' => $this->contarSeguro(fn () => (clone $ticketsBase)->whereIn('Estatus', ['Pendiente', 'En progreso'])->count()),
            'tickets_pendientes' => $this->contarSeguro(fn () => (clone $ticketsBase)->where('Estatus', 'Pendiente')->count()),
            'tickets_progreso' => $this->contarSeguro(fn () => (clone $ticketsBase)->where('Estatus', 'En progreso')->count()),
            'tickets_estatus' => $this->contarSeguro(function () use ($ticketsBase) {
                return (clone $ticketsBase)
                    ->select('Estatus', DB::raw('COUNT(*) as total'))
                    ->whereNotIn('Estatus', ['Cerrado', 'Cancelado', 'Cancelada', 'Resuelto'])
                    ->groupBy('Estatus')
                    ->orderByDesc('total')
                    ->get();
            }, collect()),
            'tareas_pendientes' => $this->contarSeguro(fn () => (clone $tareasBase)->pendientes()->count()),
            'tareas_hoy' => $this->contarSeguro(fn () => (clone $tareasBase)->deHoy()->count()),
            'tareas_atrasadas' => $this->contarSeguro(fn () => (clone $tareasBase)->atrasadas()->count()),
            'tareas_proximas' => $this->contarSeguro(fn () => (clone $tareasBase)->proximas()->count()),
            'tareas_criticas' => $this->contarSeguro(fn () => (clone $tareasBase)->pendientes()->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count()),
            'solicitudes_activas' => $this->contarSeguro(fn () => (clone $solicitudesBase)->whereNotIn('Estatus', ['Cancelada', 'Cancelado', 'Rechazada', 'Rechazado', 'Aprobada', 'Aprobado'])->count()),
            'auditorias_anio' => $this->contarSeguro(fn () => (clone $auditoriasBase)->whereYear('created_at', $anio)->count()),
            'facturas_mes' => $this->contarSeguro(fn () => (clone $facturasBase)->count()),
            'facturas_importe' => $this->contarSeguro(fn () => (float) (clone $facturasBase)->sum('Costo'), 0.0),
        ];

        $conEquipo = (int) $this->contarSeguro(function () use ($idsFiltro) {
            return $this->limitarPorEmpleados(
                $this->queryAsignadosOperativos('inventarioequipo', PresupuestoAsignacion::COLUMNA_EQUIPOS),
                $idsFiltro
            )->distinct()->count('inventarioequipo.EmpleadoID');
        });
        $decision = $this->resumenPresupuestoOficial($idsPresupuesto, $anio);
        $decision['sin_equipo'] = max(0, $empleadosActivos - $conEquipo);
        $decision['anio'] = $anio;

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
            'decision' => $decision,
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
            'decision' => $this->resumenPresupuestoVacio((int) now()->year),
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
        $ids = $this->idsEmpleadosFiltrados($request);
        if ($ids !== null) {
            $query->whereIn('inventarioinsumo.EmpleadoID', $ids === [] ? [0] : $ids);
        }

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

    private function idsPresupuestoDecision(Request $request, ?array $idsEmpleados): array
    {
        $visibles = $this->catalogoDash()['presupuestos']->keys()->map(fn ($id) => (int) $id)->all();
        $pedido = (int) $request->input('presupuesto_id');
        if ($pedido > 0) {
            return in_array($pedido, $visibles, true) || $visibles === [] ? [$pedido] : [];
        }
        if ($idsEmpleados !== null) {
            if ($idsEmpleados === []) {
                return [];
            }
            return Empleados::query()
                ->whereIn('EmpleadoID', $idsEmpleados)
                ->whereNotNull('PresupuestoInternoID')
                ->where('PresupuestoInternoID', '>', 0)
                ->distinct()
                ->pluck('PresupuestoInternoID')
                ->map(fn ($id) => (int) $id)
                ->when($visibles !== [], fn ($ids) => $ids->intersect($visibles))
                ->values()
                ->all();
        }

        return $visibles;
    }

    private function resumenPresupuestoOficial(array $idsPresupuesto, int $anio): array
    {
        $resumen = $this->resumenPresupuestoVacio($anio);
        if ($idsPresupuesto === [] || !Schema::hasTable('cortes') || !Schema::hasTable('facturas')) {
            return $resumen;
        }

        $cortes = DB::table('cortes')
            ->whereNull('deleted_at')
            ->where('Anio', $anio)
            ->whereIn('PresupuestoInternoID', $idsPresupuesto)
            ->get(['PresupuestoInternoID', 'NombreInsumo', 'Mes', 'Costo', 'CostoTotal']);

        $facturas = DB::table('facturas')
            ->whereNull('deleted_at')
            ->where('Anio', $anio)
            ->whereIn('PresupuestoInternoID', $idsPresupuesto)
            ->get(['PresupuestoInternoID', 'GerenciaID', 'InsumoNombre', 'Mes', 'Costo', 'Importe']);

        $bolsas = [];
        foreach ($cortes as $row) {
            $mes = $this->mesNumero($row->Mes);
            if ($mes < 1) {
                continue;
            }
            $key = 'p-' . (int) $row->PresupuestoInternoID . '|' . mb_strtolower(trim((string) $row->NombreInsumo)) . '|' . $mes;
            $bolsas[$key]['mes'] = $mes;
            $bolsas[$key]['presupuesto'] = ($bolsas[$key]['presupuesto'] ?? 0) + (float) ($row->CostoTotal ?? $row->Costo ?? 0);
            $bolsas[$key]['facturado'] = $bolsas[$key]['facturado'] ?? 0;
        }
        foreach ($facturas as $row) {
            $mes = $this->mesNumero($row->Mes);
            if ($mes < 1) {
                continue;
            }
            $alcance = (int) $row->PresupuestoInternoID > 0
                ? 'p-' . (int) $row->PresupuestoInternoID
                : 'g-' . (int) $row->GerenciaID;
            $key = $alcance . '|' . mb_strtolower(trim((string) $row->InsumoNombre)) . '|' . $mes;
            $bolsas[$key]['mes'] = $mes;
            $bolsas[$key]['presupuesto'] = $bolsas[$key]['presupuesto'] ?? 0;
            $bolsas[$key]['facturado'] = ($bolsas[$key]['facturado'] ?? 0) + (float) ($row->Costo ?: $row->Importe ?: 0);
        }

        foreach ($bolsas as $bolsa) {
            $presupuesto = round((float) $bolsa['presupuesto'], 2);
            $facturado = round((float) $bolsa['facturado'], 2);
            $mes = (int) $bolsa['mes'];
            $resumen['presupuesto'] += $presupuesto;
            $resumen['facturado'] += $facturado;
            $resumen['meses'][$mes]['presupuesto'] += $presupuesto;
            $resumen['meses'][$mes]['facturado'] += $facturado;
            if ($presupuesto > 0 && $facturado <= 0) {
                $resumen['no_consumido'] += $presupuesto;
            } elseif ($facturado > $presupuesto) {
                $desviacion = round($facturado - $presupuesto, 2);
                $resumen['desviacion'] += $desviacion;
                $resumen['meses'][$mes]['desviacion'] += $desviacion;
            } elseif ($presupuesto > $facturado) {
                $ahorro = round($presupuesto - $facturado, 2);
                $resumen['ahorro'] += $ahorro;
                $resumen['meses'][$mes]['ahorro'] += $ahorro;
            }
        }

        foreach (['presupuesto', 'facturado', 'desviacion', 'ahorro', 'no_consumido'] as $campo) {
            $resumen[$campo] = round($resumen[$campo], 2);
        }

        return $resumen;
    }

    private function resumenPresupuestoVacio(int $anio): array
    {
        $cortos = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        $meses = [];
        foreach ($cortos as $numero => $nombre) {
            $meses[$numero] = [
                'nombre' => $nombre,
                'presupuesto' => 0.0,
                'facturado' => 0.0,
                'desviacion' => 0.0,
                'ahorro' => 0.0,
            ];
        }

        return [
            'anio' => $anio,
            'presupuesto' => 0.0,
            'facturado' => 0.0,
            'desviacion' => 0.0,
            'ahorro' => 0.0,
            'no_consumido' => 0.0,
            'sin_equipo' => 0,
            'meses' => $meses,
        ];
    }

    private function mesNumero($mes): int
    {
        if (is_numeric($mes)) {
            $numero = (int) $mes;
            return ($numero >= 1 && $numero <= 12) ? $numero : 0;
        }
        $nombres = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
            'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
        ];
        $clave = mb_strtolower(trim((string) $mes));

        return $nombres[$clave] ?? 0;
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
