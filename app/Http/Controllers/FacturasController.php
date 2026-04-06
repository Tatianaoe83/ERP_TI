<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFacturasRequest;
use App\Http\Requests\UpdateFacturasRequest;
use App\Repositories\FacturasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Gerencia;
use App\Models\Insumos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Response;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;

class FacturasController extends AppBaseController
{
    private $facturasRepository;

    public function __construct(FacturasRepository $facturasRepo)
    {
        $this->facturasRepository = $facturasRepo;
        $this->middleware('permission:facturas.view',   ['only' => ['index']]);
        $this->middleware('permission:facturas.create', ['only' => ['create', 'store']]);
    }

    public function index()
    {
        $meses = [
            1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril',
            5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto',
            9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre',
        ];

        $currentYear = (int) Carbon::now()->format('Y');
        $years       = range($currentYear - 2, $currentYear + 3);

        $gerenciasConFacturas = Gerencia::query()
            ->where('estado', 1)
            ->whereIn('GerenciaID', function ($q) {
                $q->select('solicitudes.GerenciaID')
                    ->from('facturas')
                    ->join('solicitudes', 'facturas.SolicitudID', '=', 'solicitudes.SolicitudID')
                    ->whereNull('facturas.deleted_at')
                    ->whereNotNull('solicitudes.GerenciaID');
            })
            ->orderBy('NombreGerencia')
            ->pluck('NombreGerencia', 'GerenciaID')
            ->toArray();

        $gerencia = ['' => 'Selecciona una opción'] + $gerenciasConFacturas;

        $gerenciasModal = DB::table('gerencia')
            ->where('estado', 1)
            ->whereNull('deleted_at')
            ->select('GerenciaID as id', 'NombreGerencia as nombre')
            ->orderBy('NombreGerencia')
            ->get();

        $insumosModal = DB::table('cortes')
            ->whereNull('deleted_at')
            ->distinct()
            ->select('NombreInsumo as nombre')
            ->orderBy('NombreInsumo')
            ->get()
            ->map(fn($c) => (object)['id' => $c->nombre, 'nombre' => $c->nombre]);

        return view('facturas.index', compact('meses', 'years', 'gerencia', 'gerenciasModal', 'insumosModal') + [
            'gerencias' => $gerenciasModal,
            'insumos'   => $insumosModal,
        ]);
    }

    public function indexVista(Request $request)
    {
        if (!$request->ajax()) return redirect()->route('facturas.index');

        $gerenciaID = $request->input('gerenci_id');
        $mesParam   = $request->input('mes');
        $año        = $request->input('año');

        $mesesNum = [
            'Enero'=>1,'Febrero'=>2,'Marzo'=>3,'Abril'=>4,'Mayo'=>5,'Junio'=>6,
            'Julio'=>7,'Agosto'=>8,'Septiembre'=>9,'Octubre'=>10,'Noviembre'=>11,'Diciembre'=>12,
        ];
        $numMes = is_numeric($mesParam) ? (int)$mesParam : ($mesesNum[$mesParam] ?? null);

        $query = DB::table('facturas')
            ->select([
                'facturas.FacturasID','facturas.Nombre','facturas.SolicitudID',
                'facturas.Costo','facturas.Mes','facturas.Anio',
                'facturas.PdfRuta','facturas.ArchivoRuta','facturas.InsumoNombre',
                DB::raw('COALESCE(gerencia.NombreGerencia, g_directa.NombreGerencia) as NombreGerencia'),
            ])
            ->leftJoin('solicitudes', 'facturas.SolicitudID', '=', 'solicitudes.SolicitudID')
            ->leftJoin('gerencia', 'solicitudes.GerenciaID', '=', 'gerencia.GerenciaID')
            ->leftJoin('gerencia as g_directa', 'facturas.GerenciaID', '=', 'g_directa.GerenciaID')
            ->whereNull('facturas.deleted_at');

        if ($gerenciaID) {
            $query->where(fn($q) => $q->where('solicitudes.GerenciaID', $gerenciaID)
                ->orWhere('facturas.GerenciaID', $gerenciaID));
        }
        if ($numMes) $query->where('facturas.Mes', $numMes);
        if ($año)    $query->where('facturas.Anio', (int)$año);

        return DataTables::of($query->orderBy('facturas.created_at', 'desc'))->make(true);
    }

