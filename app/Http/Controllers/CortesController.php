<?php

namespace App\Http\Controllers;

use App\DataTables\CortesDataTable;
use App\Http\Requests\UpdateCortesRequest;
use App\Repositories\CortesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Cortes;
use App\Models\Gerencia;
use App\Models\Insumos;
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
        $this->middleware('permission:cortes.view', ['only' => ['index', 'obtenerCorteGuardado']]);
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
            return $request->expectsJson()
                ? response()->json(['message' => 'Por favor, selecciona una gerencia', 'data' => []], 422)
                : back()->with('error', 'Por favor, selecciona una gerencia');
        }

        try {
            $rows = collect(DB::select('CALL ObtenerInsumosAnualesPorGerencia6(?)', [$gerenciaID]));

            if ($rows->isEmpty()) {
                return $request->expectsJson()
                    ? response()->json(['data' => []])
                    : back()->with('warning', 'No hay datos para la gerencia');
            }

            $resultado = $rows
                ->groupBy('NombreInsumo')
                ->map(function (Collection $items, $nombre) {
                    $montosPorMes = $items
                        ->map(function ($r) {
                            $costo  = round((float) ($r->Costo ?? 0), 2);
                            if ($costo <= 0) return null;
                            $mesRaw = $r->Mes ?? null;
                            $mesNum = is_numeric($mesRaw)
                                ? max(1, min(12, (int) $mesRaw))
                                : (self::MES_MAP[strtolower((string) $mesRaw)] ?? null);
                            if (!$mesNum) return null;
                            return ['Mes' => $mesNum, 'Costo' => $costo];
                        })
                        ->filter()
                        ->values();

                    if ($montosPorMes->isEmpty()) return null;

                    $distintos = $montosPorMes->pluck('Costo')->unique()->sort()->values()->all();

                    return [
                        'NombreInsumo'  => (string) $nombre,
                        'MontosPorMes'  => $montosPorMes->all(),
                        'Distintos'     => $distintos,
                        'SelectedIndex' => 0,
                        'Margen'        => 0,
                    ];
                })
                ->filter()
                ->values();

            return DataTables::of($resultado)->make(true);
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

    private function procesarInsumosParaCorte(int $gerenciaID, int $año): array
    {
        $rows     = collect(DB::select('CALL ObtenerInsumosAnualesPorGerencia6(?)', [$gerenciaID]));
        $toInsert = [];

        $rows->groupBy('NombreInsumo')->each(function (Collection $items, $nombre) use ($año, $gerenciaID, &$toInsert) {
            foreach ($items as $r) {
                $costo  = round((float) ($r->Costo ?? 0), 2);
                if ($costo <= 0) continue;

                $mesRaw = $r->Mes ?? null;
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
                    'NombreInsumo'    => $nombreInsumo,
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
}