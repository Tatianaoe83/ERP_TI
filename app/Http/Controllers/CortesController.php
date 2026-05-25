<?php

namespace App\Http\Controllers;
use App\Http\Requests\UpdateCortesRequest;
use App\Repositories\CortesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Cortes;
use App\Models\Gerencia;
use App\Models\Insumos;
use App\Models\InventarioInsumo;
use App\Models\InventarioLineas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Response;
use Yajra\DataTables\Facades\DataTables;


class CortesController extends AppBaseController
{
    private $cortesRepository;

    private const MES_MAP = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo'  => 5, 'junio'   => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    private const NUM_TO_NAME = [
        1  => 'Enero',  2  => 'Febrero',   3  => 'Marzo',
        4  => 'Abril',  5  => 'Mayo',      6  => 'Junio',
        7  => 'Julio',  8  => 'Agosto',    9  => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private const LIMITE_MESES_SOBREESCRITURA = 6;

    public function __construct(CortesRepository $cortesRepo)
    {
        $this->cortesRepository = $cortesRepo;
        $this->middleware('permission:ver-presupuestos', ['only' => ['index', 'indexVista', 'obtenerCorteGuardado', 'obtenerInsumos', 'show']]);
        $this->middleware('permission:generar-cortes', ['only' => ['store', 'storeAll', 'saveXML', 'readXML', 'create', 'edit', 'update', 'destroy']]);
    }

    public function index(Request $request)
    {
        $gerencia   = Gerencia::where('estado', 1)->orderBy('NombreGerencia')->get();
        $anioActual = (int) Carbon::now()->year;
        $years      = array_reverse(range($anioActual - 5, $anioActual + 1));

        $anioConsulta = (int) ($request->input('anio') ?? $anioActual);
        $anioConsulta = in_array($anioConsulta, $years, true) ? $anioConsulta : $anioActual;

        $gerenciasConCorteIds = Cortes::where('Anio', $anioConsulta)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('GerenciaID')
            ->toArray();

        $gerenciasConCorte = $gerencia->whereIn('GerenciaID', $gerenciasConCorteIds)->values();
        $gerenciasSinCorte = $gerencia->whereNotIn('GerenciaID', $gerenciasConCorteIds)->values();

        $meses = array_values(self::NUM_TO_NAME);

        return view('cortes.index', compact(
            'meses', 'gerencia', 'years', 'anioActual',
            'anioConsulta', 'gerenciasConCorte', 'gerenciasSinCorte'
        ));
    }

    public function indexVista(Request $request)
    {
        $gerenciaID = $request->input('gerenci_id');
        $mes        = $request->input('mes');

        if ($request->ajax()) {
            if ($gerenciaID && $mes) {
                $query = DB::table('cortes')
                    ->where('GerenciaID', $gerenciaID)
                    ->where('Mes', $mes);

                return DataTables::of($query)
                    ->addColumn('action', fn($row) => view('cortes.datatables_actions', ['id' => $row->CortesID])->render())
                    ->rawColumns(['action'])
                    ->make(true);
            }
            return DataTables::of(collect([]))->make(true);
        }

        return view('cortes.index');
    }

