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
use Illuminate\Support\Str;

class HomeController extends Controller
{
    private ?array $catalogoDashCache = null;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-dashboard|ver-compras|ver-control|ver-presupuesto|ver-presupuestos');
    }

    public static function destinoDe($user): string
    {
        if (!$user) {
            return '/login';
        }

        if ($user->can('ver-dashboard') || $user->can('ver-compras') ) {
            return '/home';
        }

        if ($user->can('ver-presupuesto')) {
            return '/presupuesto';
        }

        if ($user->can('ver-presupuestos')) {
            return '/cortes';
        }

        if ($user->can('ver-soporte')) {
            return '/tickets';
        }

        return '/home';
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $destino = self::destinoDe($user);
        if ($destino !== '/home') {
            return redirect($destino);
        }

        $tipoDashboard = $this->resolverTipoDashboard($user);
        $anio = $this->anioFiltro($request);

        try {
            $filtrosDash = $this->filtrosDash($request);
            $tab = $this->tabActiva($request, $user, $tipoDashboard);

            // Solo se calcula la pestaña visible; cambiar de pestaña recarga la página.
            $stats = $tab === 'informatica' ? $this->buildInformaticaStats($request) : null;
            $statsCompras = $tab === 'compras' ? $this->buildComprasStats($anio) : null;
            $catalogoDash = $tab === 'informatica' ? $this->catalogoDash() : [];
            $productividadMovimientos = $tab === 'control' ? app(\App\Services\MovimientoErpService::class)->productividad(['anio' => $anio]) : [];

            return view('home', compact('stats', 'statsCompras', 'tipoDashboard', 'filtrosDash', 'catalogoDash', 'productividadMovimientos'));
        } catch (\Exception $e) {
            report($e);
            $stats = $this->statsInformaticaVacios();
            $statsCompras = $this->statsComprasVacios();

            return view('home', [
                'stats' => $stats,
                'statsCompras' => $statsCompras,
                'tipoDashboard' => $tipoDashboard,
                'filtrosDash' => $this->filtrosDash($request),
                'catalogoDash' => $this->catalogoDash(),
                'productividadMovimientos' => [],
                'errorDashboard' => true,
            ]);
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

    // Misma regla que home.blade.php usa para marcar la pestaña activa.
    private function tabActiva(Request $request, $user, string $tipoDashboard): string
    {
        $tab = $request->input('tab');
        if (in_array($tab, ['control', 'productividad'], true) && $user->can('ver-control')) {
            return 'control';
        }
        if ($tipoDashboard === 'compras' || ($tipoDashboard === 'completo' && $tab === 'compras')) {
            return 'compras';
        }

        return 'informatica';
    }

    private function buildInformaticaStats(Request $request): array
    {
        $tiposOperativos = PresupuestoAsignacion::tiposPersonaInventario();
        $idsFiltro = $this->idsEmpleadosFiltrados($request);

        // Total, activos y vacantes (EXTRAORDINARIO activos) en una sola query.
        $enOperativos = 'tipo_persona IN (' . implode(',', array_fill(0, count($tiposOperativos), '?')) . ')';
        $conteoEmpleados = Empleados::query()
            ->whereIn('tipo_persona', array_merge($tiposOperativos, ['EXTRAORDINARIO']))
            ->when($idsFiltro !== null, fn ($q) => $q->whereIn('EmpleadoID', $idsFiltro === [] ? [0] : $idsFiltro))
            ->selectRaw(
                "COALESCE(SUM($enOperativos), 0) as total,
                 COALESCE(SUM($enOperativos AND Estado = 1), 0) as activos,
                 COALESCE(SUM(tipo_persona = 'EXTRAORDINARIO' AND Estado = 1), 0) as vacantes",
                array_merge($tiposOperativos, $tiposOperativos)
            )
            ->first();
        $totalEmpleados = (int) $conteoEmpleados->total;
        $empleadosActivos = (int) $conteoEmpleados->activos;
        $vacantes = (int) $conteoEmpleados->vacantes;

        $totalEquipos = Equipos::count();
        // Equipos asignados y empleados distintos con equipo salen de la misma consulta.
        $conteoEquipos = $this->limitarPorEmpleados(
            $this->queryAsignadosOperativos('inventarioequipo', PresupuestoAsignacion::COLUMNA_EQUIPOS),
            $idsFiltro
        )->selectRaw('COUNT(*) as asignados, COUNT(DISTINCT inventarioequipo.EmpleadoID) as personas')->first();
        $equiposAsignados = (int) $conteoEquipos->asignados;
        $conEquipo = (int) $conteoEquipos->personas;

        $totalInsumos = Insumos::count();
        $insumosAsignados = $this->limitarPorEmpleados(
            $this->queryAsignadosOperativos('inventarioinsumo', PresupuestoAsignacion::COLUMNA_DEFAULT),
            $idsFiltro
        )->count();

        $conteoLineas = LineasTelefonicas::where('Activo', true)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(Disponible = 1), 0) as libres')
            ->first();
        $totalLineas = (int) $conteoLineas->total;
        $lineasLibres = (int) $conteoLineas->libres;
        $lineasReferenciados = $this->lineasAsignadasOperativas('REFERENCIADO', $idsFiltro)->count();
        $lineasAsignadasPersonaFisica = $this->lineasAsignadasOperativas('FISICA', $idsFiltro)->count();
        $lineasAsignadas = $lineasAsignadasPersonaFisica + $lineasReferenciados;
        $lineasDisponibles = $lineasLibres;

        // Tres tablas, un solo viaje: subselects (los modelos aplican su soft delete).
        $conteoOrg = DB::query()
            ->selectSub(Obras::where('Estado', true)->selectRaw('COUNT(*)'), 'obras')
            ->selectSub(Gerencia::where('Estado', true)->selectRaw('COUNT(*)'), 'gerencias')
            ->selectSub(UnidadesDeNegocio::where('Estado', true)->selectRaw('COUNT(*)'), 'unidades')
            ->first();
        $totalObras = (int) $conteoOrg->obras;
        $totalGerencias = (int) $conteoOrg->gerencias;
        $totalUnidadesNegocio = (int) $conteoOrg->unidades;

        $anio = $this->anioFiltro($request);
        $mantenimientosAnio = ['pendientes' => 0, 'vencidos' => 0, 'realizados' => 0, 'anio' => $anio];

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

        $conteoMant = $mantenimientosBase
            ->selectRaw(
                "COALESCE(SUM(m.Estatus = 'Realizado'), 0) as realizados,
                 COALESCE(SUM(m.Estatus = 'Pendiente'), 0) as pendientes,
                 COALESCE(SUM(m.Estatus = 'Pendiente' AND COALESCE(m.FechaReprogramada, m.FechaMantenimiento) < ?), 0) as vencidos",
                [now()->toDateString()]
            )
            ->first();
        $mantenimientosAnio['realizados'] = (int) $conteoMant->realizados;
        $mantenimientosAnio['pendientes'] = (int) $conteoMant->pendientes;
        $mantenimientosAnio['vencidos'] = (int) $conteoMant->vencidos;

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

        $ticketsEstatus = $this->contarSeguro(function () use ($ticketsBase) {
            return (clone $ticketsBase)
                ->select('Estatus', DB::raw('COUNT(*) as total'))
                ->whereNotIn('Estatus', ['Cerrado', 'Cancelado', 'Cancelada', 'Resuelto'])
                ->groupBy('Estatus')
                ->orderByDesc('total')
                ->get();
        }, collect());
        // Abiertos/pendientes/en progreso salen del mismo GROUP BY; la llave se normaliza
        // porque MySQL agrupa sin distinguir mayúsculas ni espacios finales.
        $porEstatus = $ticketsEstatus->groupBy(fn ($r) => mb_strtolower(trim((string) $r->Estatus)))
            ->map(fn ($filas) => (int) $filas->sum('total'));
        $ticketsDe = fn (array $estatus) => collect($estatus)->sum(fn ($e) => $porEstatus->get(mb_strtolower($e), 0));

        $conteoFacturas = $this->contarSeguro(
            fn () => (clone $facturasBase)->selectRaw('COUNT(*) as total, COALESCE(SUM(Costo), 0) as importe')->first(),
            null
        );

        // Un viaje con subselects que reusan los scopes del modelo: la regla de cada tarjeta vive solo en TicketTarea.
        $conteoTareas = $this->contarSeguro(function () use ($tareasBase) {
            $contar = fn ($q) => $q->selectRaw('COUNT(*)');
            return DB::query()
                ->selectSub($contar((clone $tareasBase)->pendientes()), 'pendientes')
                ->selectSub($contar((clone $tareasBase)->deHoy()), 'hoy')
                ->selectSub($contar((clone $tareasBase)->atrasadas()), 'atrasadas')
                ->selectSub($contar((clone $tareasBase)->proximas()), 'proximas')
                ->selectSub($contar((clone $tareasBase)->pendientes()->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)), 'criticas')
                ->first();
        }, null);

        $operacion = [
            'tickets_abiertos' => $ticketsDe(['Pendiente', 'En progreso', 'Pausado']),
            'tickets_pendientes' => $ticketsDe(['Pendiente']),
            'tickets_progreso' => $ticketsDe(['En progreso']),
            'tickets_estatus' => $ticketsEstatus,
            'tareas_pendientes' => (int) ($conteoTareas->pendientes ?? 0),
            'tareas_hoy' => (int) ($conteoTareas->hoy ?? 0),
            'tareas_atrasadas' => (int) ($conteoTareas->atrasadas ?? 0),
            'tareas_proximas' => (int) ($conteoTareas->proximas ?? 0),
            'tareas_criticas' => (int) ($conteoTareas->criticas ?? 0),
            'solicitudes_activas' => $this->contarSeguro(fn () => (clone $solicitudesBase)->whereNotIn('Estatus', ['Cancelada', 'Cancelado', 'Rechazada', 'Rechazado', 'Aprobada', 'Aprobado'])->count()),
            'auditorias_anio' => $this->contarSeguro(fn () => (clone $auditoriasBase)->whereYear('created_at', $anio)->count()),
            'facturas_mes' => $conteoFacturas ? (int) $conteoFacturas->total : 0,
            'facturas_importe' => $conteoFacturas ? (float) $conteoFacturas->importe : 0.0,
        ];

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

    private function buildComprasStats(int $anio): array
    {
        // Año en curso: movimiento del mes; años pasados: el año completo.
        $mes = $anio === (int) now()->year ? (int) now()->month : null;
        $delAnio = fn () => TicketMantenimiento::query()->whereYear('created_at', $anio);

        $porEstatus = [];
        foreach (TicketMantenimiento::ESTATUS as $estatus) {
            $porEstatus[$estatus] = $delAnio()->where('Estatus', $estatus)->count();
        }

        $activos = ($porEstatus['Pendiente'] ?? 0)
            + ($porEstatus['En proceso'] ?? 0)
            + ($porEstatus['Pausado'] ?? 0);

        $porCategoria = $delAnio()
            ->select('Categoria', DB::raw('count(*) as total'))
            ->whereNotIn('Estatus', ['Atendido', 'Cancelado'])
            ->groupBy('Categoria')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $porPrioridad = $delAnio()
            ->select('Prioridad', DB::raw('count(*) as total'))
            ->whereNotIn('Estatus', ['Atendido', 'Cancelado'])
            ->groupBy('Prioridad')
            ->orderByDesc('total')
            ->get();

        return [
            'anio' => $anio,
            'mes' => $mes,
            'total' => array_sum($porEstatus),
            'activos' => $activos,
            'por_estatus' => $porEstatus,
            'creados_mes' => $delAnio()->when($mes, fn ($q) => $q->whereMonth('created_at', $mes))->count(),
            'atendidos_mes' => TicketMantenimiento::where('Estatus', 'Atendido')
                ->whereYear('FechaFinProgreso', $anio)
                ->when($mes, fn ($q) => $q->whereMonth('FechaFinProgreso', $mes))
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
            'mantenimientos' => ['pendientes' => 0, 'vencidos' => 0, 'realizados' => 0, 'anio' => now()->year],
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
            ->limit(8)
            ->get();
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
        if ($idsPresupuesto === []) {
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
            $key = 'p-' . (int) $row->PresupuestoInternoID . '|' . $this->claveInsumo($row->NombreInsumo) . '|' . $mes;
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
            $key = $alcance . '|' . $this->claveInsumo($row->InsumoNombre) . '|' . $mes;
            $bolsas[$key]['mes'] = $mes;
            $bolsas[$key]['presupuesto'] = $bolsas[$key]['presupuesto'] ?? 0;
            $bolsas[$key]['facturado'] = ($bolsas[$key]['facturado'] ?? 0) + (float) ($row->Costo ?: $row->Importe ?: 0);
        }

        // Meses que aún no llegan no cuentan como "sin consumir".
        $hoy = now();
        $mesCorte = $anio < $hoy->year ? 12 : ($anio > $hoy->year ? 0 : (int) $hoy->month);

        foreach ($bolsas as $bolsa) {
            $presupuesto = round((float) $bolsa['presupuesto'], 2);
            $facturado = round((float) $bolsa['facturado'], 2);
            $mes = (int) $bolsa['mes'];
            $resumen['presupuesto'] += $presupuesto;
            $resumen['facturado'] += $facturado;
            $resumen['meses'][$mes]['presupuesto'] += $presupuesto;
            $resumen['meses'][$mes]['facturado'] += $facturado;
            if ($presupuesto > 0 && $facturado <= 0) {
                if ($mes <= $mesCorte) {
                    $resumen['no_consumido'] += $presupuesto;
                }
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

    // Igual que la collation de MySQL: sin acentos, mayúsculas ni espacios dobles.
    private function claveInsumo($nombre): string
    {
        return Str::lower(preg_replace('/\s+/', ' ', trim(Str::ascii((string) $nombre))));
    }

    private function anioFiltro(Request $request): int
    {
        $anio = (int) $request->input('anio');

        return ($anio >= 2000 && $anio <= 2100) ? $anio : (int) now()->year;
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
            'anio' => $this->anioFiltro($request),
        ];
    }

    private function catalogoDash(): array
    {
        if ($this->catalogoDashCache !== null) {
            return $this->catalogoDashCache;
        }

        $asignados = DB::table('presupuesto_interno_usuarios')
            ->where('users_id', auth()->id())
            ->pluck('PresupuestoInternoID');

        return $this->catalogoDashCache = [
            'gerencias' => Gerencia::query()->where('Estado', true)->orderBy('NombreGerencia')->pluck('NombreGerencia', 'GerenciaID'),
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
            report($e);
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