    public function parsearXml(Request $request): JsonResponse
    {
        $request->validate(['xml' => 'required|file|max:5120']);

        try {
            $contenido = file_get_contents($request->file('xml')->getRealPath());

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                $errores = array_map(fn($e) => $e->message, libxml_get_errors());
                libxml_clear_errors();
                return response()->json(['error' => 'XML inválido: ' . implode(', ', $errores)], 422);
            }

            $xml->registerXPathNamespace('cfdi',  'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('cfdi3', 'http://www.sat.gob.mx/cfd/3');
            $xml->registerXPathNamespace('tfd',   'http://www.sat.gob.mx/TimbreFiscalDigital');

            $attrs   = $xml->attributes();
            $version = (string)($attrs['Version'] ?? $attrs['version'] ?? '3.3');
            $nsCfdi  = str_starts_with($version, '4') ? 'cfdi' : 'cfdi3';
            $fecha   = (string)($attrs['Fecha']    ?? $attrs['fecha']    ?? '');
            $moneda  = (string)($attrs['Moneda']   ?? $attrs['moneda']   ?? 'MXN');
            $total   = (string)($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0');

            $emisorNombre = '';
            $emisorNodes  = $xml->xpath("//{$nsCfdi}:Emisor") ?: $xml->xpath('//cfdi:Emisor') ?: $xml->xpath('//cfdi3:Emisor');
            if (!empty($emisorNodes)) {
                $ea           = $emisorNodes[0]->attributes();
                $emisorNombre = (string)($ea['Nombre'] ?? $ea['nombre'] ?? '');
            }

            $uuid        = '';
            $timbreNodes = $xml->xpath('//tfd:TimbreFiscalDigital');
            if (!empty($timbreNodes)) {
                $ta   = $timbreNodes[0]->attributes();
                $uuid = strtoupper(trim((string)($ta['UUID'] ?? $ta['uuid'] ?? '')));
            }

            $mes = null; $anio = null;
            if ($fecha) {
                try { $cf = Carbon::parse($fecha); $mes = (int)$cf->format('n'); $anio = (int)$cf->format('Y'); }
                catch (\Throwable) {}
            }

            $conceptoNodes = $xml->xpath("//{$nsCfdi}:Concepto")
                          ?: $xml->xpath('//cfdi:Concepto')
                          ?: $xml->xpath('//cfdi3:Concepto')
                          ?: [];

            $catalogo  = $this->getCatalogoCortes();
            $conceptos = [];

            foreach ($conceptoNodes as $nodo) {
                $ca          = $nodo->attributes();
                $descripcion = (string)($ca['Descripcion']   ?? $ca['descripcion']   ?? '');
                $valorUnit   = (string)($ca['ValorUnitario'] ?? $ca['valorUnitario'] ?? '0');
                $importe     = (string)($ca['Importe']       ?? $ca['importe']       ?? '0');
                $cantidad    = (string)($ca['Cantidad']      ?? $ca['cantidad']      ?? '1');

                $matchNombre = $this->matchInsumoNombre($descripcion, $catalogo);

                if (!$matchNombre && $emisorNombre) {
                    $normEmisor = $this->normalizeText($emisorNombre);
                    if (str_contains($normEmisor, 'starlink') || str_contains($normEmisor, 'space exploration')) {
                        $matchNombre = $this->matchPorKeyword('starlink', $catalogo)
                                    ?? $this->matchPorKeyword('internet satelital', $catalogo);
                    }
                }

                $conceptos[] = [
                    'nombre'       => $descripcion,
                    'costo'        => $valorUnit,
                    'importe'      => $importe,
                    'cantidad'     => $cantidad,
                    'insumoId'     => null,
                    'insumoNombre' => $matchNombre,
                ];
            }

            return response()->json([
                'ok'        => true,
                'version'   => $version,
                'uuid'      => $uuid,
                'emisor'    => $emisorNombre,
                'fecha'     => $fecha,
                'mes'       => $mes,
                'anio'      => $anio,
                'total'     => $total,
                'moneda'    => $moneda,
                'conceptos' => $conceptos,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error procesando XML: ' . $e->getMessage()], 500);
        }
    }

    public function previsualizarPdf(Request $request): JsonResponse
    {
        $request->validate(['pdf' => 'required|file|mimes:pdf|max:10240']);

        try {
            $data = $this->leerPdfExtranjero($request->file('pdf')->getRealPath(), 'Proveedor Extranjero');

            if (!empty($data['error'])) {
                return response()->json(['error' => 'No se pudieron extraer datos del PDF.'], 422);
            }

            $catalogo      = $this->getCatalogoCortes();
            $normEmisor    = $this->normalizeText($data['emisor'] ?? '');
            $esStarlink    = str_contains($normEmisor, 'starlink') || str_contains($normEmisor, 'space exploration');
            $matchStarlink = $esStarlink
                ? ($this->matchPorKeyword('starlink', $catalogo) ?? $this->matchPorKeyword('internet satelital', $catalogo))
                : null;

            $conceptosMapeados = [];
            foreach (($data['conceptos'] ?? []) as $c) {
                $conceptosMapeados[] = [
                    'nombre'       => $c['nombre'],
                    'cantidad'     => 1,
                    'costo'        => $c['importe'],
                    'importe'      => $c['importe'],
                    'insumoId'     => null,
                    'insumoNombre' => $matchStarlink ?? $this->matchInsumoNombre($c['nombre'], $catalogo),
                ];
            }

            return response()->json([
                'emisor'    => $data['emisor'] ?? '',
                'uuid'      => null,
                'mes'       => (int)now()->format('n'),
                'anio'      => (int)now()->format('Y'),
                'total'     => $data['total'] ?? 0,
                'moneda'    => 'MXN',
                'conceptos' => $conceptosMapeados,
                'es_pdf'    => true,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function storeDirecta(Request $request): JsonResponse
    {
        $request->validate([
            'Nombre'       => 'required|string|max:300',
            'Costo'        => 'required|numeric|min:0',
            'Importe'      => 'nullable|numeric|min:0',
            'Mes'          => 'nullable|integer|min:1|max:12',
            'Anio'         => 'nullable|integer|min:2000|max:2099',
            'InsumoNombre' => 'nullable|string|max:300',
            'UUID'         => 'nullable|string|max:36',
            'Emisor'       => 'nullable|string|max:300',
            'archivo_xml'  => 'nullable|file|max:5120',
            'archivo_pdf'  => 'nullable|file|mimes:pdf|max:10240',
            'GerenciaID'   => 'nullable|integer',
        ]);

        $xmlRuta = null;
        $pdfRuta = null;

        if ($request->hasFile('archivo_xml') && $request->file('archivo_xml')->isValid()) {
            $xmlRuta = $request->file('archivo_xml')->store('facturas/xml', 'public');
        }
        if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
            $pdfRuta = $request->file('archivo_pdf')->store('facturas/pdf', 'public');
        }

        $insumoNombre = trim((string)($request->input('InsumoNombre', '')));
        $insumoID     = $insumoNombre
            ? DB::table('insumos')->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower($insumoNombre)])
                ->value('ID')
            : null;

        DB::table('facturas')->insert([
            'SolicitudID'  => null,
            'GerenciaID'   => $request->input('GerenciaID') ?: null,
            'Nombre'       => $request->input('Nombre'),
            'Costo'        => $request->input('Costo'),
            'Importe'      => $request->input('Importe')   ?: null,
            'Mes'          => $request->input('Mes')       ?: null,
            'Anio'         => $request->input('Anio')      ?: null,
            'InsumoNombre' => $insumoNombre                ?: null,
            'InsumoID'     => $insumoID,
            'UUID'         => $request->input('UUID')      ?: null,
            'Emisor'       => $request->input('Emisor')    ?: null,
            'ArchivoRuta'  => $xmlRuta,
            'PdfRuta'      => $pdfRuta,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json(['message' => 'Factura guardada correctamente.'], 201);
    }

    public function reemplazarArchivo(Request $request, $id): JsonResponse
    {
        $request->validate([
            'archivo_xml' => 'nullable|file|max:10240',
            'archivo_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if (!$request->hasFile('archivo_xml') && !$request->hasFile('archivo_pdf')) {
            return response()->json(['message' => 'Debes subir al menos un archivo (XML o PDF).'], 422);
        }

        $factura = DB::table('facturas')->where('FacturasID', $id)->whereNull('deleted_at')->first();
        if (!$factura) return response()->json(['message' => 'Factura no encontrada'], 404);

        $baseDir    = $factura->SolicitudID ? "solicitudes/{$factura->SolicitudID}/facturas" : "facturas/extras";
        $updateData = ['updated_at' => now()];
        $parsedData = null;
        $rutaXml    = null;
        $rutaPdf    = null;

        if ($request->hasFile('archivo_xml')) {
            if (!empty($factura->ArchivoRuta) && Storage::disk('public')->exists($factura->ArchivoRuta)) {
                Storage::disk('public')->delete($factura->ArchivoRuta);
            }
            $rutaXml                   = $request->file('archivo_xml')->store($baseDir . '/xml', 'public');
            $updateData['ArchivoRuta'] = $rutaXml;
            $parsedData                = $this->parsearCfdiBasico($request->file('archivo_xml')->getRealPath());
        }

        if ($request->hasFile('archivo_pdf')) {
            if (!empty($factura->PdfRuta) && Storage::disk('public')->exists($factura->PdfRuta)) {
                Storage::disk('public')->delete($factura->PdfRuta);
            }
            $rutaPdf                = $request->file('archivo_pdf')->store($baseDir . '/pdf', 'public');
            $updateData['PdfRuta'] = $rutaPdf;
            if (!$parsedData && !$request->hasFile('archivo_xml')) {
                $parsedData = $this->parsearPdfBasico(
                    $request->file('archivo_pdf')->getRealPath(),
                    $factura->Emisor ?? 'Extranjero'
                );
            }
        }

        if ($parsedData && empty($parsedData['error'])) {
            if (!empty($parsedData['total']))  { $updateData['Costo'] = $parsedData['total']; $updateData['Importe'] = $parsedData['total']; }
            if (!empty($parsedData['uuid']))   $updateData['UUID']   = $parsedData['uuid'];
            if (!empty($parsedData['emisor'])) $updateData['Emisor'] = $parsedData['emisor'];
            if (!empty($parsedData['mes']))    $updateData['Mes']    = $parsedData['mes'];
            if (!empty($parsedData['anio']))   $updateData['Anio']   = $parsedData['anio'];
        }

        DB::table('facturas')->where('FacturasID', $id)->update($updateData);

        if ($factura->SolicitudID && ($rutaXml || $rutaPdf)) {
            DB::table('solicitud_activos')
                ->where('SolicitudID', $factura->SolicitudID)
                ->where(fn($q) => $q->where('FacturaPath', $factura->ArchivoRuta ?? '')
                    ->orWhere('FacturaPath', $factura->PdfRuta ?? ''))
                ->update(['FacturaPath' => $rutaXml ?? $rutaPdf, 'updated_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Archivo actualizado correctamente.', 'parsed' => $parsedData]);
    }

    public function getInsumosPorGerencia(Request $request): JsonResponse
    {
        $solicitudID = $request->input('solicitudID');
        if (!$solicitudID) return response()->json(['data' => []]);

        $gerenciaID = DB::table('solicitudes')->where('SolicitudID', $solicitudID)->value('GerenciaID');
        if (!$gerenciaID) return response()->json(['data' => []]);

        return response()->json(['data' => DB::table('cortes')
            ->where('GerenciaID', $gerenciaID)->whereNull('deleted_at')
            ->distinct()->orderBy('NombreInsumo')->pluck('NombreInsumo')]);
    }

    public function actualizarInsumo(Request $request, $id): JsonResponse
    {
        $request->validate(['InsumoNombre' => ['nullable', 'string', 'max:150']]);

        $nombre   = $request->input('InsumoNombre');
        $insumoID = $nombre
            ? DB::table('insumos')->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($nombre))])->value('ID')
            : null;

        $updated = DB::table('facturas')->where('FacturasID', $id)->whereNull('deleted_at')
            ->update(['InsumoNombre' => $nombre, 'InsumoID' => $insumoID, 'updated_at' => now()]);

        return $updated
            ? response()->json(['message' => 'Insumo actualizado'])
            : response()->json(['message' => 'Factura no encontrada'], 404);
    }

    public function historial(Request $request): JsonResponse
    {
        $gerenciaID = $request->input('gerenci_id');

        $query = DB::table('facturas as f')
            ->select([
                's.SolicitudID','s.Motivo','s.Estatus','s.Requerimientos','s.Presupuesto',
                's.created_at as solicitud_fecha','g.NombreGerencia','g.GerenciaID',
                'f.FacturasID','f.Nombre as FacturaNombre','f.Costo','f.Importe',
                'f.Mes','f.Anio','f.PdfRuta','f.Emisor','f.UUID','f.InsumoNombre',
                'c.Costo as CostoMensual','c.CostoTotal as CostoAnual',
            ])
            ->join('solicitudes as s', 'f.SolicitudID', '=', 's.SolicitudID')
            ->join('gerencia as g', 's.GerenciaID', '=', 'g.GerenciaID')
            ->leftJoin(DB::raw('(SELECT NombreInsumo, MIN(Costo) as Costo, MIN(CostoTotal) as CostoTotal FROM cortes WHERE deleted_at IS NULL GROUP BY NombreInsumo) as c'),
                fn($j) => $j->on(
                    DB::raw('LOWER(TRIM(f.InsumoNombre)) COLLATE utf8mb4_unicode_ci'), '=',
                    DB::raw('LOWER(TRIM(c.NombreInsumo)) COLLATE utf8mb4_unicode_ci')
                ))
            ->whereNull('f.deleted_at')->whereNull('s.deleted_at');

        if ($gerenciaID) $query->where('s.GerenciaID', $gerenciaID);

        $rows        = $query->orderBy('s.SolicitudID', 'desc')->orderBy('f.Mes', 'asc')->get();
        $solicitudes = [];

        foreach ($rows as $row) {
            $sid = $row->SolicitudID;
            if (!isset($solicitudes[$sid])) {
                $solicitudes[$sid] = [
                    'SolicitudID'=>$sid,'Motivo'=>$row->Motivo,'Estatus'=>$row->Estatus,
                    'Requerimientos'=>$row->Requerimientos,'Presupuesto'=>$row->Presupuesto,
                    'solicitud_fecha'=>$row->solicitud_fecha,'NombreGerencia'=>$row->NombreGerencia,
                    'GerenciaID'=>$row->GerenciaID,'facturas'=>[],'total_costo'=>0,
                ];
            }
            $solicitudes[$sid]['facturas'][] = [
                'FacturasID'=>$row->FacturasID,'FacturaNombre'=>$row->FacturaNombre,
                'Costo'=>$row->Costo,'Importe'=>$row->Importe,'Mes'=>$row->Mes,'Anio'=>$row->Anio,
                'PdfRuta'=>$row->PdfRuta,'Emisor'=>$row->Emisor,'UUID'=>$row->UUID,
                'InsumoNombre'=>$row->InsumoNombre,'CostoMensual'=>$row->CostoMensual,'CostoAnual'=>$row->CostoAnual,
            ];
            $solicitudes[$sid]['total_costo'] += (float)$row->Costo;
        }

        return response()->json(['data' => array_values($solicitudes)]);
    }

    public function comparativa(Request $request): JsonResponse
    {
        $gerenciaId = $request->input('gerencia_id');
        $mes        = $request->input('mes');
        $anio       = $request->input('anio');
        $insumo     = $request->input('insumo');

        $base = DB::table('facturas as f')
            ->leftJoin('solicitudes as s', 'f.SolicitudID', '=', 's.SolicitudID')
            ->whereNull('f.deleted_at')
            ->where(fn($q) => $q->whereNull('s.deleted_at')->orWhereNull('f.SolicitudID'))
            ->whereNotNull('f.InsumoNombre')->where('f.InsumoNombre', '<>', '');

        if ($gerenciaId) $base->where(fn($q) => $q->where('s.GerenciaID', $gerenciaId)->orWhere('f.GerenciaID', $gerenciaId));
        if ($mes)    $base->where('f.Mes', $mes);
        if ($anio)   $base->where('f.Anio', $anio);
        if ($insumo) $base->where('f.InsumoNombre', 'like', "%{$insumo}%");

        $insumos = (clone $base)->distinct()->pluck('f.InsumoNombre');
        if ($insumos->isEmpty()) return response()->json(['insumos' => [], 'meta' => ['total' => 0]]);

        $gerenciaMap = DB::table('gerencia')->select('GerenciaID','NombreGerencia')->get()->keyBy('GerenciaID');
        $todasFacturas = (clone $base)
            ->select([
                'f.FacturasID','f.Costo','f.Mes','f.Anio','f.InsumoNombre',
                'f.GerenciaID as FacturaGerenciaID','s.GerenciaID as SolicitudGerenciaID',
            ])
            ->orderBy('f.Anio')->orderBy('f.Mes')
            ->get()->groupBy('InsumoNombre');

        $presupuestos = DB::table('cortes')
            ->whereNull('deleted_at')
            ->whereIn('NombreInsumo', $insumos)
            ->when($mes,        fn($q) => $q->where('Mes', $mes))
            ->when($anio,       fn($q) => $q->where('Anio', $anio))
            ->when($gerenciaId, fn($q) => $q->where('GerenciaID', $gerenciaId))
            ->select(['NombreInsumo','Costo','CostoTotal','GerenciaID'])
            ->get()->groupBy('NombreInsumo');

        $resultado = $insumos->map(function ($nombre) use ($todasFacturas, $presupuestos, $gerenciaMap) {
            $facts  = $todasFacturas->get($nombre, collect());
            $presos = $presupuestos->get($nombre,  collect());

            $pf      = $facts->first();
            $gId     = $pf->SolicitudGerenciaID ?? $pf->FacturaGerenciaID;
            $gNombre = $gId ? optional($gerenciaMap->get($gId))->NombreGerencia : null;

            $totalFact  = $facts->sum(fn($f) => (float)($f->Costo ?? 0));
            $totalPresu = $presos->sum(fn($c) => (float)($c->CostoTotal ?? $c->Costo ?? 0));

            $desvMonto = ($totalPresu > 0 && $totalFact > 0) ? round($totalFact - $totalPresu, 2)                         : null;
            $desvPct   = ($totalPresu > 0 && $totalFact > 0) ? round((($totalFact - $totalPresu) / $totalPresu) * 100, 2) : null;

            return [
                'nombre'      => $nombre,
                'gerencia_id' => $gId,
                'gerencia'    => $gNombre,
                'metricas'    => [
                    'total_facturado'       => $totalFact,
                    'presupuesto_generales' => $totalPresu > 0 ? $totalPresu : null,
                    'desviacion_monto'      => $desvMonto,
                    'desviacion_pct'        => $desvPct,
                ],
            ];
        })
        ->filter(fn($i) => $i['metricas']['total_facturado'] > 0 || ($i['metricas']['presupuesto_generales'] ?? 0) > 0)
        ->values();

        return response()->json(['insumos' => $resultado, 'meta' => ['total' => $resultado->count()]]);
    }

    public function create()  { return view('facturas.create'); }

    public function store(CreateFacturasRequest $request)
    {
        $this->facturasRepository->create($request->all());
        Flash::success('Factura guardada correctamente.');
        return redirect(route('facturas.index'));
    }

    public function show($id)
    {
        $facturas = $this->facturasRepository->find($id);
        if (empty($facturas)) { Flash::error('Factura no encontrada'); return redirect(route('facturas.index')); }
        return view('facturas.show')->with('facturas', $facturas);
    }

    public function edit($id)
    {
        $facturas = $this->facturasRepository->find($id);
        if (empty($facturas)) { Flash::error('Factura no encontrada'); return redirect(route('facturas.index')); }
        return view('facturas.edit')->with('facturas', $facturas);
    }

    public function update($id, UpdateFacturasRequest $request)
    {
        $facturas = $this->facturasRepository->find($id);
        if (empty($facturas)) { Flash::error('Factura no encontrada'); return redirect(route('facturas.index')); }
        $this->facturasRepository->update($request->all(), $id);
        Flash::success('Factura actualizada correctamente.');
        return redirect(route('facturas.index'));
    }

    public function destroy($id)
    {
        $facturas = $this->facturasRepository->find($id);
        if (empty($facturas)) { Flash::error('Factura no encontrada'); return redirect(route('facturas.index')); }
        $this->facturasRepository->delete($id);
        Flash::success('Factura eliminada correctamente.');
        return redirect(route('facturas.index'));
    }


    private function parsearCfdiBasico(string $ruta): array
    {
        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string(file_get_contents($ruta), 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false) return ['error' => true];

            $xml->registerXPathNamespace('cfdi',  'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('cfdi3', 'http://www.sat.gob.mx/cfd/3');
            $xml->registerXPathNamespace('tfd',   'http://www.sat.gob.mx/TimbreFiscalDigital');

            $attrs  = $xml->attributes();
            $nsCfdi = str_starts_with((string)($attrs['Version'] ?? $attrs['version'] ?? '3.3'), '4') ? 'cfdi' : 'cfdi3';
            $total  = (float)((string)($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0'));
            $fecha  = (string)($attrs['Fecha'] ?? $attrs['fecha'] ?? '');

            $emisorNodes  = $xml->xpath("//{$nsCfdi}:Emisor") ?: $xml->xpath('//cfdi:Emisor') ?: $xml->xpath('//cfdi3:Emisor');
            $emisorNombre = !empty($emisorNodes) ? (string)($emisorNodes[0]->attributes()['Nombre'] ?? '') : '';

            $uuid = '';
            $timbre = $xml->xpath('//tfd:TimbreFiscalDigital');
            if (!empty($timbre)) { $ta = $timbre[0]->attributes(); $uuid = strtoupper(trim((string)($ta['UUID'] ?? ''))); }

            $mes = null; $anio = null;
            if ($fecha) {
                try { $cf = Carbon::parse($fecha); $mes = (int)$cf->format('n'); $anio = (int)$cf->format('Y'); }
                catch (\Throwable) {}
            }

            return ['error' => false, 'total' => $total, 'emisor' => $emisorNombre, 'uuid' => $uuid, 'mes' => $mes, 'anio' => $anio];
        } catch (\Throwable) { return ['error' => true]; }
    }

    private function parsearPdfBasico(string $ruta, string $proveedorFallback): array
    {
        try {
            $data = $this->leerPdfExtranjero($ruta, $proveedorFallback);
            return $data['error'] ? ['error' => true] : ['error' => false, 'total' => $data['total'], 'emisor' => $data['emisor']];
        } catch (\Throwable) { return ['error' => true]; }
    }

    private function leerPdfExtranjero(string $ruta, string $proveedorHint): array
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $text   = $parser->parseFile($ruta)->getText();
        } catch (\Throwable) {
            return ['error' => true, 'total' => 0, 'conceptos' => []];
        }

        $lower     = strtolower($text);
        $provLower = strtolower(trim($proveedorHint));

        $esStarlink  = str_contains($lower, 'starlink') || str_contains($lower, 'space exploration technologies') || str_contains($provLower, 'starlink');
        $esHostgator = str_contains($lower, 'hostgator') || str_contains($lower, 'newfold digital') || str_contains($provLower, 'hostgator');

        $emisor = $proveedorHint ?: 'Proveedor Extranjero';
        if ($esStarlink)      $emisor = 'STARLINK';
        elseif ($esHostgator) $emisor = 'HOSTGATOR';

        $ivaExplicito = (bool)preg_match('/\b(?:iva|i\.v\.a|vat|tax(?:es)?)\b[^\d\n]{0,30}[\d,]+\.\d{2}/i', $text);
        $subtotalDoc  = null; $totalDoc = null;

        if (preg_match('/(?:sub\s*total|subtotal|net\s*amount)[^\d\n]{0,20}([\d,]+\.\d{2})/i', $text, $m))
            $subtotalDoc = (float)str_replace(',', '', $m[1]);
        if (preg_match('/(?:^|\n)[^\n]{0,30}(?:total|amount\s*due|balance\s*due|invoice\s*total)[^\d\n]{0,20}([\d,]+\.\d{2})/im', $text, $m))
            $totalDoc = (float)str_replace(',', '', $m[1]);

        $ivaRatio = false;
        if ($subtotalDoc && $totalDoc && $totalDoc > $subtotalDoc) {
            $r = $totalDoc / $subtotalDoc;
            if ($r >= 1.14 && $r <= 1.18) $ivaRatio = true;
        }

        $tieneIva = ($ivaExplicito || $ivaRatio) && !$esHostgator;
        $total    = 0.0;

        if ($subtotalDoc !== null)  { $total = $subtotalDoc; }
        elseif ($totalDoc !== null) { $total = $tieneIva ? round($totalDoc / 1.16, 2) : $totalDoc; }
        else {
            preg_match_all('/\$\s*([\d,]+\.\d{2})/', $text, $m);
            if (!empty($m[1])) {
                $mayor = max(array_map(fn($n) => (float)str_replace(',', '', $n), $m[1]));
                $total = $tieneIva ? round($mayor / 1.16, 2) : $mayor;
            }
        }

        $conceptos = $this->extraerConceptosPdf($text, $tieneIva);
        if (empty($conceptos) && $total > 0) {
            $conceptos[] = ['nombre' => ucwords(strtolower($emisor)) ?: 'Servicio Extranjero', 'importe' => $total];
        }

        return ['error' => false, 'emisor' => $emisor, 'total' => $total, 'conceptos' => $conceptos];
    }

    private function extraerConceptosPdf(string $text, bool $quitarIva): array
    {
        $conceptos = [];
        $excluir   = [
            'subtotal','sub total','sub-total','net amount','total','amount due','balance due',
            'invoice total','iva','i.v.a','vat','tax','taxes','payment','due date','invoice date',
            'invoice','bill to','ship to','sold to','page','thank you','please','note','balance',
            'credit','discount','descuento','transaction','gateway','powered by',
            'description','descripcion','amount','qty','quantity','pdf generated','invoiced to',
        ];

        $patron = '/^[ \t]*([A-Za-zÁÉÍÓÚáéíóúñÑ][A-Za-zÁÉÍÓÚáéíóúñÑ0-9 ,\.\-\/\(\)\#\@\_\:]{2,149}?)[ \t]*(?:\.{2,}|_{2,}|-{2,})?[ \t]*\$?\s*([\d]{1,3}(?:,[\d]{3})*\.[\d]{2})(?!\d)/m';

        if (preg_match_all($patron, $text, $sets, PREG_SET_ORDER)) {
            foreach ($sets as $m) {
                $nombre  = trim(preg_replace('/\s+/', ' ', $m[1]));
                $importe = (float)str_replace(',', '', $m[2]);
                if (!$this->esConceptoValido($nombre, $importe, $excluir)) continue;
                if (array_filter($conceptos, fn($c) => strtolower($c['nombre']) === strtolower($nombre))) continue;
                $conceptos[] = ['nombre' => ucfirst($nombre), 'importe' => $quitarIva ? round($importe / 1.16, 2) : $importe];
            }
        }

        if (empty($conceptos)) {
            $lineas = preg_split('/\r?\n/', $text);
            for ($i = 0, $n = count($lineas) - 1; $i < $n; $i++) {
                if (!preg_match('/^\$?\s*([\d]{1,3}(?:,[\d]{3})*\.[\d]{2})$/', trim($lineas[$i + 1]), $pm)) continue;
                $importe = (float)str_replace(',', '', $pm[1]);
                $nombre  = trim(preg_replace('/\s+/', ' ', $lineas[$i]));
                if (!$this->esConceptoValido($nombre, $importe, $excluir)) continue;
                if (array_filter($conceptos, fn($c) => strtolower($c['nombre']) === strtolower($nombre))) continue;
                $conceptos[] = ['nombre' => ucfirst($nombre), 'importe' => $quitarIva ? round($importe / 1.16, 2) : $importe];
            }
        }

        return $conceptos;
    }

    private function esConceptoValido(string $nombre, float $importe, array $excluir): bool
    {
        if ($importe <= 0 || strlen($nombre) < 4) return false;
        if (!preg_match('/[A-Za-záéíóúñÁÉÍÓÚÑ]{2}/', $nombre)) return false;
        if (preg_match('/^\d/', $nombre)) return false;
        $lower = strtolower($nombre);
        foreach ($excluir as $kw) { if (str_contains($lower, $kw)) return false; }
        return true;
    }

    private function getCatalogoCortes(): array
    {
        return DB::table('cortes')->whereNull('deleted_at')->distinct()->orderBy('NombreInsumo')
            ->pluck('NombreInsumo')
            ->map(fn($n) => ['nombre' => (string)$n, 'norm' => $this->normalizeText((string)$n)])
            ->toArray();
    }

    private function matchInsumoNombre(string $descripcion, array $catalogo): ?string
    {
        $dn = $this->normalizeText($descripcion);
        if ($dn === '' || empty($catalogo)) return null;

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            if ($cat['norm'] === $dn || str_contains($dn, $cat['norm']) || str_contains($cat['norm'], $dn))
                return $cat['nombre'];
        }

        $best = null; $score = 0;
        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            similar_text($dn, $cat['norm'], $s);
            if ($s > $score) { $score = $s; $best = $cat['nombre']; }
        }

        return ($score >= 60) ? $best : null;
    }

    private function matchPorKeyword(string $keyword, array $catalogo): ?string
    {
        $kw = $this->normalizeText($keyword);
        foreach ($catalogo as $cat) { if (str_contains($cat['norm'], $kw)) return $cat['nombre']; }
        return null;
    }

    private function normalizeText(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'],['a','e','i','o','u','a','e','i','o','u','n'], $t);
        $t = preg_replace('/[^a-z0-9\s]/', '', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}