    public function obtenerInsumos(Request $request)
    {
        $gerenciaID = $request->input('gerenciaID');

        if (empty($gerenciaID)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'draw'            => (int) $request->input('draw', 0),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                ]);
            }

            return back()->with('error', 'Por favor, selecciona una gerencia');
        }

        try {
            $lineasMensuales = $this->obtenerLineasMensuales($gerenciaID);
            $fianzas = $this->obtenerFianzas($gerenciaID);
            $inversiones = $this->obtenerInversiones($gerenciaID);
            $licencias = $this->obtenerLicencias($gerenciaID);
            $otrosAnuales = $this->obtenerOtrosAnuales($gerenciaID);
            $mensuales = $this->obtenerMensuales($gerenciaID);

            $resultado = array_merge(
                $lineasMensuales,
                $fianzas,
                $inversiones,
                $licencias,
                $otrosAnuales,
                $mensuales
            );

            // Agrupar por NombreInsumo
            $agrupado = collect($resultado)
                ->sortBy('NombreInsumo')
                ->groupBy('NombreInsumo')
                ->map(function ($items, $nombre) {
                    $montos = $items->map(function ($item) {
                        // Convertir nombre de mes a número
                        $mesNum = array_search($item['Mes'], self::NUM_TO_NAME);
                        return [
                            'Mes' => $mesNum,
                            'Costo' => $item['Costo'],
                        ];
                    })->values();
                    return [
                        'NombreInsumo' => $nombre,
                        'MontosPorMes' => $montos,
                    ];
                })
                ->values()
                ->all();

            return response()->json(['data' => $agrupado]);
        } catch (\Throwable $th) {
            report($th);
            return $request->expectsJson()
                ? response()->json(['message' => 'No se pudo procesar los insumos presupuestados', 'data' => []], 500)
                : back()->with('error', 'No se pudo procesar los insumos presupuestados');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'rows'                => ['required', 'array', 'min:1'],
            'rows.*.NombreInsumo' => ['required', 'string', 'max:255'],
            'rows.*.Mes'          => ['required', 'integer', 'between:1,12'],
            'rows.*.Costo'        => ['required', 'numeric', 'min:0'],
            'rows.*.Margen'       => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.CostoTotal'   => ['required', 'numeric', 'min:0'],
            'rows.*.GerenciaID'   => ['required', 'integer'],
        ], [
            'rows.required' => 'No hay filas a guardar.',
        ]);

        $ids = collect($data['rows'])->pluck('GerenciaID')->unique()->values();
        if ($ids->count() !== 1) {
            return response()->json(['message' => 'Todas las filas deben pertenecer a la misma gerencia.'], 422);
        }

        $gerenciaID = (int) $ids->first();
        $año        = (int) ($request->input('anio') ?? Carbon::now()->year);

        $yaExiste = Cortes::where('GerenciaID', $gerenciaID)
            ->where('Anio', $año)
            ->whereNull('deleted_at')
            ->exists();

        if ($this->plazoVencido($año)) {
            $accion = $yaExiste ? 'modificar' : 'crear';
            return response()->json([
                'message' => "El plazo para {$accion} el presupuesto de {$año} venció el 30 de junio de {$año}. No es posible realizar la acción.",
            ], 403);
        }

        $toInsert = collect($data['rows'])->map(function (array $r) use ($año) {
            $mesNombre = self::NUM_TO_NAME[(int) $r['Mes']] ?? null;
            $costo     = round((float) $r['Costo'], 2);
            $margen    = max(0, min(100, (float) $r['Margen']));
            return [
                'NombreInsumo' => (string) $r['NombreInsumo'],
                'Mes'          => $mesNombre,
                'Costo'        => $costo,
                'Margen'       => $margen,
                'CostoTotal'   => round($costo * (1 + $margen / 100), 2),
                'Anio'         => $año,
                'GerenciaID'   => (int) $r['GerenciaID'],
            ];
        });

        if ($toInsert->isEmpty()) {
            return response()->json(['message' => 'Nada que guardar'], 422);
        }

        DB::transaction(function () use ($gerenciaID, $año, $toInsert) {
            Cortes::where('GerenciaID', $gerenciaID)->where('Anio', $año)->delete();
            Cortes::insertOrIgnore($toInsert->all());
        });

        return response()->json(['message' => 'Presupuesto anual registrado'], 201);
    }

    public function storeAll(Request $request)
    {
        $año       = (int) ($request->input('anio') ?? Carbon::now()->year);
        $gerencias = Gerencia::where('estado', 1)->orderBy('NombreGerencia')->get();

        $resultados = [];

        foreach ($gerencias as $g) {
            try {
                $yaExiste = Cortes::where('GerenciaID', $g->GerenciaID)
                    ->where('Anio', $año)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($this->plazoVencido($año)) {
                    $accion = $yaExiste ? 'sobreescribir' : 'crear';
                    $resultados[] = [
                        'gerencia' => $g->NombreGerencia,
                        'status'   => 'error',
                        'msg'      => "Plazo vencido: no se puede {$accion} el presupuesto de {$año}.",
                    ];
                    continue;
                }

                $toInsert = $this->procesarInsumosParaCorte((int) $g->GerenciaID, $año);

                if (empty($toInsert)) {
                    $resultados[] = ['gerencia' => $g->NombreGerencia, 'status' => 'sin_datos'];
                    continue;
                }

                DB::transaction(function () use ($g, $año, $toInsert) {
                    Cortes::where('GerenciaID', $g->GerenciaID)->where('Anio', $año)->delete();
                    Cortes::insertOrIgnore($toInsert);
                });

                $resultados[] = [
                    'gerencia' => $g->NombreGerencia,
                    'status'   => 'ok',
                    'count'    => count($toInsert),
                ];
            } catch (\Throwable $e) {
                report($e);
                $resultados[] = [
                    'gerencia' => $g->NombreGerencia,
                    'status'   => 'error',
                    'msg'      => $e->getMessage(),
                ];
            }
        }

        $exitosos = count(array_filter($resultados, fn($r) => $r['status'] === 'ok'));

        return response()->json([
            'message'    => "Proceso completado: {$exitosos} gerencias guardadas.",
            'resultados' => $resultados,
        ], 200);
    }

    private function plazoVencido(int $año): bool
    {
        $limite = Carbon::create($año, self::LIMITE_MESES_SOBREESCRITURA, 30, 23, 59, 59);
        return Carbon::now()->isAfter($limite);
    }


            // Método para obtener la consulta base del inventario, filtrada por gerencia
            private function inventarioQuery(int $gerenciaID)
            {
                return InventarioInsumo::query()
                    ->from('inventarioinsumo as i')
                    ->select(
                        'i.*',
                        'g.GerenciaID'
                    )
                    ->join('empleados as e', 'i.EmpleadoID', '=', 'e.EmpleadoID')
                    ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
                    ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
                    ->join('gerencia as g', 'd.GerenciaID', '=', 'g.GerenciaID')
                    ->whereIn('e.tipo_persona', ['FISICA', 'EXTRAORDINARIO']    )
                    ->where('g.GerenciaID', $gerenciaID);
            }

            // Método para obtener la consulta base de las líneas de inventario, filtrada por gerencia
            private function lineasQuery(int $gerenciaID)
            {
                return InventarioLineas::query()
                    ->from('inventariolineas as il')
                    ->select(
                        'il.*',
                        'g.GerenciaID'
                    )
                    ->join('empleados as e', 'il.EmpleadoID', '=', 'e.EmpleadoID')
                    ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
                    ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
                    ->join('gerencia as g', 'd.GerenciaID', '=', 'g.GerenciaID')
                    ->whereIn('e.tipo_persona', ['FISICA', 'EXTRAORDINARIO']    )
                    ->where('g.GerenciaID', $gerenciaID);
            
                    
            }
            

            // Métodos específicos para obtener cada tipo de insumo, utilizando las consultas base

            private function obtenerLineasMensuales(int $gerenciaID): array
            {
                $reporte = [];

                $rows = $this->lineasQuery($gerenciaID)
                    ->select(
                        'il.Compania',
                        'il.TipoLinea',
                        DB::raw('SUM(il.CostoRentaMensual) as Total')
                    )
                    ->groupBy('il.Compania', 'il.TipoLinea')
                    ->get();

                foreach ($rows as $row) {
                    $tipoNorm = ucfirst(strtolower($row->TipoLinea));
                    $nombreInsumo = $row->Compania . ' ' . $tipoNorm;
                    foreach (self::NUM_TO_NAME as $mes) {
                        $costo = round($row->Total, 0);
                        if ($costo > 0) {
                            $reporte[] = [
                                'NombreInsumo' => $nombreInsumo,
                                'Mes'          => $mes,
                                'Costo'        => $costo,
                                'Orden'        => 5,
                                'GerenciaID'   => $gerenciaID,
                            ];
                        }
                    }
                }

                return $reporte;
            }

            // Método para obtener fianzas, considerando solo las líneas de voz y datos, y agrupando por compañía y tipo de línea

            private function obtenerFianzas(int $gerenciaID): array
            {
                $reporte = [];

                $rows = $this->lineasQuery($gerenciaID)
                    ->select(
                        'il.Compania',
                        'il.TipoLinea',
                        'il.FechaFianza',
                        'il.CostoFianza'
                    )
                    ->whereIn('il.TipoLinea', ['Voz', 'Datos', 'GPS', 'voz', 'datos', 'gps', 'VOZ', 'DATOS'])
                    ->get();

                // Normalize TipoLinea for consistent PHP grouping
                $rows->transform(function ($item) {
                    $item->TipoLineaNorm = ucfirst(strtolower($item->TipoLinea));
                    $item->NombreInsumoNorm = $item->Compania . ' FIANZA - ' . $item->TipoLineaNorm;
                    return $item;
                });

                $grouped = $rows->groupBy('NombreInsumoNorm');

                foreach ($grouped as $nombreInsumo => $items) {
                    $valoresMeses = array_fill_keys(array_values(self::NUM_TO_NAME), 0.0);

                    foreach ($items as $item) {
                        if (!$item->FechaFianza) {
                            continue;
                        }
                        $mesNum = (int) Carbon::parse($item->FechaFianza)->month;
                        $mesNombre = self::NUM_TO_NAME[$mesNum] ?? null;

                        if ($mesNombre && isset($valoresMeses[$mesNombre])) {
                            $valoresMeses[$mesNombre] += (float) $item->CostoFianza;
                        }
                    }

                    foreach ($valoresMeses as $mes => $total) {
                        if (round($total, 0) > 0) {
                            $reporte[] = [
                                'NombreInsumo' => $nombreInsumo,
                                'Mes'          => $mes,
                                'Costo'        => round($total, 0),
                                'Orden'        => 4,
                                'GerenciaID'   => $gerenciaID,
                            ];
                        }
                    }
                }

                return $reporte;
            }
            
            // Método para obtener inversiones, considerando renovaciones de fianzas y ciertos insumos de categoría específica, y agrupando por mes
            private function obtenerInversiones(int $gerenciaID): array
            {
                $reporte = [];

                $totalRenovacionFianzas = (float) $this->lineasQuery($gerenciaID)
                    ->whereNotNull('il.MontoRenovacionFianza')
                    ->sum('il.MontoRenovacionFianza');

                $rows = $this->inventarioQuery($gerenciaID)
                    ->whereIn('i.FrecuenciaDePago', ['Anual', 'Pago único'])
                    ->whereIn('i.CateogoriaInsumo', [ 'LAPTOP','MONITOR','NO BREAK', 'TABLET','IMPRESORA' ])
                    ->get();

                $valoresMeses = array_fill_keys(array_values(self::NUM_TO_NAME), 0.0);

                foreach ($rows as $row) {
                    $mesRaw = $row->MesDePago;
                    $mesLower = strtolower(trim((string)$mesRaw));
                    $mesNum = self::MES_MAP[$mesLower] ?? null;
                    $mesNormalized = self::NUM_TO_NAME[$mesNum] ?? null;
                    if ($mesNormalized && isset($valoresMeses[$mesNormalized])) {
                        $valoresMeses[$mesNormalized] += (float) $row->CostoAnual;
                    }
                }

                $valoresMeses['Junio'] += $totalRenovacionFianzas;

                foreach ($valoresMeses as $mes => $total) {
                    if (round($total, 0) > 0) {
                        $reporte[] = [
                            'NombreInsumo' => 'INVERSIONES',
                            'Mes'          => $mes,
                            'Costo'        => round($total, 0),
                            'Orden'        => 6,
                            'GerenciaID'   => $gerenciaID,
                        ];
                    }
                }

                return $reporte;
            }

            // Método para obtener licencias, considerando reglas específicas para ciertos insumos de Windows según la gerencia, y agrupando por nombre de insumo y mes

            private function obtenerLicencias(int $gerenciaID): array
            {
                $reporte = [];

                $costoWindows10Pro = 0.00;
                $costoWindows11Pro = 0.00;

                if (!in_array($gerenciaID, [17, 18], true)) {
                    $maxWindows10 = DB::table('inventarioinsumo')
                        ->where('NombreInsumo', 'WINDOWS 10 PRO')
                        ->max(DB::raw('CostoMensual * 1.07')) ?? 0.00;
                    $costoWindows10Pro = round((float) $maxWindows10);

                    $maxWindows11 = DB::table('inventarioinsumo')
                        ->where('NombreInsumo', 'WINDOWS 11 PRO')
                        ->max(DB::raw('CostoAnual * 1.07')) ?? 0.00;
                    $costoWindows11Pro = round((float) $maxWindows11);
                }

                $rows = $this->inventarioQuery($gerenciaID)
                    ->whereIn('i.FrecuenciaDePago', ['Anual', 'Pago único'])
                    ->where('i.CateogoriaInsumo', 'LICENCIA')
                    ->get();

                $grouped = $rows->groupBy('NombreInsumo');

                foreach ($grouped as $nombreInsumo => $items) {
                    if (in_array($gerenciaID, [17, 18], true) && str_starts_with(strtoupper($nombreInsumo), 'WINDOWS')) {
                        continue;
                    }

                    $valoresMeses = array_fill_keys(array_values(self::NUM_TO_NAME), 0.0);

                    foreach ($items as $item) {
                        $mesRaw = $item->MesDePago;
                        $mesLower = strtolower(trim((string)$mesRaw));
                        $mesNum = self::MES_MAP[$mesLower] ?? null;
                        $mesNormalized = self::NUM_TO_NAME[$mesNum] ?? null;
                        if (!$mesNormalized || !isset($valoresMeses[$mesNormalized])) {
                            continue;
                        }

                        $costo = 0.0;
                        if ($nombreInsumo === 'WINDOWS 10 HOME') {
                            $costo = $costoWindows10Pro;
                        } elseif ($nombreInsumo === 'WINDOWS 11 HOME') {
                            $costo = $costoWindows11Pro;
                        } elseif (in_array($nombreInsumo, ['WINDOWS 10 PRO', 'WINDOWS 11 PRO'], true)) {
                            $costo = 0.00;
                        } else {
                            $costo = (float) $item->CostoAnual * 1.07;
                        }

                        $valoresMeses[$mesNormalized] += $costo;
                    }

                    foreach ($valoresMeses as $mes => $total) {
                        if (round($total, 0) > 0) {
                            $reporte[] = [
                                'NombreInsumo' => $nombreInsumo,
                                'Mes'          => $mes,
                                'Costo'        => round($total, 0),
                                'Orden'        => 2,
                                'GerenciaID'   => $gerenciaID,
                            ];
                        }
                    }
                }

                return $reporte;
            }

            // Método para obtener otros insumos anuales, excluyendo ciertas categorías específicas, y agrupando por nombre de insumo y mes, con reglas especiales para "REPARACIONES" y "RENTA DE IMPRESORA"

            private function obtenerOtrosAnuales(int $gerenciaID): array
            {
                $reporte = [];

                $rows = $this->inventarioQuery($gerenciaID)
                    ->whereIn('i.FrecuenciaDePago', ['Anual', 'Pago único'])
                    ->whereNotIn('i.CateogoriaInsumo', [
                        'LAPTOP',
                        'MONITOR',
                        'NO BREAK',
                        'LICENCIA',
                        'ACCESORIOS',
                        'BATERIA UPS',
                        'IMPRESORA',
                    ])
                    ->get();

                $mapped = $rows->map(function ($item) {
                    $item->MappedNombreInsumo = $item->CateogoriaInsumo === 'REPARACIONES'? 'ACCESORIOS Y REFACCIONES'
                        : $item->NombreInsumo;
                    return $item;
                });

                $grouped = $mapped->groupBy('MappedNombreInsumo');

                foreach ($grouped as $nombreInsumo => $items) {
                    $valoresMeses = array_fill_keys(array_values(self::NUM_TO_NAME), 0.0);

                    foreach ($items as $item) {
                        $mesRaw = $item->MesDePago;
                        $mesLower = strtolower(trim((string)$mesRaw));
                        $mesNum = self::MES_MAP[$mesLower] ?? null;
                        $mesNormalized = self::NUM_TO_NAME[$mesNum] ?? null;
                        if (!$mesNormalized || !isset($valoresMeses[$mesNormalized])) {
                            continue;
                        }

                        $costo = $item->CateogoriaInsumo === 'RENTA DE IMPRESORA'
                            ? (float) $item->CostoAnual
                            : (float) $item->CostoAnual * 1.07;

                        $valoresMeses[$mesNormalized] += $costo;
                    }

                    foreach ($valoresMeses as $mes => $total) {
                        if (round($total, 0) > 0) {
                            $reporte[] = [
                                'NombreInsumo' => $nombreInsumo,
                                'Mes'          => $mes,
                                'Costo'        => round($total, 0),
                                'Orden'        => 3,
                                'GerenciaID'   => $gerenciaID,
                            ];
                        }
                    }
                }

                return $reporte;
            }

            // Método para obtener insumos mensuales, considerando ciertos insumos de categoría específica, y agrupando por nombre de insumo y mes

            private function obtenerMensuales(int $gerenciaID): array
            {
                $rows = $this->inventarioQuery($gerenciaID)
                    ->where('i.FrecuenciaDePago', 'Mensual')
                    ->whereIn('i.CateogoriaInsumo', [ 'LICENCIA', 'HOSTING', 'STARLINK','INTERNET','TABLET' ])
                    ->get();

                $grouped = $rows->groupBy('NombreInsumo');
                $resultado = [];

                foreach ($grouped as $nombreInsumo => $items) {
                    $costoTotal = 0.0;
                    foreach ($items as $item) {
                        $costoTotal += in_array($item->CateogoriaInsumo, ['INTERNET', 'STARLINK'], true)
                            ? (float) $item->CostoMensual
                            : ((float) $item->CostoMensual * 1.07);
                    }
                    if (round($costoTotal, 0) > 0) {
                        foreach (self::NUM_TO_NAME as $mes) {
                            $resultado[] = [
                                'NombreInsumo' => $nombreInsumo,
                                'Mes'          => $mes,
                                'Costo'        => round($costoTotal, 0),
                                'Orden'        => 1,
                                'GerenciaID'   => $gerenciaID,
                            ];
                        }
                    }
                }
                return $resultado;
            }

    private function procesarInsumosParaCorte(int $gerenciaID, int $año): array
    {
        $rows = collect($this->generarReporteInsumos($gerenciaID));
        $toInsert = [];

        $rows->groupBy('NombreInsumo')->each(function (Collection $items, $nombre) use ($año, $gerenciaID, &$toInsert) {
            foreach ($items as $r) {
                $costo  = round((float) ($r['Costo'] ?? 0), 2);
                if ($costo <= 0) continue;

                $mesRaw = $r['Mes'] ?? null;
                $mesNum = is_numeric($mesRaw)
                    ? max(1, min(12, (int) $mesRaw))
                    : (self::MES_MAP[strtolower((string) $mesRaw)] ?? null);

                if (!$mesNum) continue;

                $mesNombre = self::NUM_TO_NAME[$mesNum] ?? null;
                if (!$mesNombre) continue;

                $toInsert[] = [
                    'NombreInsumo' => (string) $nombre,
                    'Mes'          => $mesNombre,
                    'Costo'        => $costo,
                    'Margen'       => 0,
                    'CostoTotal'   => $costo,
                    'Anio'         => $año,
                    'GerenciaID'   => $gerenciaID,
                ];
            }
        });

        return $toInsert;
    }

    public function obtenerCorteGuardado(Request $request)
    {
        $anio       = (int) $request->input('anio');
        $gerenciaID = (int) $request->input('gerenciaID');

        if (!$anio || !$gerenciaID) {
            return response()->json(['message' => 'Faltan año o gerencia', 'data' => []], 422);
        }

        $rows = Cortes::where('GerenciaID', $gerenciaID)
            ->where('Anio', $anio)
            ->whereNull('deleted_at')
            ->orderBy('NombreInsumo')
            ->orderBy('Costo')
            ->orderBy('Mes')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'No hay presupuesto guardado para esta gerencia y año', 'data' => []], 200);
        }

        $resultado = [];
        $porInsumo = $rows->groupBy('NombreInsumo');

        foreach ($porInsumo as $nombreInsumo => $registros) {
            $porCosto = $registros->groupBy(fn($r) => (string) round((float) ($r->Costo ?? 0), 2));

            foreach ($porCosto as $costoKey => $regsVariante) {
                $costoBase      = (float) $costoKey;
                $margen         = (float) ($regsVariante->first()->Margen ?? 0);
                $porMes         = [];
                $sumaCostoTotal = 0;

                foreach ($regsVariante as $r) {
                    $mes        = $r->Mes ?? '';
                    $costoTotal = (float) ($r->CostoTotal ?? 0);
                    $porMes[$mes] = ['Costo' => round($costoBase, 2), 'CostoTotal' => round($costoTotal, 2)];
                    $sumaCostoTotal += $costoTotal;
                }

                foreach (array_values(self::NUM_TO_NAME) as $m) {
                    if (!isset($porMes[$m])) {
                        $porMes[$m] = ['Costo' => 0, 'CostoTotal' => 0];
                    }
                }

                $resultado[] = [
                    'NombreInsumo'    => html_entity_decode((string) $nombreInsumo, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'Meses'           => $porMes,
                    'Costo'           => round($costoBase, 2),
                    'Margen'          => $margen,
                    'CostoConMargen'  => round($costoBase * (1 + $margen / 100), 2),
                    'CostoTotalAnual' => round($sumaCostoTotal, 2),
                ];
            }
        }

        return response()->json(['message' => 'Presupuesto guardado', 'data' => $resultado], 200);
    }

    public function readXml(Request $request)
    {
        $request->validate(['imagen' => 'required|mimes:xml|max:2048']);

        $content = file_get_contents($request->file('imagen')->getRealPath());
        libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($content);
            if ($xml === false) throw new \RuntimeException('XML inválido');

            $namespaces = $xml->getDocNamespaces(true);
            $cfdiUri    = $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
            $xml->registerXPathNamespace('cfdi', $cfdiUri);

            $emisorNode   = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor');
            $nombreEmisor = $emisorNode ? (string) $emisorNode[0]['Nombre'] : null;
            $conceptos    = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: [];

            $catalogo = Insumos::select(['ID', 'NombreInsumo'])->get()->map(fn($insumo) => [
                'id'     => $insumo->ID,
                'nombre' => $insumo->NombreInsumo,
                'norm'   => $this->normalizeText($insumo->NombreInsumo),
            ])->all();

            $datos = [];
            foreach ($conceptos as $concepto) {
                $descripcion = (string) ($concepto['Descripcion'] ?? '');
                $importe     = (float)  ($concepto['Importe']     ?? 0);

                [$best, $score] = $this->matchInsumo($descripcion, $catalogo);

                if (($best === null || $score < 60) && $nombreEmisor) {
                    if (str_contains($this->normalizeText($nombreEmisor), 'starlink')) {
                        $star = $this->matchPorKeyword('starlink', $catalogo);
                        if ($star) { $best = $star; $score = 95; }
                    }
                }

                $datos[] = [
                    'insumo'      => $best['nombre'] ?? null,
                    'insumo_id'   => $best['id']     ?? null,
                    'descripcion' => $descripcion,
                    'importe'     => $importe,
                    'confianza'   => $score ?? 0,
                    'emisor'      => $nombreEmisor,
                ];
            }

            return response()->json(['success' => 'XML leído con éxito', 'emisor' => $nombreEmisor, 'datos' => $datos]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al leer XML: ' . $e->getMessage()], 422);
        }
    }

    private function normalizeText(string $txt): string
    {
        $txt = mb_strtolower($txt, 'UTF-8');
        $txt = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
        $txt = preg_replace('/[^a-z0-9\s]/i', ' ', $txt);
        $txt = preg_replace('/\s+/', ' ', $txt);
        return trim($txt ?? '');
    }

    private function matchInsumo(string $descripcion, array $catalogo): array
    {
        $normDesc = $this->normalizeText($descripcion);
        if ($normDesc === '') return [null, 0];

        $best = null;
        $bestScore = 0;

        foreach ($catalogo as $row) {
            if ($row['norm'] !== '' && mb_stripos($normDesc, $row['norm']) !== false) return [$row, 95];
            if ($normDesc !== '' && mb_stripos($row['norm'], $normDesc) !== false) {
                $best      = $row;
                $bestScore = max($bestScore, 85);
                continue;
            }
            similar_text($normDesc, $row['norm'], $pct);
            if ($pct > $bestScore) { $bestScore = $pct; $best = $row; }
        }

        return ($best && $bestScore >= 60) ? [$best, round($bestScore, 2)] : [null, round($bestScore, 2)];
    }

    private function matchPorKeyword(string $keyword, array $catalogo): ?array
    {
        $k = $this->normalizeText($keyword);
        foreach ($catalogo as $row) {
            if ($row['norm'] !== '' && str_contains($row['norm'], $k)) return $row;
        }
        return null;
    }

    public function create()
    {
        return view('cortes.create');
    }

    public function show($id)
    {
        $cortes = $this->cortesRepository->find($id);
        if (empty($cortes)) {
            Flash::error('Cortes not found');
            return redirect(route('cortes.index'));
        }
        return view('cortes.show')->with('cortes', $cortes);
    }

    public function edit($id)
    {
        $cortes = $this->cortesRepository->find($id);
        if (empty($cortes)) {
            Flash::error('Cortes not found');
            return redirect(route('cortes.index'));
        }
        return view('cortes.edit')->with('cortes', $cortes);
    }

    public function update($id, UpdateCortesRequest $request)
    {
        $cortes = $this->cortesRepository->find($id);
        if (empty($cortes)) {
            Flash::error('Cortes not found');
            return redirect(route('cortes.index'));
        }
        $this->cortesRepository->update($request->all(), $id);
        Flash::success('Cortes updated successfully.');
        return redirect(route('cortes.index'));
    }

    public function destroy($id)
    {
        $cortes = $this->cortesRepository->find($id);
        if (empty($cortes)) {
            Flash::error('Cortes not found');
            return redirect(route('cortes.index'));
        }
        $this->cortesRepository->delete($id);
        Flash::success('Cortes deleted successfully.');
        return redirect(route('cortes.index'));
    }

    public function generarReporteInsumos(int $gerenciaID): array
    {
        return array_merge(
            $this->obtenerMensuales($gerenciaID),
            $this->obtenerLicencias($gerenciaID),
            $this->obtenerOtrosAnuales($gerenciaID),
            $this->obtenerFianzas($gerenciaID),
            $this->obtenerLineasMensuales($gerenciaID),
            $this->obtenerInversiones($gerenciaID)
        );
    }
}