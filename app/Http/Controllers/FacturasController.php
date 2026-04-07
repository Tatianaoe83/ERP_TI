<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFacturasRequest;
use App\Http\Requests\UpdateFacturasRequest;
use App\Repositories\FacturasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Gerencia;
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

                [$best, $score] = $this->matchInsumo($descripcion, $catalogo, $emisorNombre);

                $conceptos[] = [
                    'nombre'       => $descripcion,
                    'costo'        => $valorUnit,
                    'importe'      => $importe,
                    'cantidad'     => $cantidad,
                    'insumoId'     => $best['id'] ?? null,
                    'insumoNombre' => $best['nombre'] ?? null,
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

            $catalogo  = $this->getCatalogoCortes();
            $emisorPdf = $data['emisor'] ?? '';

            $conceptosMapeados = [];
            foreach (($data['conceptos'] ?? []) as $c) {
                [$best, $score] = $this->matchInsumo($c['nombre'], $catalogo, $emisorPdf);

                $conceptosMapeados[] = [
                    'nombre'       => $c['nombre'],
                    'cantidad'     => 1,
                    'costo'        => $c['importe'],
                    'importe'      => $c['importe'],
                    'insumoId'     => $best['id'] ?? null,
                    'insumoNombre' => $best['nombre'] ?? null,
                ];
            }

            return response()->json([
                'emisor'    => $emisorPdf,
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
            'Nombre'             => 'required|string|max:300',
            'Costo'              => 'required|numeric|min:0',
            'Importe'            => 'nullable|numeric|min:0',
            'Mes'                => 'nullable|integer|min:1|max:12',
            'Anio'               => 'nullable|integer|min:2000|max:2099',
            'InsumoNombre'       => 'nullable|string|max:300',
            'UUID'               => 'nullable|string|max:36',
            'Emisor'             => 'nullable|string|max:300',
            'archivo_xml'        => 'nullable|file|max:5120',
            'archivo_pdf'        => 'nullable|file|mimes:pdf|max:10240',
            'GerenciaID'         => 'nullable|integer',
            'conceptos_insumos'  => 'nullable|string|max:10000',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $xmlRuta = null;
                $pdfRuta = null;

                if ($request->hasFile('archivo_xml') && $request->file('archivo_xml')->isValid()) {
                    $xmlRuta = $request->file('archivo_xml')->store('facturas/xml', 'public');
                }
                if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
                    $pdfRuta = $request->file('archivo_pdf')->store('facturas/pdf', 'public');
                }

                $uuid       = trim((string)($request->input('UUID', '')));
                $emisor     = trim((string)($request->input('Emisor', '')));
                $gerenciaId = $request->input('GerenciaID') ?: null;
                $mes        = $request->input('Mes') ?: null;
                $anio       = $request->input('Anio') ?: null;

                $parsedConceptos = [];

                if ($xmlRuta && $request->hasFile('archivo_xml')) {
                    $parsed = $this->parsearCfdi($request->file('archivo_xml')->getRealPath());
                    if (!empty($parsed['conceptos'])) {
                        $parsedConceptos = $parsed['conceptos'];
                        if (empty($uuid) && !empty($parsed['uuid']))     $uuid   = $parsed['uuid'];
                        if (empty($emisor) && !empty($parsed['emisor'])) $emisor = $parsed['emisor'];
                        if (empty($mes) && !empty($parsed['mes']))       $mes    = $parsed['mes'];
                        if (empty($anio) && !empty($parsed['anio']))     $anio   = $parsed['anio'];
                    }
                }

                if (empty($parsedConceptos) && $pdfRuta && $request->hasFile('archivo_pdf')) {
                    $parsed = $this->parsearPdfBasico($request->file('archivo_pdf')->getRealPath(), $emisor ?: 'Extranjero');
                    if (!empty($parsed['conceptos'])) {
                        $catalogo  = $this->getCatalogoCortes();
                        $emisorPdf = $parsed['emisor'] ?? $emisor;
                        foreach ($parsed['conceptos'] as $c) {
                            [$best, $score] = $this->matchInsumo($c['nombre'], $catalogo, $emisorPdf);
                            $parsedConceptos[] = [
                                'nombre'       => $c['nombre'],
                                'costo'        => $c['importe'] ?? 0,
                                'importe'      => $c['importe'] ?? 0,
                                'cantidad'     => 1,
                                'insumoId'     => $best['id'] ?? null,
                                'insumoNombre' => $best['nombre'] ?? null,
                            ];
                        }
                        if (empty($emisor) && !empty($parsed['emisor'])) $emisor = $parsed['emisor'];
                    }
                }

                $conceptosInsumosOverride = [];
                $ciRaw = $request->input('conceptos_insumos');
                if ($ciRaw) {
                    $decoded = is_string($ciRaw) ? json_decode($ciRaw, true) : (is_array($ciRaw) ? $ciRaw : []);
                    if (is_array($decoded)) $conceptosInsumosOverride = $decoded;
                }

                if ($uuid) {
                    DB::table('facturas')
                        ->where('UUID', $uuid)
                        ->whereNull('SolicitudID')
                        ->delete();
                }

                if (!empty($parsedConceptos)) {
                    foreach ($parsedConceptos as $cIdx => $concepto) {
                        $nombreConcepto = mb_substr(trim((string)($concepto['nombre'] ?? '')), 0, 300);
                        if ($nombreConcepto === '') continue;

                        $overrideRaw    = array_key_exists((string)$cIdx, $conceptosInsumosOverride)
                            ? $conceptosInsumosOverride[(string)$cIdx]
                            : '__not_set__';
                        $overrideIsNull = ($overrideRaw === null || $overrideRaw === '');
                        $overrideNombre = ($overrideRaw !== '__not_set__' && !$overrideIsNull) ? (string)$overrideRaw : null;
                        $hasOverride    = ($overrideRaw !== '__not_set__');

                        if ($hasOverride && $overrideIsNull) {
                            $insumoNombreConcepto = null;
                            $insumoID             = null;
                        } elseif ($hasOverride && $overrideNombre) {
                            $insumoNombreConcepto = $overrideNombre;
                            $insumoID = DB::table('cortes')
                                ->whereNull('deleted_at')
                                ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($insumoNombreConcepto))])
                                ->max('CortesID');
                        } elseif (!empty($concepto['insumoId'])) {
                            $insumoID             = (int)$concepto['insumoId'];
                            $insumoNombreConcepto = $concepto['insumoNombre'] ?? null;
                        } else {
                            $insumoNombreConcepto = $concepto['insumoNombre'] ?? null;
                            $insumoID = $insumoNombreConcepto
                                ? DB::table('cortes')
                                    ->whereNull('deleted_at')
                                    ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($insumoNombreConcepto))])
                                    ->max('CortesID')
                                : null;
                        }

                        if (!$hasOverride && !$insumoNombreConcepto) {
                            $insumoNombreConcepto = trim((string)($request->input('InsumoNombre', ''))) ?: null;
                            if ($insumoNombreConcepto && !$insumoID) {
                                $insumoID = DB::table('cortes')
                                    ->whereNull('deleted_at')
                                    ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($insumoNombreConcepto))])
                                    ->max('CortesID');
                            }
                        }

                        DB::table('facturas')->insert([
                            'SolicitudID'  => null,
                            'GerenciaID'   => $gerenciaId,
                            'Nombre'       => $nombreConcepto,
                            'Costo'        => is_numeric($concepto['costo'] ?? null) ? (float)$concepto['costo'] : 0,
                            'Importe'      => is_numeric($concepto['importe'] ?? null) ? (float)$concepto['importe'] : 0,
                            'Mes'          => $mes,
                            'Anio'         => $anio,
                            'InsumoNombre' => $insumoNombreConcepto,
                            'InsumoID'     => $insumoID ?: null,
                            'UUID'         => $uuid ?: null,
                            'Emisor'       => $emisor ?: null,
                            'ArchivoRuta'  => $xmlRuta,
                            'PdfRuta'      => $pdfRuta,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                    }
                } else {
                    $insumoNombre = trim((string)($request->input('InsumoNombre', '')));
                    $insumoID     = $insumoNombre
                        ? DB::table('cortes')
                            ->whereNull('deleted_at')
                            ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower($insumoNombre)])
                            ->max('CortesID')
                        : null;

                    DB::table('facturas')->insert([
                        'SolicitudID'  => null,
                        'GerenciaID'   => $gerenciaId,
                        'Nombre'       => $request->input('Nombre'),
                        'Costo'        => $request->input('Costo'),
                        'Importe'      => $request->input('Importe') ?: null,
                        'Mes'          => $mes,
                        'Anio'         => $anio,
                        'InsumoNombre' => $insumoNombre ?: null,
                        'InsumoID'     => $insumoID ?: null,
                        'UUID'         => $uuid ?: null,
                        'Emisor'       => $emisor ?: null,
                        'ArchivoRuta'  => $xmlRuta,
                        'PdfRuta'      => $pdfRuta,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            });

            return response()->json(['message' => 'Factura guardada correctamente.'], 201);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    public function reemplazarArchivo(Request $request, $id): JsonResponse
    {
        $request->validate([
            'archivo_xml'   => 'nullable|file|max:10240',
            'archivo_pdf'   => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $factura = DB::table('facturas')->where('FacturasID', $id)->first();
        if (!$factura) return response()->json(['message' => 'Factura no encontrada'], 404);

        try {
            DB::transaction(function () use ($request, $id, $factura) {
                
                $insumoElegido = $request->input('insumo_nombre');
                $insumoElegido = trim((string)$insumoElegido) === '' ? null : $insumoElegido;
                
                $finalInsumoID = null;
                if ($insumoElegido) {
                    $finalInsumoID = DB::table('cortes')->whereNull('deleted_at')
                        ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($insumoElegido))])
                        ->max('CortesID');
                }

                if (!$request->hasFile('archivo_xml') && !$request->hasFile('archivo_pdf')) {
                    DB::table('facturas')->where('FacturasID', $id)->update([
                        'InsumoNombre' => $insumoElegido,
                        'InsumoID'     => $finalInsumoID,
                        'updated_at'   => now()
                    ]);
                    return;
                }

                $baseDir = $factura->SolicitudID ? "solicitudes/{$factura->SolicitudID}/facturas" : "facturas/extras";
                $parsedData = null;
                $rutaXml = $factura->ArchivoRuta;
                $rutaPdf = $factura->PdfRuta;

                if ($request->hasFile('archivo_xml')) {
                    if ($rutaXml && Storage::disk('public')->exists($rutaXml)) Storage::disk('public')->delete($rutaXml);
                    $rutaXml = $request->file('archivo_xml')->store($baseDir . '/xml', 'public');
                    $parsedData = $this->parsearCfdi($request->file('archivo_xml')->getRealPath());
                }

                if ($request->hasFile('archivo_pdf')) {
                    if ($rutaPdf && Storage::disk('public')->exists($rutaPdf)) Storage::disk('public')->delete($rutaPdf);
                    $rutaPdf = $request->file('archivo_pdf')->store($baseDir . '/pdf', 'public');
                    if (!$parsedData) {
                        $parsedPdf = $this->parsearPdfBasico($request->file('archivo_pdf')->getRealPath(), $factura->Emisor ?? 'Extranjero');
                        if (!$parsedPdf['error']) $parsedData = $parsedPdf;
                    }
                }

                $conceptosNuevos = $parsedData['conceptos'] ?? [];
                $uuidViejo = trim((string)($factura->UUID ?? ''));
                $uuidNuevo = ($parsedData['uuid'] ?? '') ?: $uuidViejo;

                if (!empty($conceptosNuevos)) {
                    
                    $queryDel = DB::table('facturas')
                        ->when($factura->SolicitudID, fn($q) => $q->where('SolicitudID', $factura->SolicitudID), fn($q) => $q->whereNull('SolicitudID'));
                        
                    if ($uuidViejo) {
                        $queryDel->where('UUID', $uuidViejo);
                    } elseif ($factura->ArchivoRuta) {
                        $queryDel->where('ArchivoRuta', $factura->ArchivoRuta);
                    } elseif ($factura->PdfRuta) {
                        $queryDel->where('PdfRuta', $factura->PdfRuta);
                    } else {
                        $queryDel->where('FacturasID', $id);
                    }
                    $queryDel->delete();

                    $conceptosInsumosOverride = [];
                    $ciRaw = $request->input('conceptos_insumos');
                    if ($ciRaw) {
                        $decoded = is_string($ciRaw) ? json_decode($ciRaw, true) : (is_array($ciRaw) ? $ciRaw : []);
                        if (is_array($decoded)) $conceptosInsumosOverride = $decoded;
                    }

                    foreach ($conceptosNuevos as $cIdx => $concepto) {
                        if ($request->has('conceptos_insumos')) {
                            $overrideRaw = array_key_exists((string)$cIdx, $conceptosInsumosOverride) ? $conceptosInsumosOverride[(string)$cIdx] : '__not_set__';
                            if ($overrideRaw !== '__not_set__' && ($overrideRaw === null || $overrideRaw === '')) {
                                $nombreInsumoFila = null;
                                $idInsumoFila = null;
                            } elseif ($overrideRaw !== '__not_set__') {
                                $nombreInsumoFila = (string)$overrideRaw;
                                $idInsumoFila = DB::table('cortes')->whereNull('deleted_at')
                                    ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($nombreInsumoFila))])
                                    ->max('CortesID');
                            } else {
                                $nombreInsumoFila = $concepto['insumoNombre'] ?? null;
                                $idInsumoFila = $concepto['insumoId'] ?? null;
                            }
                        } else if ($request->has('insumo_nombre')) {
                            $nombreInsumoFila = $insumoElegido;
                            $idInsumoFila = $finalInsumoID;
                        } else {
                            $nombreInsumoFila = $concepto['insumoNombre'] ?? null;
                            $idInsumoFila = $concepto['insumoId'] ?? null;
                        }

                        DB::table('facturas')->insert([
                            'SolicitudID'  => $factura->SolicitudID,
                            'GerenciaID'   => $factura->GerenciaID,
                            'Nombre'       => mb_substr(trim((string)($concepto['nombre'] ?? '')), 0, 300),
                            'Costo'        => is_numeric($concepto['costo'] ?? null) ? (float)$concepto['costo'] : 0,
                            'Importe'      => is_numeric($concepto['importe'] ?? null) ? (float)$concepto['importe'] : 0,
                            'Mes'          => $parsedData['mes'] ?? $factura->Mes,
                            'Anio'         => $parsedData['anio'] ?? $factura->Anio,
                            'UUID'         => $uuidNuevo ?: null,
                            'Emisor'       => $parsedData['emisor'] ?? $factura->Emisor,
                            'InsumoNombre' => $nombreInsumoFila,
                            'InsumoID'     => $idInsumoFila,
                            'ArchivoRuta'  => $rutaXml,
                            'PdfRuta'      => $rutaPdf,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                    }
                } else {
                    $queryDelHermanas = DB::table('facturas')
                        ->where('FacturasID', '!=', $id)
                        ->when($factura->SolicitudID, fn($q) => $q->where('SolicitudID', $factura->SolicitudID), fn($q) => $q->whereNull('SolicitudID'));
                        
                    if ($uuidViejo) {
                        $queryDelHermanas->where('UUID', $uuidViejo)->delete();
                    } elseif ($factura->ArchivoRuta) {
                        $queryDelHermanas->where('ArchivoRuta', $factura->ArchivoRuta)->delete();
                    } elseif ($factura->PdfRuta) {
                        $queryDelHermanas->where('PdfRuta', $factura->PdfRuta)->delete();
                    }

                    $updateData = [
                        'updated_at'  => now(),
                        'ArchivoRuta' => $rutaXml,
                        'PdfRuta'     => $rutaPdf
                    ];
                    if ($request->has('insumo_nombre')) {
                        $updateData['InsumoNombre'] = $insumoElegido;
                        $updateData['InsumoID']     = $finalInsumoID;
                    }
                    if ($parsedData) {
                        if (!empty($parsedData['total']))  { $updateData['Costo'] = $parsedData['total']; $updateData['Importe'] = $parsedData['total']; }
                        if (!empty($parsedData['uuid']))   $updateData['UUID'] = $parsedData['uuid'];
                        if (!empty($parsedData['emisor'])) $updateData['Emisor'] = $parsedData['emisor'];
                        if (!empty($parsedData['mes']))    $updateData['Mes'] = $parsedData['mes'];
                        if (!empty($parsedData['anio']))   $updateData['Anio'] = $parsedData['anio'];
                    }
                    DB::table('facturas')->where('FacturasID', $id)->update($updateData);
                }

                if ($factura->SolicitudID && ($rutaXml || $rutaPdf)) {
                    DB::table('solicitud_activos')
                        ->where('SolicitudID', $factura->SolicitudID)
                        ->where(fn($q) => $q->where('FacturaPath', $factura->ArchivoRuta ?? '')
                                            ->orWhere('FacturaPath', $factura->PdfRuta ?? ''))
                        ->update(['FacturaPath' => $rutaXml ?? $rutaPdf, 'updated_at' => now()]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Archivo actualizado correctamente.']);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al reemplazar: ' . $e->getMessage()], 500);
        }
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
            ? DB::table('cortes')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($nombre))])
                ->max('CortesID')
            : null;

        $updated = DB::table('facturas')->where('FacturasID', $id)->whereNull('deleted_at')
            ->update(['InsumoNombre' => $nombre, 'InsumoID' => $insumoID ?: null, 'updated_at' => now()]);

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

        $rows        = $query->orderBy('s.SolicitudID', 'desc')->orderBy('f.Mes', 'asc')->cursor();
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
                'f.InsumoNombre',
                DB::raw('SUM(f.Costo) as CostoTotalFacturado'),
                DB::raw('MAX(COALESCE(s.GerenciaID, f.GerenciaID)) as GerenciaID')
            ])
            ->groupBy('f.InsumoNombre')
            ->get()
            ->keyBy('InsumoNombre');

        $presupuestos = DB::table('cortes')
            ->whereNull('deleted_at')
            ->whereIn('NombreInsumo', $insumos)
            ->when($mes,        fn($q) => $q->where('Mes', $mes))
            ->when($anio,       fn($q) => $q->where('Anio', $anio))
            ->when($gerenciaId, fn($q) => $q->where('GerenciaID', $gerenciaId))
            ->select([
                'NombreInsumo',
                DB::raw('SUM(COALESCE(CostoTotal, Costo, 0)) as TotalPresupuesto')
            ])
            ->groupBy('NombreInsumo')
            ->pluck('TotalPresupuesto', 'NombreInsumo');

        $resultado = $insumos->map(function ($nombre) use ($todasFacturas, $presupuestos, $gerenciaMap) {
            $factData  = $todasFacturas->get($nombre);
            $totalFact = $factData ? (float)$factData->CostoTotalFacturado : 0;
            
            $gId       = $factData ? $factData->GerenciaID : null;
            $gNombre   = $gId ? optional($gerenciaMap->get($gId))->NombreGerencia : null;

            $totalPresu = (float)($presupuestos->get($nombre, 0));

            $desvMonto = ($totalPresu > 0 && $totalFact > 0) ? round($totalFact - $totalPresu, 2) : null;
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

    private function getCatalogoCortes(): array
    {
        return DB::table('cortes')
            ->whereNull('deleted_at')
            ->select(DB::raw('MAX(CortesID) as id, NombreInsumo as nombre'))
            ->groupBy('NombreInsumo')
            ->get()
            ->map(fn($c) => ['id'=>(int)$c->id,'nombre'=>(string)$c->nombre,'norm'=>$this->normalizeText((string)$c->nombre)])
            ->toArray();
    }

    private function matchInsumo(string $descripcion, array $catalogo, string $emisor = ''): array
    {
        $dn = $this->normalizeText($descripcion);
        $en = $this->normalizeText($emisor);
        if ($dn === '' && $en === '') return [null, 0];

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            if ($cat['norm'] === $dn) return [$cat, 100];
        }

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            if (str_contains($dn, $cat['norm']) || str_contains($cat['norm'], $dn)) return [$cat, 95];
        }

        $palabrasDesc   = array_filter(explode(' ', $dn), fn($w) => mb_strlen($w) > 2);
        $palabrasEmisor = array_filter(explode(' ', $en), fn($w) => mb_strlen($w) > 2);
        $todasPalabras  = array_unique(array_merge($palabrasDesc, $palabrasEmisor));

        $mejorScore = 0;
        $mejorCat   = null;

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            $palabrasCat = array_filter(explode(' ', $cat['norm']), fn($w) => mb_strlen($w) > 2);
            if (empty($palabrasCat)) continue;

            $hits = 0;
            foreach ($palabrasCat as $pw) {
                foreach ($todasPalabras as $dw) {
                    if ($pw === $dw || str_contains($dw, $pw) || str_contains($pw, $dw)) {
                        $hits++;
                        break;
                    }
                }
            }

            if ($hits === 0) continue;

            $pct = ($hits / count($palabrasCat)) * 100;
            if ($pct > $mejorScore) { $mejorScore = $pct; $mejorCat = $cat; }
        }

        if ($mejorScore >= 50) return [$mejorCat, $mejorScore];
        return [null, 0];
    }

    private function parsearCfdi(string $ruta): array
    {
        $contenido = file_get_contents($ruta);
        if ($contenido === false) throw new \Exception('No se pudo leer el archivo XML.');

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $e = array_map(fn($err) => $err->message, libxml_get_errors()); 
            libxml_clear_errors();
            throw new \Exception('XML inválido: ' . implode(', ', $e));
        }

        $ns      = $xml->getDocNamespaces(true);
        $cfdiUri = $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
        $xml->registerXPathNamespace('cfdi', $cfdiUri);
        $xml->registerXPathNamespace('tfd',  'http://www.sat.gob.mx/TimbreFiscalDigital');

        $attrs   = $xml->attributes();
        $version = (string)($attrs['Version'] ?? $attrs['version'] ?? '3.3');
        $fecha   = (string)($attrs['Fecha'] ?? '');
        $moneda  = (string)($attrs['Moneda'] ?? 'MXN');
        $total   = (string)($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0');

        $emisorNode   = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor') ?: $xml->xpath('//cfdi:Emisor');
        $emisorNombre = $emisorNode ? (string)$emisorNode[0]['Nombre'] : '';

        $uuid = '';
        $timbre = $xml->xpath('//tfd:TimbreFiscalDigital') ?: [];
        if (!empty($timbre)) $uuid = strtoupper(trim((string)($timbre[0]['UUID'] ?? '')));

        $mes = null; $anio = null;
        if ($fecha) {
            try { 
                $cf = Carbon::parse($fecha); 
                $mes = (int)$cf->format('n'); 
                $anio = (int)$cf->format('Y'); 
            } catch (\Throwable) {}
        }

        $conceptoNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: $xml->xpath('//cfdi:Concepto') ?: [];
        $catalogo      = $this->getCatalogoCortes();
        $conceptos     = [];

        foreach ($conceptoNodes as $nodo) {
            $ca          = $nodo->attributes();
            $descripcion = (string)($ca['Descripcion'] ?? '');
            $valorUnit   = (string)($ca['ValorUnitario'] ?? '0');
            $importe     = (string)($ca['Importe']       ?? '0');
            $cantidad    = (string)($ca['Cantidad']      ?? '1');

            [$best, $score] = $this->matchInsumo($descripcion, $catalogo, $emisorNombre);

            $conceptos[] = [
                'nombre'       => $descripcion,
                'costo'        => $valorUnit,
                'importe'      => $importe,
                'cantidad'     => $cantidad,
                'insumoId'     => $best['id'] ?? null,
                'insumoNombre' => $best['nombre'] ?? null,
            ];
        }

        return [
            'version'   => $version,
            'uuid'      => $uuid,
            'emisor'    => $emisorNombre,
            'fecha'     => $fecha,
            'mes'       => $mes,
            'anio'      => $anio,
            'total'     => $total,
            'moneda'    => $moneda,
            'conceptos' => $conceptos
        ];
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

    private function normalizeText(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'],['a','e','i','o','u','a','e','i','o','u','n'], $t);
        $t = preg_replace('/[^a-z0-9\s]/', '', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}