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
    /** @var CortesRepository $cortesRepository*/
    private $cortesRepository;

    public function __construct(CortesRepository $cortesRepo)
    {
        $this->cortesRepository = $cortesRepo;

        $this->middleware('permission:cortes.view', ['only' => ['index', 'obtenerCorteGuardado']]);
    }

    /**
     * Display a listing of the Cortes.
     *
     * @param CortesDataTable $cortesDataTable
     *
     * @return Response
     */
    /* public function index(CortesDataTable $cortesDataTable)
    {
        return $cortesDataTable->render('cortes.index');
    } */

    public function index(Request $request)
    {
        $gerencia = Gerencia::where('estado', 1)->orderBy('NombreGerencia')->get();

        $meses = [
            'Enero',
            'Febrero',
            'Marzo',
            'Abril',
            'Mayo',
            'Junio',
            'Julio',
            'Agosto',
            'Septiembre',
            'Octubre',
            'Noviembre',
            'Diciembre'
        ];

        $anioActual = (int) Carbon::now()->year;
        $years = range($anioActual - 5, $anioActual + 1);
        $years = array_reverse($years);

        $anioConsulta = (int) ($request->input('anio') ?? $anioActual);
        $anioConsulta = in_array($anioConsulta, $years, true) ? $anioConsulta : $anioActual;

        $gerenciasConCorte = Cortes::where('Anio', $anioConsulta)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('GerenciaID')
            ->toArray();
        $gerenciasSinCorte = $gerencia->whereNotIn('GerenciaID', $gerenciasConCorte)->values();

        $gerenciasConCorteIds = $gerenciasConCorte;
        $gerenciasConCorte = $gerencia->whereIn('GerenciaID', $gerenciasConCorteIds)->values();

        return view('cortes.index', compact('meses', 'gerencia', 'years', 'anioActual', 'anioConsulta', 'gerenciasConCorte', 'gerenciasSinCorte'));
    }

    public function indexVista(Request $request)
    {
        $gerenciaID = $request->input('gerenci_id');
        $mes = $request->input('mes');

        if ($request->ajax()) {
            if ($gerenciaID && $mes) {
                $query = DB::table('cortes')
                    ->where('GerenciaID', $gerenciaID)
                    ->where('Mes', $mes);

                return DataTables::of($query)
                    ->addColumn('action', function ($row) {
                        return view('cortes.datatables_actions', ['id' => $row->CortesID])->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } else {
                return DataTables::of(collect([]))->make(true);
            }
        }

        return view('cortes.index');
    }

    public function obtenerInsumos(Request $request)
    {
        $gerenciaID = $request->input('gerenciaID');

        if (empty($gerenciaID)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Por favor, selecciona una gerencia',
                    'data'    => [],
                ], 422);
            }
            return back()->with('error', 'Por favor, selecciona una gerencia');
        }

        try {
            $rows = collect(DB::select('CALL ObtenerInsumosAnualesPorGerencia6(?)', [$gerenciaID]));

            if ($rows->isEmpty()) {
                return $request->expectsJson()
                    ? response()->json(['data' => []])
                    : back()->with('warning', 'No hay datos para la gerencia');
            }

            $mesMap = [
                'enero' => 1,
                'febrero' => 2,
                'marzo' => 3,
                'abril' => 4,
                'mayo' => 5,
                'junio' => 6,
                'julio' => 7,
                'agosto' => 8,
                'septiembre' => 9,
                'octubre' => 10,
                'noviembre' => 11,
                'diciembre' => 12
            ];

            $resultado = $rows
                ->groupBy('NombreInsumo')
                ->map(function (Collection $items, $nombre) use ($mesMap) {
                    $montosPorMes = $items
                        ->map(function ($r) use ($mesMap) {
                            $costo = round((float) ($r->Costo ?? 0), 2);
                            if ($costo <= 0) return null;

                            $mesRaw = $r->Mes ?? null;
                            $mesNum = is_numeric($mesRaw)
                                ? max(1, min(12, (int) $mesRaw))
                                : ($mesMap[strtolower((string) $mesRaw)] ?? null);

                            if (!$mesNum) return null;

                            return ['Mes' => $mesNum, 'Costo' => $costo];
                        })
                        ->filter()
                        ->values();

                    if ($montosPorMes->isEmpty()) {
                        return null;
                    }

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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se pudo procesar los insumos presupuestados',
                    'data'    => [],
                ], 500);
            }
            return back()->with('error', 'No se pudo procesar los insumos presupuestados');
        }
    }

    public function readXml(Request $request)
    {
        $request->validate([
            'imagen' => 'required|mimes:xml|max:2048'
        ]);

        $file = $request->file('imagen');
        $content = file_get_contents($file->getRealPath());

        libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                throw new RuntimeException('XML inválido');
            }
            $namespaces = $xml->getDocNamespaces(true);
            $cfdiUri = $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
            $xml->registerXPathNamespace('cfdi', $cfdiUri);

            $emisorNode = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor');
            $nombreEmisor = $emisorNode ? (string) $emisorNode[0]['Nombre'] : null;

            $conceptos = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: [];

            $insumos = Insumos::select(['ID', 'NombreInsumo'])->get();
            $catalogo = $insumos->map(fn($insumo) => [
                'id'     => $insumo->ID,
                'nombre' => $insumo->NombreInsumo,
                'norm'   => $this->normalizeText($insumo->NombreInsumo),
            ])->all();

            $UMBRAL = 60;
            $datos = [];

            foreach ($conceptos as $concepto) {
                $descripcion = (string) ($concepto['Descripcion'] ?? '');
                $importe     = (float)  ($concepto['Importe'] ?? 0);

                [$best, $score] = $this->matchInsumo($descripcion, $catalogo);

                if (($best === null || $score < $UMBRAL) && $nombreEmisor) {
                    $normEmisor = $this->normalizeText($nombreEmisor);
                    if (str_contains($normEmisor, 'starlink')) {
                        $star = $this->matchPorKeyword('starlink', $catalogo);
                        if ($star) {
                            $best  = $star;
                            $score = 95;
                        }
                    }
                }

                $datos[] = [
                    'insumo'       => $best['nombre'] ?? null,
                    'insumo_id'    => $best['id'] ?? null,
                    'descripcion'  => $descripcion,
                    'importe'      => $importe,
                    'confianza'    => $score ?? 0,
                    'emisor'       => $nombreEmisor,
                ];
            }

            return response()->json([
                'success' => 'XML leído con éxito',
                'emisor'  => $nombreEmisor,
                'datos'   => $datos
            ]);
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
            if ($row['norm'] !== '' && mb_stripos($normDesc, $row['norm']) !== false) {
                return [$row, 95];
            }
            if ($normDesc !== '' && mb_stripos($row['norm'], $normDesc) !== false) {
                $best = $row;
                $bestScore = max($bestScore, 85);
                continue;
            }
            similar_text($normDesc, $row['norm'], $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $row;
            }
        }

        return ($best && $bestScore >= 60) ? [$best, round($bestScore, 2)] : [null, round($bestScore, 2)];
    }

    private function matchPorKeyword(string $keyword, array $catalogo): ?array
    {
        $k = $this->normalizeText($keyword);
        foreach ($catalogo as $row) {
            if ($row['norm'] !== '' && str_contains($row['norm'], $k)) {
                return $row;
            }
        }
        return null;
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'rows'                       => ['required', 'array', 'min:1'],
            'rows.*.NombreInsumo'        => ['required', 'string', 'max:255'],
            'rows.*.Mes'                 => ['required', 'integer', 'between:1,12'],
            'rows.*.Costo'               => ['required', 'numeric', 'min:0'],
            'rows.*.Margen'              => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.CostoTotal'          => ['required', 'numeric', 'min:0'],
            'rows.*.GerenciaID'          => ['required', 'integer'],
        ], [
            'rows.required' => 'No hay filas a guardar.',
        ]);

        $ids = collect($data['rows'])->pluck('GerenciaID')->unique()->values();
        if ($ids->count() !== 1) {
            return response()->json([
                'message' => 'Todas las filas deben pertenecer a la misma gerencia.'
            ], 422);
        }

        $gerenciaID = (int) $ids->first();
        // Año del corte: enviado por el front o año en curso (ya no se usa +1)
        $año = (int) ($request->input('anio') ?? Carbon::now()->year);

        $yaExiste = Cortes::where('GerenciaID', $gerenciaID)
            ->where('Anio', $año)
            ->exists();

        if ($yaExiste) {
            Cortes::where('GerenciaID', $gerenciaID)->where('Anio', $año)->delete();
        }

        $numToName = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        $toInsert = collect($data['rows'])->map(function (array $r) use ($año, $numToName) {
            $mes = $numToName[(int) $r['Mes']] ?? null;
            $costo  = round((float) $r['Costo'], 2);
            $margen = max(0, min(100, (float) $r['Margen']));
            $calc   = round($costo * (1 + $margen / 100), 2);

            return [
                'NombreInsumo' => (string) $r['NombreInsumo'],
                'Mes'          => $mes,
                'Costo'        => $costo,
                'Margen'       => $margen,
                'CostoTotal'   => $calc,
                'Anio'          => $año,
                'GerenciaID'   => (int) $r['GerenciaID'],
            ];
        });


        if ($toInsert->isEmpty()) {
            return response()->json(['message' => 'Nada que guardar'], 422);
        }

        $insertados = 0;
        DB::transaction(function () use ($toInsert, &$insertados) {
            $insertados = Cortes::insertOrIgnore($toInsert->all());
        });

        return response()->json([
            'message'  => 'Corte anual registrado',
        ], 201);
    }

    /**
     * Obtener corte ya guardado por año y gerencia (para vista de solo lectura con costo, costo total, costo + margen).
     *
     * @param Request $request (anio, gerenciaID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerCorteGuardado(Request $request)
    {
        $anio = (int) $request->input('anio');
        $gerenciaID = (int) $request->input('gerenciaID');

        if (!$anio || !$gerenciaID) {
            return response()->json([
                'message' => 'Faltan año o gerencia',
                'data'    => [],
            ], 422);
        }

        $mesesOrden = [
            'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6,
            'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12,
        ];
        $nombresMeses = array_keys($mesesOrden);

        $rows = Cortes::where('GerenciaID', $gerenciaID)
            ->where('Anio', $anio)
            ->whereNull('deleted_at')
            ->orderBy('NombreInsumo')
            ->orderBy('Mes')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'message' => 'No hay corte guardado para esta gerencia y año',
                'data'    => [],
            ], 200);
        }

        $agrupado = $rows->groupBy('NombreInsumo')->map(function ($registros, $nombreInsumo) use ($nombresMeses, $mesesOrden) {
            $porMes = [];
            $sumaCosto = 0;
            $sumaCostoTotal = 0;
            $margen = 0;
            $count = 0;

            foreach ($registros as $r) {
                $mes = $r->Mes ?? '';
                $costo = (float) ($r->Costo ?? 0);
                $costoTotal = (float) ($r->CostoTotal ?? 0);
                $margen = (float) ($r->Margen ?? 0);

                $porMes[$mes] = [
                    'Costo'      => round($costo, 2),
                    'CostoTotal' => round($costoTotal, 2),
                ];
                $sumaCosto += $costo;
                $sumaCostoTotal += $costoTotal;
                $count++;
            }

            foreach ($nombresMeses as $m) {
                if (!isset($porMes[$m])) {
                    $porMes[$m] = ['Costo' => 0, 'CostoTotal' => 0];
                }
            }

            return [
                'NombreInsumo'   => $nombreInsumo,
                'Meses'          => $porMes,
                'Costo'          => round($sumaCosto / max(1, $count), 2),
                'Margen'         => $margen,
                'CostoTotalAnual' => round($sumaCostoTotal, 2),
                'Orden'          => $count,
            ];
        })->values()->all();

        return response()->json([
            'message' => 'Corte guardado',
            'data'    => $agrupado,
        ], 200);
    }

    /**
     * Show the form for creating a new Cortes.
     *
     * @return Response
     */
    public function create()
    {

        return view('cortes.create');
    }

    /**
     * Display the specified Cortes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        return view('cortes.show')->with('cortes', $cortes);
    }

    /**
     * Show the form for editing the specified Cortes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        return view('cortes.edit')->with('cortes', $cortes);
    }

    /**
     * Update the specified Cortes in storage.
     *
     * @param int $id
     * @param UpdateCortesRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCortesRequest $request)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        $cortes = $this->cortesRepository->update($request->all(), $id);

        Flash::success('Cortes updated successfully.');

        return redirect(route('cortes.index'));
    }

    /**
     * Remove the specified Cortes from storage.
     *
     * @param int $id
     *
     * @return Response
     */
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
