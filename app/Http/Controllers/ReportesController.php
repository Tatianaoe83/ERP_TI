<?php

namespace App\Http\Controllers;

use App\DataTables\ReportesDataTable;
use App\Exports\ReporteExport;
use App\Helpers\JoinHelper;
use App\Helpers\ReporteHelper;
use Illuminate\Http\Request;
use App\Http\Requests\CreateReportesRequest;
use App\Http\Requests\UpdateReportesRequest;
use App\Repositories\ReportesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Reportes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Response;
use Stringable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ReportesController extends AppBaseController
{
    /** @var ReportesRepository $reportesRepository*/
    private $reportesRepository;

    public function __construct(ReportesRepository $reportesRepo)
    {
        $this->reportesRepository = $reportesRepo;

        $this->middleware('permission:ver-reportes')->only(['index', 'show', 'preview', 'showData']);
        $this->middleware('permission:crear-reportes')->only(['create', 'store']);
        $this->middleware('permission:editar-reportes')->only(['edit', 'update']);
        $this->middleware('permission:borrar-reportes')->only(['destroy']);
        $this->middleware('permission:exportar-reportes')->only([
            'exportPdf', 'exportExcel',
            'iniciarExportExcel', 'statusExport', 'descargarExport',
        ]);
    }

    // ── Relaciones universales ─────────────────────────────────────────────────
    protected array $relacionesUniversales = [
        'categorias' => [
            'tiposdecategorias' => ['tiposdecategorias.ID', '=', 'categorias.TipoID'],
        ],
        'departamentos' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'departamentos.GerenciaID'],
        ],
        'empleados' => [
            'obras'            => ['obras.ObraID',                '=', 'empleados.ObraID'],
            'puestos'          => ['puestos.PuestoID',            '=', 'empleados.PuestoID'],
            'inventarioinsumo' => ['inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventarioequipo' => ['inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventariolineas' => ['inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID'],
        ],
        'equipos' => [
            'categorias' => ['categorias.ID', '=', 'equipos.CategoriaID'],
        ],
        'gerencia' => [],
        'insumos' => [
            'categorias' => ['categorias.ID', '=', 'insumos.CategoriaID'],
        ],
        'inventarioequipo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioequipo.EmpleadoID'],
        ],
        'inventarioinsumo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioinsumo.EmpleadoID'],
            'insumos'   => ['insumos.InsumoID',     '=', 'inventarioinsumo.InsumoID'],
        ],
        'inventariolineas' => [
            'empleados'         => ['empleados.EmpleadoID',      '=', 'inventariolineas.EmpleadoID'],
            'lineastelefonicas' => ['lineastelefonicas.LineaID',  '=', 'inventariolineas.LineaID'],
            'obras'             => ['obras.ObraID',               '=', 'inventariolineas.ObraID'],
        ],
        'lineastelefonicas' => [
            'obras'  => ['obras.ObraID', '=', 'lineastelefonicas.ObraID'],
            'planes' => ['planes.ID',    '=', 'lineastelefonicas.PlanID'],
        ],
        // obras → unidadesdenegocio es seguro (N:1).
        // unidadesdenegocio → gerencia NO se agrega aquí porque es 1:N.
        'obras' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'obras.UnidadNegocioID'],
        ],
        'puestos' => [
            'departamentos' => ['departamentos.DepartamentoID', '=', 'puestos.DepartamentoID'],
        ],
        'planes' => [
            'companiaslineastelefonicas' => ['companiaslineastelefonicas.ID', '=', 'planes.CompaniaID'],
        ],
        // unidadesdenegocio NO tiene ruta a gerencia (sería 1:N = duplicados).
        'unidadesdenegocio' => [],
    ];

    // ── index ──────────────────────────────────────────────────────────────────
    public function index(ReportesDataTable $dataTable)
    {
        if (request()->ajax()) {
            $query = Reportes::select(['id', 'title']);

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    return view('reportes.datatables_actions', ['id' => $row->id])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $dataTable->render('reportes.index');
    }

    // ── create / store ─────────────────────────────────────────────────────────
    public function create()
    {
        return view('reportes.create');
    }

    public function store(CreateReportesRequest $request)
    {
        $reportes = $this->reportesRepository->create($request->all());
        Flash::success('Reporte guardado correctamente.');
        return redirect(route('reportes.index'));
    }

    // ── show (server-side DataTable del reporte dinámico) ─────────────────────
    public function show($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata = json_decode($reportes->query_details, true);

        if (!$metadata || !isset($metadata['tabla_principal'])) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se pudo interpretar la metadata del reporte.');
        }

        $columnas = $metadata['columnas'] ?? [];

        return view('reportes.show', compact('reportes', 'columnas'));
    }

    /**
     * Endpoint AJAX que alimenta el DataTable del reporte dinámico.
     */
    public function showData(Request $request, $id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        $metadata = json_decode($reportes->query_details, true);

        $metadata['limite'] = null;

        try {
            $query = ReporteHelper::construirQuery($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $columnas = $metadata['columnas'] ?? [];

        $aliasMap = [];
        foreach ($columnas as $col) {
            $col = trim($col);
            if (stripos($col, ' as ') !== false) {

                [$expr, $alias] = preg_split('/\s+as\s+/i', $col);
                $aliasMap[trim($alias)] = trim($expr);
            } elseif (str_contains($col, '.')) {
                $alias = last(explode('.', $col));
                $aliasMap[$alias] = $col;
            } else {
                $aliasMap[$col] = $col;
            }
        }

        $search = $request->input('search.value', '');
        $request->merge(['search' => ['value' => '', 'regex' => 'false']]);

        $dt = DataTables::of($query);

        if (!empty(trim($search))) {
            $dt->filter(function ($query) use ($search, $aliasMap) {
                $query->where(function ($q) use ($search, $aliasMap) {
                    foreach ($aliasMap as $alias => $expr) {
                        $q->orWhereRaw("LOWER({$expr}) LIKE ?", ['%' . strtolower($search) . '%']);
                    }
                });
            });
        }

        return $dt->make(true);
    }

    // ── edit / update ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata              = json_decode($reportes->query_details, true);
        $tablaPrincipal        = $metadata['tabla_principal']   ?? null;
        $tablaRelacionInput    = $metadata['tabla_relacion']    ?? null;
        $ordenCol              = $metadata['ordenColumna']      ?? null;
        $ordenDir              = $metadata['ordenDireccion']    ?? null;
        $limite                = $metadata['limite']            ?? null;
        $columnasSeleccionadas = $metadata['columnas']          ?? [];
        $tablaRelacion         = is_array($tablaRelacionInput) ? $tablaRelacionInput : [$tablaRelacionInput];
        $condiciones           = $metadata['filtros']           ?? [];

        $columnasPrincipales = $tablaPrincipal
            ? collect(Schema::getColumnListing($tablaPrincipal))
                ->reject(fn($col) => Str::endsWith($col, ['ID', 'Id', '_id', '_at', 'created_at', 'updated_at', 'deleted_at']))
                ->toArray()
            : [];

        $columnasRelacion = [];
        foreach ($tablaRelacion as $tablaRel) {
            if (Schema::hasTable($tablaRel)) {
                $columnasRelacion[$tablaRel] = collect(Schema::getColumnListing($tablaRel))
                    ->reject(fn($col) => Str::endsWith($col, ['ID', 'Id', '_id', '_at', 'created_at', 'updated_at', 'deleted_at']))
                    ->toArray();
            }
        }

        return view('reportes.edit', compact(
            'reportes', 'metadata', 'tablaPrincipal', 'tablaRelacion',
            'columnasPrincipales', 'columnasRelacion', 'ordenCol', 'ordenDir',
            'limite', 'columnasSeleccionadas', 'condiciones'
        ));
    }

    public function update($id, UpdateReportesRequest $request)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadataOriginal = json_decode($reportes->query_details, true);
        $nuevoMetadata    = array_merge($metadataOriginal, [
            'columnas'        => $request->input('columnas', []),
            'ordenColumna'    => $request->input('ordenColumna'),
            'ordenDireccion'  => $request->input('ordenDireccion'),
            'limite'          => $request->input('limite'),
            'filtros'         => $request->input('filtros', []),
        ]);

        $reportes->query_details = json_encode($nuevoMetadata);
        $reportes->save();

        Flash::success('Reporte actualizado correctamente.');
        return redirect(route('reportes.index'));
    }

    // ── destroy ────────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $this->reportesRepository->delete($id);
        Flash::success('Reporte eliminado correctamente.');
        return redirect(route('reportes.index'));
    }

    // ── exportPdf ──────────────────────────────────────────────────────────────
    public function exportPdf(Request $request, $id)
    {
        set_time_limit(600);
        ini_set('memory_limit', '1024M');

        $reportes = $this->reportesRepository->find($id);
        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata = json_decode($reportes->query_details, true);
        if (!$metadata || !isset($metadata['tabla_principal'])) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se pudo interpretar la metadata del reporte.');
        }

        $metadata['limite'] = null;

        try {
            $resultado = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return redirect()->route('reportes.index')
                ->with('error', 'Error al ejecutar la consulta: ' . $e->getMessage());
        }

        if ($resultado->isEmpty()) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se encontraron resultados para el reporte.');
        }

        $columns        = collect($resultado->first())->keys()->map(fn($key) => ['title' => $key, 'field' => $key])->values();
        $nombre_reporte = $reportes->title;

        $pdf = Pdf::loadView('reportes.exportPdf', compact('reportes', 'resultado', 'columns', 'nombre_reporte'))
            ->setPaper('letter', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'Arial');

        $token    = $request->query('downloadToken', '');
        $filename = Str::slug($reportes->title) . '.pdf';

        $pdfContent = $pdf->output();

        $response = new \Symfony\Component\HttpFoundation\Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"', // ← fuerza descarga
            'Content-Length'      => strlen($pdfContent),
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);

        if ($token) {
            $response->headers->setCookie(
                new \Symfony\Component\HttpFoundation\Cookie(
                    $token,
                    'done',
                    time() + 60, 
                    '/',
                    null,       
                    false,       
                    false       
                )
            );
        }

        return $response;
    }

    // ── iniciarExportExcel (async: guarda en disco, el frontend hace polling) ───
    public function iniciarExportExcel(Request $request, $id)
    {
        $reportes = $this->reportesRepository->find($id);
        if (!$reportes) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        $metadata = json_decode($reportes->query_details, true);
        $metadata['limite'] = null;

        try {
            $query = ReporteHelper::construirQuery($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $primeraFila = (clone $query)->limit(1)->first();
        if (!$primeraFila) {
            return response()->json(['error' => 'Sin resultados'], 422);
        }

        $columnas = array_keys((array) $primeraFila);

        if (empty($query->orders)) {
            $tablaPrincipal = $metadata['tabla_principal'];
            $columnasPK     = Schema::getColumnListing($tablaPrincipal);
            $pkFallback     = !empty($columnasPK) ? "{$tablaPrincipal}.{$columnasPK[0]}" : '1';
            $query->orderBy($pkFallback);
        }

        $token    = Str::uuid()->toString();
        $filename = 'exports/' . $token . '.xlsx';

        set_time_limit(600);
        ini_set('memory_limit', '1024M');

        Excel::store(new ReporteExport($query, $columnas), $filename);

        $nombreDescarga = Str::slug($reportes->title) . '.xlsx';
        Storage::put('exports/' . $token . '.meta', $nombreDescarga);

        return response()->json(['token' => $token]);
    }

    // ── statusExport 
    public function statusExport(Request $request, $token)
    {
        if (!preg_match('/^[0-9a-f-]{36}$/', $token)) {
            return response()->json(['ready' => false]);
        }

        $exists = Storage::exists('exports/' . $token . '.xlsx');
        return response()->json(['ready' => $exists]);
    }

    // ── descargarExport —
    public function descargarExport(Request $request, $token)
    {
        if (!preg_match('/^[0-9a-f-]{36}$/', $token)) {
            abort(404);
        }

        $path = storage_path('app/exports/' . $token . '.xlsx');
        $meta = storage_path('app/exports/' . $token . '.meta');

        if (!file_exists($path)) {
            abort(404);
        }

        $nombre = file_exists($meta) ? trim(file_get_contents($meta)) : 'reporte.xlsx';

        return response()->download($path, $nombre)->deleteFileAfterSend(true);
    }

    public function exportExcel(Request $request, $id)
    {
        set_time_limit(600);
        ini_set('memory_limit', '1024M');

        $reportes = $this->reportesRepository->find($id);
        if (!$reportes) {
            return redirect()->route('reportes.index')->with('error', 'Reporte no encontrado');
        }

        $metadata = json_decode($reportes->query_details, true);
        $metadata['limite'] = null;

        try {
            $query = ReporteHelper::construirQuery($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return redirect()->route('reportes.index')
                ->with('error', 'Error al ejecutar la consulta: ' . $e->getMessage());
        }

        $primeraFila = (clone $query)->limit(1)->first();
        if (!$primeraFila) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se encontraron resultados para el reporte.');
        }

        $columnas = array_keys((array) $primeraFila);

        if (empty($query->orders)) {
            $tablaPrincipal = $metadata['tabla_principal'];
            $columnasPK     = Schema::getColumnListing($tablaPrincipal);
            $pkFallback     = !empty($columnasPK) ? "{$tablaPrincipal}.{$columnasPK[0]}" : '1';
            $query->orderBy($pkFallback);
        }

        $nombreArchivo = Str::slug($reportes->title) . '.xlsx';
        $token         = $request->query('downloadToken', '');

        $tmpToken = Str::uuid()->toString();
        $tmpPath  = 'exports/tmp_' . $tmpToken . '.xlsx';
        Excel::store(new ReporteExport($query, $columnas), $tmpPath);
        $fullPath = storage_path('app/' . $tmpPath);

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            fpassthru($stream);
            fclose($stream);
            @unlink($fullPath);
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            'Content-Length'      => filesize($fullPath),
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);

        if ($token) {
            $response->headers->setCookie(
                new \Symfony\Component\HttpFoundation\Cookie(
                    $token,   
                    'done',  
                    time() + 60, 
                    '/',      
                    null,    
                    false,
                    false  
                )
            );
        }

        return $response;
    }

    // ── preview ─────
    public function preview(Request $request)
    {
        try {
            $metadata = $request->all();

            if (empty($metadata['tabla_principal']) || empty($metadata['columnas'])) {
                return response()->json(['error' => 'Faltan datos esenciales.'], 422);
            }

            if (!is_array($metadata['tabla_relacion'])) {
                $metadata['tabla_relacion'] = json_decode($metadata['tabla_relacion'], true) ?? [];
            }

            // Preview: máximo 10 filas
            $metadata['limite'] = 10;

            $resultado = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
            $html      = view('reportes.preview', compact('resultado'))->render();

            return response()->json(['html' => $html]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Error en preview',
                'mensaje' => $e->getMessage(),
                'linea'   => $e->getLine(),
                'archivo' => $e->getFile(),
            ], 500);
        }
    }

    // ── autocomplete ───────────────────────────────────────────────────────────
    public function autocomplete(Request $request): JsonResponse
    {
        $tabla   = $request->get('tabla');
        $columna = $request->get('columna');
        $query   = $request->get('query');

        if (!Schema::hasTable($tabla) || !Schema::hasColumn($tabla, $columna)) {
            return response()->json([], 400);
        }

        $resultados = DB::table($tabla)
            ->select($columna)
            ->where($columna, 'like', '%' . $query . '%')
            ->groupBy($columna)
            ->limit(5)
            ->pluck($columna);

        return response()->json($resultados);
    }
}