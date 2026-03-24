<?php

namespace App\Http\Controllers;

use App\DataTables\ReportesDataTable;
use App\Exports\ReporteExport;
use App\Helpers\JoinHelper;
use App\Helpers\ReporteHelper;
use App\Helpers\RelacionesUniversales;
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

    /** @var array Relaciones centralizadas en App\Helpers\RelacionesUniversales */
    protected array $relacionesUniversales;

    public function __construct(ReportesRepository $reportesRepo)
    {
        $this->reportesRepository  = $reportesRepo;
        $this->relacionesUniversales = RelacionesUniversales::get();

        $this->middleware('permission:ver-reportes')->only(['index', 'show', 'preview', 'showData']);
        $this->middleware('permission:crear-reportes')->only(['create', 'store']);
        $this->middleware('permission:editar-reportes')->only(['edit', 'update']);
        $this->middleware('permission:borrar-reportes')->only(['destroy']);
        $this->middleware('permission:exportar-reportes')->only([
            'exportPdf', 'exportExcel',
            'iniciarExportExcel', 'iniciarExportPdf', 'statusExport', 'descargarExport', // <-- Aquí agregamos iniciarExportPdf
        ]);
    }

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

    // ── show ───────────────────────────────────────────────────────────────────
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
     * Endpoint AJAX — alimenta el DataTable server-side del reporte.
     * Yajra pagina en SQL, nunca se cargan 100k filas en PHP.
     */
    public function showData(Request $request, $id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        $metadata           = json_decode($reportes->query_details, true);
        $metadata['limite'] = null;

        try {
            $query = ReporteHelper::construirQuery($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $esUnion = str_contains((string) ($query->from ?? ''), 'union_result');

        // ── Construir aliasMap DEFINITIVO ─────────────────────────────────────────
        $aliasMap = [];
        $primeraFila = (clone $query)->limit(1)->first();
        
        if ($primeraFila) {
            $columnasResultantes = array_keys((array) $primeraFila);
            
            if ($esUnion) {
                // En UNION, todo es alias puro
                foreach ($columnasResultantes as $alias) {
                    $aliasMap[$alias] = "`{$alias}`";
                }
            } else {
                $columnasSeleccionadas = $metadata['columnas'] ?? [];
                
                foreach ($columnasResultantes as $alias) {
                    $tablaReal = null;
                    $columnaReal = null;

                    foreach ($columnasSeleccionadas as $colMeta) {
                        if (str_contains($colMeta, '.')) {
                            [$tbl, $col] = explode('.', $colMeta, 2);
                            
                            // Comparamos si el alias es igual a la columna, 
                            // o si el helper le puso el formato "tabla_columna"
                            if (
                                $alias === $col || 
                                $alias === "{$tbl}_{$col}" || 
                                str_replace('_', ' ', $col) === $alias
                            ) {
                                $tablaReal = $tbl;
                                $columnaReal = $col;
                                break; 
                            }
                        } else {
                            if ($alias === $colMeta) {
                                $columnaReal = $colMeta;
                                break;
                            }
                        }
                    }

                    // Armamos el mapeo final ya con backticks (comillas invertidas)
                    if ($tablaReal && $columnaReal) {
                        $aliasMap[$alias] = "`{$tablaReal}`.`{$columnaReal}`"; 
                    } elseif ($columnaReal) {
                        $aliasMap[$alias] = "`{$columnaReal}`";
                    } else {
                        $aliasMap[$alias] = "`{$alias}`";
                    }
                }
            }
        }

        $search = $request->input('search.value', '');
        
        // Apagamos la búsqueda nativa para que Yajra no intente hacerla mal por su cuenta
        $request->merge(['search' => ['value' => '', 'regex' => 'false']]);

        $dt = DataTables::of($query);

        if (!empty(trim($search))) {
            $dt->filter(function ($query) use ($search, $aliasMap, $esUnion) {
                $query->where(function ($q) use ($search, $aliasMap, $esUnion) {
                    foreach ($aliasMap as $alias => $columnaEscapada) {
                        if ($esUnion) {
                            $q->orWhereRaw(
                                "LOWER({$columnaEscapada}) LIKE ?",
                                ['%' . strtolower($search) . '%']
                            );
                        } else {
                            $q->orWhereRaw(
                                "LOWER({$columnaEscapada}) LIKE ?",
                                ['%' . strtolower($search) . '%']
                            );
                        }
                    }
                });
            });
        }

        return $dt->make(true);
    }
    // ── edit ───────────────────────────────────────────────────────────────────
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

    // ── update ─────────────────────────────────────────────────────────────────
    public function update($id, UpdateReportesRequest $request)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadataOriginal = json_decode($reportes->query_details, true);
        $nuevoMetadata    = array_merge($metadataOriginal, [
            'columnas'       => $request->input('columnas', []),
            'ordenColumna'   => $request->input('ordenColumna'),
            'ordenDireccion' => $request->input('ordenDireccion'),
            'limite'         => $request->input('limite'),
            'filtros'        => $request->input('filtros', []),
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

    public function exportPdf(Request $request, $id)
    {
        set_time_limit(0);
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

        $encabezados   = array_keys((array) $primeraFila);
        $nombreReporte = $reportes->title;
        $fecha         = now()->format('d/m/Y');
        $logoPath      = 'file://' . public_path('img/logo.png');
        $tmpDir        = storage_path('app/tmp_pdf_chunks');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // ── CSS compartido (CORREGIDO PARA DOMPDF) ──────────────────────────────
        $css = '
            @page { size: Letter landscape; margin: 1cm; }
            body  { font-family: "DejaVu Sans", sans-serif; font-size: 7px; margin:0; padding:0; color:#222; }
            .hdr-logo  { float:left; }
            .hdr-logo img { max-width:160px; max-height:55px; }
            .hdr-fecha { float:right; font-size:9px; color:#555; padding-top:8px; }
            .clearfix::after { content:""; display:table; clear:both; }
            h1 { text-align:center; font-size:12px; text-transform:uppercase;
                background:#191970; color:#fff; padding:5px 0; margin:10px 0 12px 0; }
            table { width:100%; max-width:100%; border-collapse:collapse; table-layout:fixed; font-size:7px; }
            th { background:#191970; color:#fff; padding:4px 2px; border:1px solid #0f0f50; white-space:normal; word-wrap:break-word; overflow-wrap:break-word; }
            td { padding:3px 2px; border:1px solid #ccc; text-align:center; vertical-align:middle; white-space:normal; word-wrap:break-word; overflow-wrap:break-word; word-break:break-all; }
            .footer { text-align:center; font-size:7px; margin-top:10px; color:#999;
                    border-top:1px solid #ddd; padding-top:5px; }
        ';

        // ── Encabezado HTML de tabla (reutilizable por chunk) ───────────────────
        $theadHtml = '<thead><tr>';
        $totalColumnas = count($encabezados);
        $anchoColumna = $totalColumnas > 0 ? (100 / $totalColumnas) : 100;
        
        foreach ($encabezados as $col) {
            $theadHtml .= '<th style="width: ' . $anchoColumna . '%;">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $col)), ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $theadHtml .= '</tr></thead>';

        // ── Generar PDFs por chunk ──────────────────────────────────────────────
        $chunkSize  = 500;
        $chunkIndex = 0;
        $totalFilas = 0;
        $chunkFiles = [];
        $buffer     = [];

        // Usamos cursor() para no cargar todo en memoria
        foreach ($query->cursor() as $fila) {
            $buffer[] = $fila;
            $totalFilas++;

            if (count($buffer) >= $chunkSize) {
                $chunkFiles[] = $this->_generarChunkPdf(
                    $buffer, $chunkIndex, $css, $theadHtml,
                    $logoPath, $fecha, $nombreReporte, $tmpDir
                );
                $chunkIndex++;
                $buffer = [];
                gc_collect_cycles(); // liberar memoria entre chunks
            }
        }

        // Último chunk (filas sobrantes)
        if (!empty($buffer)) {
            $chunkFiles[] = $this->_generarChunkPdf(
                $buffer, $chunkIndex, $css, $theadHtml,
                $logoPath, $fecha, $nombreReporte, $tmpDir
            );
            $buffer = [];
            gc_collect_cycles();
        }

        if (empty($chunkFiles)) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se generó ningún chunk de PDF.');
        }

        // ── Fusionar todos los chunks en un solo PDF ────────────────────────────
        $outputPath = $tmpDir . '/merged_' . Str::uuid() . '.pdf';
        $this->_mergeChunkPdfs($chunkFiles, $outputPath);

        // Limpiar temporales de chunk
        foreach ($chunkFiles as $file) {
            @unlink($file);
        }

        // Cookie spinner
        $token = $request->query('downloadToken', '');
        if ($token) {
            \Cookie::queue(\Cookie::make($token, 'done', 1, '/', null, false, false));
        }

        $nombreArchivo = Str::slug($nombreReporte) . '.pdf';

        return response()->download($outputPath, $nombreArchivo)->deleteFileAfterSend(true);
    }

    // ── Helper privado: genera el PDF de un chunk y lo guarda en disco ──────────
    private function _generarChunkPdf(
        array  $filas, int $chunkIndex, string $css, string $theadHtml,
        string $logoPath, string $fecha, string $nombreReporte, string $tmpDir
    ): string {
        $html  = '<!DOCTYPE html><html lang="es"><head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $html .= '<style>' . $css . '</style></head><body>';

        if ($chunkIndex === 0) {
            $html .= '<div class="clearfix">';
            $html .= '<div class="hdr-logo"><img src="' . $logoPath . '" alt="Logo"></div>';
            $html .= '<div class="hdr-fecha">M&eacute;rida, Yucat&aacute;n a ' . $fecha . '</div></div>';
            $html .= '<h1>' . htmlspecialchars($nombreReporte, ENT_QUOTES, 'UTF-8') . '</h1>';
        }

        $html .= '<table>' . $theadHtml . '<tbody>';

        $esPar = false;
        foreach ($filas as $fila) {
            // Inyectamos el color desde PHP (Nivel 1 de Optimización)
            $bgColor = $esPar ? '#f2f4ff' : '#ffffff';
            $html .= '<tr style="background-color: ' . $bgColor . ';">';
            
            foreach ((array) $fila as $valor) {
                if ($valor !== null && trim((string) $valor) !== '') {
                    $celda = htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
                } else {
                    $celda = 'N/A'; // Manejo de celdas vacías
                }
                $html .= '<td>' . $celda . '</td>';
            }
            $html .= '</tr>';
            $esPar = !$esPar;
        }

        $html .= '</tbody></table></body></html>';

        $filePath = $tmpDir . '/chunk_' . $chunkIndex . '_' . Str::uuid() . '.pdf';

        Pdf::loadHtml($html)
            ->setPaper('letter', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'DejaVu Sans',
                'chroot'               => public_path(),
                'dpi'                  => 96,
            ])->save($filePath);

        return $filePath;
    }

    // ── iniciarExportPdf (async: guarda en disco, frontend hace polling) ─────
    public function iniciarExportPdf(Request $request, $id)
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

        $encabezados   = array_keys((array) $primeraFila);
        $nombreReporte = $reportes->title;
        $fecha         = now()->format('d/m/Y');
        $logoPath      = 'file://' . public_path('img/logo.png');
        $tmpDir        = storage_path('app/tmp_pdf_chunks');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // Generamos el Token y la ruta final
        $token = Str::uuid()->toString();
        $outputPath = storage_path('app/exports/' . $token . '.pdf');

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // CSS Optimizado (Sin las reglas de nth-child para mayor velocidad)
        $css = '
            @page { size: Letter landscape; margin: 1cm; }
            body  { font-family: "DejaVu Sans", sans-serif; font-size: 7px; margin:0; padding:0; color:#222; }
            .hdr-logo  { float:left; }
            .hdr-logo img { max-width:150px; max-height:50px; }
            .hdr-fecha { float:right; font-size:9px; color:#555; padding-top:10px; }
            .clearfix::after { content:""; display:table; clear:both; }
            h1 { text-align:center; font-size:12px; text-transform:uppercase; background:#191970; color:#fff; padding:5px 0; margin:5px 0 10px 0; }
            table { width:100%; max-width:100%; border-collapse:collapse; table-layout:fixed; font-size:7px; }
            th { background:#191970; color:#fff; padding:4px 2px; border:1px solid #0f0f50; white-space:normal; word-wrap:break-word; overflow-wrap:break-word; }
            td { padding:3px 2px; border:1px solid #ccc; text-align:center; vertical-align:middle; white-space:normal; word-wrap:break-word; overflow-wrap:break-word; word-break:break-all; }
            .footer { text-align:center; font-size:8px; margin-top:10px; color:#999; border-top:1px solid #ddd; padding-top:5px; }
        ';

        $theadHtml = '<thead><tr>';
        $totalColumnas = count($encabezados);
        $anchoColumna = $totalColumnas > 0 ? (100 / $totalColumnas) : 100;
        
        foreach ($encabezados as $col) {
            $theadHtml .= '<th style="width: ' . $anchoColumna . '%;">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $col)), ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $theadHtml .= '</tr></thead>';

        // Subimos el Chunk a 1000 para que termine más rápido
        $chunkSize  = 1000;
        $chunkIndex = 0;
        $chunkFiles = [];
        $buffer     = [];

        foreach ($query->cursor() as $fila) {
            $buffer[] = $fila;

            if (count($buffer) >= $chunkSize) {
                $chunkFiles[] = $this->_generarChunkPdf(
                    $buffer, $chunkIndex, $css, $theadHtml,
                    $logoPath, $fecha, $nombreReporte, $tmpDir
                );
                $chunkIndex++;
                $buffer = [];
                gc_collect_cycles();
            }
        }

        if (!empty($buffer)) {
            $chunkFiles[] = $this->_generarChunkPdf(
                $buffer, $chunkIndex, $css, $theadHtml,
                $logoPath, $fecha, $nombreReporte, $tmpDir
            );
            $buffer = [];
            gc_collect_cycles();
        }

        if (empty($chunkFiles)) {
            return response()->json(['error' => 'No se generó ningún chunk de PDF.'], 500);
        }

        // Fusiona y guarda el PDF final con el nombre del Token
        $this->_mergeChunkPdfs($chunkFiles, $outputPath);

        foreach ($chunkFiles as $file) {
            @unlink($file);
        }

        // Guarda el archivo Meta con el nombre real para la descarga
        Storage::put('exports/' . $token . '.meta', Str::slug($nombreReporte) . '.pdf');

        return response()->json(['token' => $token]);
    }

    private function _mergeChunkPdfs(array $chunkFiles, string $outputPath): void
    {
        // Opción A: FPDI (recomendado)
        // composer require setasign/fpdi
        if (class_exists(\setasign\Fpdi\Tcpdf\Fpdi::class)) {
            $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
            $fpdi->SetAutoPageBreak(false);

            foreach ($chunkFiles as $file) {
                $pageCount = $fpdi->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($tpl);

                    $fpdi->AddPage(
                        $size['width'] > $size['height'] ? 'L' : 'P',
                        [$size['width'], $size['height']]
                    );
                    $fpdi->useTemplate($tpl);
                }
            }

            $fpdi->Output($outputPath, 'F');
            return;
        }

        // Opción B: FPDI sin TCPDF
        if (class_exists(\setasign\Fpdi\Fpdi::class)) {
            $fpdi = new \setasign\Fpdi\Fpdi();
            $fpdi->SetAutoPageBreak(false);

            foreach ($chunkFiles as $file) {
                $pageCount = $fpdi->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl  = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($tpl);

                    $fpdi->AddPage(
                        $size['width'] > $size['height'] ? 'L' : 'P',
                        [$size['width'], $size['height']]
                    );
                    $fpdi->useTemplate($tpl);
                }
            }

            file_put_contents($outputPath, $fpdi->Output('', 'S'));
            return;
        }

        // Opción C: fallback — concatenar PDFs como strings
        // No es un PDF válido en todos los lectores, pero funciona en Chrome/Acrobat.
        Log::warning('exportPdf: FPDI no encontrado, usando concatenación raw de PDFs.');
        $merged = '';
        foreach ($chunkFiles as $file) {
            $merged .= file_get_contents($file);
        }
        file_put_contents($outputPath, $merged);
    }

    // ── exportExcel ────────────────────────────────────────────────────────────
    public function exportExcel(Request $request, $id)
    {
        set_time_limit(600);
        ini_set('memory_limit', '1024M');

        $reportes = $this->reportesRepository->find($id);
        if (!$reportes) {
            return redirect()->route('reportes.index')->with('error', 'Reporte no encontrado');
        }

        $metadata           = json_decode($reportes->query_details, true);
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

        // FromQuery requiere ORDER BY para chunks deterministas.
        // Si la query es UNION (from contiene 'union_result'), las tablas originales
        // ya no existen en el contexto — hay que ordenar por alias, no por tabla.columna.
        if (empty($query->orders)) {
            $esUnion = str_contains((string) ($query->from ?? ''), 'union_result');

            if ($esUnion) {
                // Ordenar por el primer alias del resultado (siempre existe)
                $query->orderByRaw('`' . $columnas[0] . '`');
            } else {
                $tablaPrincipal = $metadata['tabla_principal'];
                $columnasPK     = Schema::getColumnListing($tablaPrincipal);
                $pkFallback     = !empty($columnasPK) ? "{$tablaPrincipal}.{$columnasPK[0]}" : '1';
                $query->orderBy($pkFallback);
            }
        }

        $nombreArchivo = Str::slug($reportes->title) . '.xlsx';

        $token = $request->query('downloadToken', '');
        if ($token) {
            \Cookie::queue(\Cookie::make($token, 'done', 1, '/', null, false, false));
        }

        return Excel::download(new ReporteExport($query, $columnas), $nombreArchivo);
    }

    // ── iniciarExportExcel (async: guarda en disco, frontend hace polling) ─────
    public function iniciarExportExcel(Request $request, $id)
    {
        $reportes = $this->reportesRepository->find($id);
        if (!$reportes) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        $metadata           = json_decode($reportes->query_details, true);
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
            $esUnion = str_contains((string) ($query->from ?? ''), 'union_result');

            if ($esUnion) {
                $query->orderByRaw('`' . $columnas[0] . '`');
            } else {
                $tablaPrincipal = $metadata['tabla_principal'];
                $columnasPK     = Schema::getColumnListing($tablaPrincipal);
                $pkFallback     = !empty($columnasPK) ? "{$tablaPrincipal}.{$columnasPK[0]}" : '1';
                $query->orderBy($pkFallback);
            }
        }

        $token    = Str::uuid()->toString();
        $filename = 'exports/' . $token . '.xlsx';

        set_time_limit(600);
        ini_set('memory_limit', '1024M');

        Excel::store(new ReporteExport($query, $columnas), $filename);
        Storage::put('exports/' . $token . '.meta', Str::slug($reportes->title) . '.xlsx');

        return response()->json(['token' => $token]);
    }

    // ── statusExport ───────────────────────────────────────────────────────────
    public function statusExport(Request $request, $token)
    {
        if (!preg_match('/^[0-9a-f-]{36}$/', $token)) {
            return response()->json(['ready' => false]);
        }

        // Verifica si existe el Excel o el PDF
        $exists = Storage::exists('exports/' . $token . '.xlsx') || Storage::exists('exports/' . $token . '.pdf');
        
        return response()->json(['ready' => $exists]);
    }

    // ── descargarExport ────────────────────────────────────────────────────────
    public function descargarExport(Request $request, $token)
    {
        if (!preg_match('/^[0-9a-f-]{36}$/', $token)) {
            abort(404);
        }

        $pathExcel = storage_path('app/exports/' . $token . '.xlsx');
        $pathPdf   = storage_path('app/exports/' . $token . '.pdf');

        // Selecciona el archivo que exista
        $path = file_exists($pathExcel) ? $pathExcel : (file_exists($pathPdf) ? $pathPdf : null);

        if (!$path) {
            abort(404);
        }

        $meta = storage_path('app/exports/' . $token . '.meta');
        $nombre = file_exists($meta) ? trim(file_get_contents($meta)) : 'reporte.file';

        return response()->download($path, $nombre)->deleteFileAfterSend(true);
    }

    // ── preview ────────────────────────────────────────────────────────────────
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