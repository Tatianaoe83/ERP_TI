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

    public function storeDirecta(Request $request)
    {
        $request->validate([
            'Nombre'       => 'required|string|max:300',
            'Costo'        => 'required|numeric|min:0',
            'Importe'      => 'nullable|numeric|min:0',
            'Mes'          => 'nullable|integer|min:1|max:12',
            'Anio'         => 'nullable|integer|min:2000|max:2099',
            'InsumoNombre' => 'nullable|string|max:150',
            'UUID'         => 'nullable|string|max:36',
            'Emisor'       => 'nullable|string|max:300',
            'archivo_xml'  => 'nullable|file|mimes:xml,text/xml|max:2048',
            'archivo_pdf'  => 'nullable|file|mimes:pdf|max:10240',
            'GerenciaID'   => 'nullable|integer', 
            'InsumoID'     => 'nullable|integer',
        ]);
    
        $xmlRuta = null;
        $pdfRuta = null;
    
        if ($request->hasFile('archivo_xml')) {
            $xmlRuta = $request->file('archivo_xml')->store('facturas/xml', 'public');
        }
    
        if ($request->hasFile('archivo_pdf')) {
            $pdfRuta = $request->file('archivo_pdf')->store('facturas/pdf', 'public');
        }
    
        $insumoID = $request->input('InsumoID');
        if (!$insumoID && $request->filled('InsumoNombre')) {
            $insumoID = DB::table('insumos')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($request->InsumoNombre))])
                ->value('ID');
        }
    
        DB::table('facturas')->insert([
            'SolicitudID'  => null,          
            'GerenciaID'   => $request->GerenciaID ?: null, 
            'Nombre'       => $request->Nombre,
            'Costo'        => $request->Costo,
            'Importe'      => $request->Importe  ?: null,
            'Mes'          => $request->Mes        ?: null,
            'Anio'         => $request->Anio       ?: null,
            'InsumoNombre' => $request->InsumoNombre ?: null,
            'InsumoID'     => $insumoID,                    
            'UUID'         => $request->UUID       ?: null,
            'Emisor'       => $request->Emisor     ?: null,
            'ArchivoRuta'  => $xmlRuta,
            'PdfRuta'      => $pdfRuta,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    
        return response()->json(['message' => 'Factura guardada correctamente.'], 201);
    }

    public function reemplazarArchivo(Request $request, $id)
    {
        $request->validate([
            'archivo_xml' => 'nullable|file|mimes:xml,text/xml|max:10240',
            'archivo_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if (!$request->hasFile('archivo_xml') && !$request->hasFile('archivo_pdf')) {
            return response()->json(['message' => 'Debes subir al menos un archivo (XML o PDF).'], 422);
        }

        $factura = DB::table('facturas')->where('FacturasID', $id)->whereNull('deleted_at')->first();

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        $baseDir    = $factura->SolicitudID ? "solicitudes/{$factura->SolicitudID}/facturas" : "facturas/extras";
        $updateData = ['updated_at' => now()];
        $parsedData = null;
        $rutaXml    = null;
        $rutaPdf    = null;

        // 1. SI SUBE XML
        if ($request->hasFile('archivo_xml')) {
            if (!empty($factura->ArchivoRuta) && Storage::disk('public')->exists($factura->ArchivoRuta)) {
                Storage::disk('public')->delete($factura->ArchivoRuta);
            }
            $rutaXml                   = $request->file('archivo_xml')->store($baseDir . '/xml', 'public');
            $updateData['ArchivoRuta'] = $rutaXml;
            $parsedData                = $this->extraerDatosFacturaXml($request->file('archivo_xml')->getRealPath());
        }

        // 2. SI SUBE PDF
        if ($request->hasFile('archivo_pdf')) {
            if (!empty($factura->PdfRuta) && Storage::disk('public')->exists($factura->PdfRuta)) {
                Storage::disk('public')->delete($factura->PdfRuta);
            }
            $rutaPdf                = $request->file('archivo_pdf')->store($baseDir . '/pdf', 'public');
            $updateData['PdfRuta'] = $rutaPdf;

            if (!$parsedData && !$request->hasFile('archivo_xml')) {
                $parsedData = $this->extraerDatosFacturaPdf($request->file('archivo_pdf')->getRealPath(), $factura->Emisor ?? 'Extranjero');
            }
        }

        // 3. ACTUALIZAR MONTOS SI EL PARSEO FUE EXITOSO
        if ($parsedData && empty($parsedData['error'])) {
            if (!empty($parsedData['total']))  { $updateData['Costo']   = $parsedData['total']; $updateData['Importe'] = $parsedData['total']; }
            if (!empty($parsedData['uuid']))    $updateData['UUID']   = $parsedData['uuid'];
            if (!empty($parsedData['emisor']))  $updateData['Emisor'] = $parsedData['emisor'];
            if (!empty($parsedData['mes']))     $updateData['Mes']    = $parsedData['mes'];
            if (!empty($parsedData['anio']))    $updateData['Anio']   = $parsedData['anio'];
        }

        DB::table('facturas')->where('FacturasID', $id)->update($updateData);

        // 4. SINCRONIZAR solicitud_activos SI LA FACTURA PERTENECE A UNA SOLICITUD
        if ($factura->SolicitudID && ($rutaXml || $rutaPdf)) {
            $nuevaRuta       = $rutaXml ?? $rutaPdf;
            $rutaAnteriorXml = $factura->ArchivoRuta ?? '';
            $rutaAnteriorPdf = $factura->PdfRuta     ?? '';

            DB::table('solicitud_activos')
                ->where('SolicitudID', $factura->SolicitudID)
                ->where(function ($q) use ($rutaAnteriorXml, $rutaAnteriorPdf) {
                    $q->where('FacturaPath', $rutaAnteriorXml)
                    ->orWhere('FacturaPath', $rutaAnteriorPdf);
                })
                ->update([
                    'FacturaPath' => $nuevaRuta,
                    'updated_at'  => now(),
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Archivo actualizado y montos recalculados correctamente.',
            'parsed'  => $parsedData,
        ]);
    }
    // ── Lógica idéntica a Solicitudes para extraer datos ──
    private function extraerDatosFacturaXml(string $rutaArchivo): array
    {
        try {
            $contenido = file_get_contents($rutaArchivo);
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) return ['error' => true];

            $xml->registerXPathNamespace('cfdi',  'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('cfdi3', 'http://www.sat.gob.mx/cfd/3');
            $xml->registerXPathNamespace('tfd',   'http://www.sat.gob.mx/TimbreFiscalDigital');

            $attrs  = $xml->attributes();
            $nsCfdi = str_starts_with((string) ($attrs['Version'] ?? $attrs['version'] ?? '3.3'), '4') ? 'cfdi' : 'cfdi3';

            $total = (string) ($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0'); // SIN IVA
            $fecha = (string) ($attrs['Fecha']  ?? $attrs['fecha']  ?? '');

            $emisorNodes = $xml->xpath("//{$nsCfdi}:Emisor") ?: $xml->xpath('//cfdi:Emisor') ?: $xml->xpath('//cfdi3:Emisor');
            $emisorNombre = !empty($emisorNodes) ? (string) ($emisorNodes[0]->attributes()['Nombre'] ?? '') : '';

            $uuid = '';
            $timbreNodes = $xml->xpath('//tfd:TimbreFiscalDigital');
            if (!empty($timbreNodes)) {
                $ta   = $timbreNodes[0]->attributes();
                $uuid = strtoupper(trim((string) ($ta['UUID'] ?? $ta['uuid'] ?? '')));
            }

            $mes = null; $anio = null;
            if ($fecha) {
                try {
                    $cf = Carbon::parse($fecha);
                    $mes = (int) $cf->format('n');
                    $anio = (int) $cf->format('Y');
                } catch (\Throwable $th) {}
            }

            return [
                'error'  => false,
                'total'  => (float) $total,
                'emisor' => $emisorNombre,
                'uuid'   => $uuid,
                'mes'    => $mes,
                'anio'   => $anio
            ];
        } catch (\Throwable $th) {
            return ['error' => true];
        }
    }

    private function extraerDatosFacturaPdf(string $rutaPdfRutaAbsoluta, string $proveedorFallback): array
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($rutaPdfRutaAbsoluta);
            $text   = strtolower($pdf->getText());

            $total = 0;
            if (str_contains($text, 'starlink')) {
                if (preg_match('/(?:subtotal|sub-total|net amount).*?\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2}))/i', $text, $matches)) {
                    $total = (float) str_replace(',', '', $matches[1]);
                } elseif (preg_match('/(?:total|amount due|balance).*?\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2}))/i', $text, $matches)) {
                    $total = round(((float) str_replace(',', '', $matches[1])) / 1.16, 2);
                } else {
                    if (preg_match_all('/\$([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2}))/', $text, $matches)) {
                        $numeros = array_map(fn($n) => (float) str_replace(',', '', $n), $matches[1]);
                        $total = round(max($numeros) / 1.16, 2);
                    }
                }
            } else {
                if (preg_match('/(?:subtotal|sub-total|net amount).*?\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2}))/i', $text, $matches)) {
                    $total = (float) str_replace(',', '', $matches[1]);
                } elseif (preg_match('/(?:total|amount due|invoice total).*?\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2}))/i', $text, $matches)) {
                    $total = round(((float) str_replace(',', '', $matches[1])) / 1.16, 2);
                }
            }

            return [
                'error'  => false,
                'total'  => $total,
                'emisor' => str_contains($text, 'starlink') ? 'STARLINK' : $proveedorFallback,
            ];
        } catch (\Throwable $e) {
            return ['error' => true];
        }
    }
    public function previsualizarPdf(Request $request)
    {
        $request->validate(['pdf' => 'required|file|mimes:pdf|max:10240']);
        
        $path = $request->file('pdf')->getRealPath();
        $data = $this->extraerDatosFacturaPdf($path, 'Proveedor Extranjero');
        
        return response()->json($data);
    }

    public function parsearXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml,text/xml|max:2048',
        ]);

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
            $version = (string) ($attrs['Version'] ?? $attrs['version'] ?? '3.3');
            $nsCfdi  = str_starts_with($version, '4') ? 'cfdi' : 'cfdi3';

            $fecha  = (string) ($attrs['Fecha']  ?? $attrs['fecha']  ?? '');
            $total  = (string) ($attrs['Total']  ?? $attrs['total']  ?? '0');
            $moneda = (string) ($attrs['Moneda'] ?? $attrs['moneda'] ?? 'MXN');

            $emisorNombre = '';
            $emisorNodes  = $xml->xpath("//{$nsCfdi}:Emisor") ?: $xml->xpath('//cfdi:Emisor') ?: $xml->xpath('//cfdi3:Emisor');
            if (!empty($emisorNodes)) {
                $ea           = $emisorNodes[0]->attributes();
                $emisorNombre = (string) ($ea['Nombre'] ?? $ea['nombre'] ?? '');
            }

            $uuid        = '';
            $timbreNodes = $xml->xpath('//tfd:TimbreFiscalDigital');
            if (!empty($timbreNodes)) {
                $ta   = $timbreNodes[0]->attributes();
                $uuid = (string) ($ta['UUID'] ?? $ta['uuid'] ?? '');
            }

            $mes  = null;
            $anio = null;
            if ($fecha) {
                try {
                    $cf   = Carbon::parse($fecha);
                    $mes  = (int) $cf->format('n');
                    $anio = (int) $cf->format('Y');
                } catch (\Throwable) {}
            }

            $conceptoNodes = $xml->xpath("//{$nsCfdi}:Concepto")
                ?: $xml->xpath('//cfdi:Concepto')
                ?: $xml->xpath('//cfdi3:Concepto')
                ?: [];

            $catalogoInsumos = DB::table('cortes')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('NombreInsumo')
            ->get(['NombreInsumo'])
            ->map(fn($i) => [
                'id'     => null,
                'nombre' => mb_strtolower(trim((string) $i->NombreInsumo)),
            ])
            ->toArray();

            $conceptos = [];
            foreach ($conceptoNodes as $concepto) {
                $cAttr       = $concepto->attributes();
                $descripcion = (string) ($cAttr['Descripcion'] ?? $cAttr['descripcion'] ?? '');
                $valorUnit   = (string) ($cAttr['ValorUnitario'] ?? $cAttr['valorUnitario'] ?? '0');
                $importe     = (string) ($cAttr['Importe']      ?? $cAttr['importe']      ?? '0');
                $cantidad    = (string) ($cAttr['Cantidad']     ?? $cAttr['cantidad']     ?? '1');

                $insumoId  = null;
                $descLower = mb_strtolower(trim($descripcion));
                foreach ($catalogoInsumos as $cat) {
                    if ($cat['nombre'] === $descLower) { $insumoId = $cat['id']; break; }
                    if ($cat['nombre'] !== '' && (str_contains($descLower, $cat['nombre']) || str_contains($cat['nombre'], $descLower))) {
                        $insumoId = $cat['id'];
                    }
                }

                $conceptos[] = [
                    'nombre'   => $descripcion,
                    'costo'    => $valorUnit,
                    'importe'  => $importe,
                    'cantidad' => $cantidad,
                    'insumoId' => $insumoId,
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

    public function index()
    {
        $meses = [
            1  => 'Enero',    2  => 'Febrero',   3  => 'Marzo',
            4  => 'Abril',    5  => 'Mayo',      6  => 'Junio',
            7  => 'Julio',    8  => 'Agosto',    9  => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
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
            ->map(function ($corte) {
                return (object) [
                    'id'     => $corte->nombre, 
                    'nombre' => $corte->nombre
                ];
            });

        return view('facturas.index', [
            'meses'     => $meses, 
            'years'     => $years, 
            'gerencia'  => $gerencia, 
            'gerencias' => $gerenciasModal,
            'insumos'   => $insumosModal,
        ]);
    }

    public function indexVista(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('facturas.index');
        }

        $gerenciaID = $request->input('gerenci_id');
        $mesParam   = $request->input('mes');
        $año        = $request->input('año');

        $mesesNum = [
            'Enero'=>1,'Febrero'=>2,'Marzo'=>3,'Abril'=>4,
            'Mayo'=>5,'Junio'=>6,'Julio'=>7,'Agosto'=>8,
            'Septiembre'=>9,'Octubre'=>10,'Noviembre'=>11,'Diciembre'=>12,
        ];
        $numMes = is_numeric($mesParam)
            ? (int) $mesParam
            : ($mesesNum[$mesParam] ?? null);

        $query = DB::table('facturas')
        ->select([
            'facturas.FacturasID',
            'facturas.Nombre',
            'facturas.SolicitudID',
            'facturas.Costo',
            'facturas.Mes',
            'facturas.Anio',
            'facturas.PdfRuta',
            'facturas.ArchivoRuta',
            'facturas.InsumoNombre',
            DB::raw('COALESCE(gerencia.NombreGerencia, g_directa.NombreGerencia) as NombreGerencia')
        ])
        ->leftJoin('solicitudes', 'facturas.SolicitudID', '=', 'solicitudes.SolicitudID')
        ->leftJoin('gerencia', 'solicitudes.GerenciaID', '=', 'gerencia.GerenciaID')
        ->leftJoin('gerencia as g_directa', 'facturas.GerenciaID', '=', 'g_directa.GerenciaID')
        ->whereNull('facturas.deleted_at');

        if ($gerenciaID) {
            $query->where(function($q) use ($gerenciaID) {
                $q->where('solicitudes.GerenciaID', $gerenciaID)
                  ->orWhere('facturas.GerenciaID', $gerenciaID);
            });
        }
        if ($numMes) {
            $query->where('facturas.Mes', $numMes);
        }
        if ($año) {
            $query->where('facturas.Anio', (int) $año);
        }

        $query->orderBy('facturas.created_at', 'desc');

        return DataTables::of($query)->make(true);
    }

    public function getInsumosPorGerencia(Request $request)
    {
        $solicitudID = $request->input('solicitudID');

        if (!$solicitudID) {
            return response()->json(['data' => []]);
        }

        $gerenciaID = DB::table('solicitudes')
            ->where('SolicitudID', $solicitudID)
            ->value('GerenciaID');

        if (!$gerenciaID) {
            return response()->json(['data' => []]);
        }

        $insumos = DB::table('cortes')
            ->where('GerenciaID', $gerenciaID)
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('NombreInsumo')
            ->pluck('NombreInsumo');

        return response()->json(['data' => $insumos]);
    }

    public function actualizarInsumo(Request $request, $id)
    {
        $request->validate([
            'InsumoNombre' => ['nullable', 'string', 'max:150'],
        ]);

        $insumoID = null;
        if ($request->input('InsumoNombre')) {
            $insumoID = DB::table('insumos')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(NombreInsumo)) = ?', [strtolower(trim($request->input('InsumoNombre')))])
                ->value('ID');
        }

        $updated = DB::table('facturas')
            ->where('FacturasID', $id)
            ->whereNull('deleted_at')
            ->update([
                'InsumoNombre' => $request->input('InsumoNombre'),
                'InsumoID'     => $insumoID,
                'updated_at'   => now(),
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        return response()->json(['message' => 'Insumo actualizado']);
    }

    public function create()
    {
        return view('facturas.create');
    }

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

    public function historial(Request $request)
    {
        $gerenciaID = $request->input('gerenci_id');

        $query = DB::table('facturas as f')
            ->select([
                's.SolicitudID',
                's.Motivo',
                's.Estatus',
                's.Requerimientos',
                's.Presupuesto',
                's.created_at as solicitud_fecha',
                'g.NombreGerencia',
                'g.GerenciaID',
                'f.FacturasID',
                'f.Nombre as FacturaNombre',
                'f.Costo',
                'f.Importe',
                'f.Mes',
                'f.Anio',
                'f.PdfRuta',
                'f.Emisor',
                'f.UUID',
                'f.InsumoNombre',
                'c.Costo as CostoMensual',
                'c.CostoTotal as CostoAnual',
            ])
            ->join('solicitudes as s', 'f.SolicitudID', '=', 's.SolicitudID')
            ->join('gerencia as g', 's.GerenciaID', '=', 'g.GerenciaID')
            ->leftJoin(DB::raw('(
                SELECT NombreInsumo, MIN(Costo) as Costo, MIN(CostoTotal) as CostoTotal 
                FROM cortes 
                WHERE deleted_at IS NULL 
                GROUP BY NombreInsumo
            ) as c'), function ($join) {
                $join->on(
                    DB::raw('LOWER(TRIM(f.InsumoNombre)) COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('LOWER(TRIM(c.NombreInsumo)) COLLATE utf8mb4_unicode_ci')
                );
            })
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at');

        if ($gerenciaID) {
            $query->where('s.GerenciaID', $gerenciaID);
        }

        $query->orderBy('s.SolicitudID', 'desc')
              ->orderBy('f.Mes', 'asc');

        $rows = $query->get();

        $solicitudes = [];
        foreach ($rows as $row) {
            $sid = $row->SolicitudID;
            if (!isset($solicitudes[$sid])) {
                $solicitudes[$sid] = [
                    'SolicitudID'    => $sid,
                    'Motivo'         => $row->Motivo,
                    'Estatus'        => $row->Estatus,
                    'Requerimientos' => $row->Requerimientos,
                    'Presupuesto'    => $row->Presupuesto,
                    'solicitud_fecha'=> $row->solicitud_fecha,
                    'NombreGerencia' => $row->NombreGerencia,
                    'GerenciaID'     => $row->GerenciaID,
                    'facturas'       => [],
                    'total_costo'    => 0,
                ];
            }
            $solicitudes[$sid]['facturas'][] = [
                'FacturasID'   => $row->FacturasID,
                'FacturaNombre'=> $row->FacturaNombre,
                'Costo'        => $row->Costo,
                'Importe'      => $row->Importe,
                'Mes'          => $row->Mes,
                'Anio'         => $row->Anio,
                'PdfRuta'      => $row->PdfRuta,
                'Emisor'       => $row->Emisor,
                'UUID'         => $row->UUID,
                'InsumoNombre' => $row->InsumoNombre,
                'CostoMensual' => $row->CostoMensual,
                'CostoAnual'   => $row->CostoAnual,
            ];
            $solicitudes[$sid]['total_costo'] += (float) $row->Costo;
        }

        return response()->json(['data' => array_values($solicitudes)]);
    }

    public function comparativa(Request $request): \Illuminate\Http\JsonResponse
    {
        $gerenciaId = $request->input('gerencia_id'); 
        $mes        = $request->input('mes'); 
        $anio       = $request->input('anio');          
        $insumo     = $request->input('insumo');     
        $estatus    = $request->input('estatus');      

        $queryBase = DB::table('facturas as f')
            ->leftJoin('solicitudes as s', 'f.SolicitudID', '=', 's.SolicitudID')
            ->whereNull('f.deleted_at')
            ->where(function($q) {
                $q->whereNull('s.deleted_at')
                  ->orWhereNull('f.SolicitudID');
            })
            ->whereNotNull('f.InsumoNombre')
            ->where('f.InsumoNombre', '<>', '');

        if ($gerenciaId) {
            $queryBase->where(function($q) use ($gerenciaId) {
                $q->where('s.GerenciaID', $gerenciaId)
                  ->orWhere('f.GerenciaID', $gerenciaId);
            });
        }
        if ($estatus) {
            $queryBase->where('s.Estatus', $estatus);
        }
        if ($mes)  $queryBase->where('f.Mes', $mes);
        if ($anio) $queryBase->where('f.Anio', $anio);
        if ($insumo) $queryBase->where('f.InsumoNombre', 'like', "%{$insumo}%");

        $insumos = (clone $queryBase)->distinct()->pluck('f.InsumoNombre');

        if ($insumos->isEmpty()) {
            return response()->json(['insumos' => [], 'meta' => ['total' => 0]]);
        }

        $gerenciaMap = DB::table('gerencia')
            ->select('GerenciaID', 'NombreGerencia')
            ->get()
            ->keyBy('GerenciaID');

        $todasFacturas = clone $queryBase;
        $todasFacturas = $todasFacturas->select([
                'f.FacturasID','f.Nombre','f.SolicitudID','f.Importe','f.Costo',
                'f.Mes','f.Anio','f.InsumoNombre','f.PdfRuta','f.Emisor',
                'f.GerenciaID as FacturaGerenciaID', 's.GerenciaID as SolicitudGerenciaID'
            ])
            ->orderBy('f.Anio')->orderBy('f.Mes')
            ->get()
            ->groupBy('InsumoNombre');

        $todasSolicitudesConFact = $todasFacturas->flatten()
            ->pluck('SolicitudID')->filter()->unique();

        $todasCotizaciones = $todasSolicitudesConFact->count()
            ? DB::table('cotizaciones')
                ->whereIn('SolicitudID', $todasSolicitudesConFact)
                ->select(['CotizacionID','SolicitudID','Proveedor','Descripcion',
                        'Precio','CostoEnvio','TiempoEntrega','Estatus','NumeroPropuesta','NumeroParte'])
                ->orderBy('NumeroPropuesta')
                ->get()
                ->groupBy('SolicitudID')
            : collect();

        $todosCortes = DB::table('cortes')
            ->whereNull('deleted_at')
            ->whereIn('NombreInsumo', $insumos)
            ->when($mes,        fn($q) => $q->where('Mes', $mes))
            ->when($anio,       fn($q) => $q->where('Anio', $anio))
            ->when($gerenciaId, fn($q) => $q->where('GerenciaID', $gerenciaId))
            ->select(['CortesID','NombreInsumo','Mes','Anio','Costo','CostoTotal','Margen','GerenciaID'])
            ->orderBy('Anio')->orderBy('Mes')
            ->get()
            ->groupBy('NombreInsumo');

        $resultado = $insumos->map(function ($nombreInsumo) use (
            $todasFacturas, $todasCotizaciones, $todosCortes, $gerenciaMap
        ) {
            $facturas = $todasFacturas->get($nombreInsumo, collect());
            $cortes   = $todosCortes->get($nombreInsumo,   collect());

            $solicitudIds = $facturas->pluck('SolicitudID')->filter()->unique();

            $cotizaciones = $solicitudIds->flatMap(fn($sid) =>
                $todasCotizaciones->get($sid, collect())
            )->values();

            $primeraFactura = $facturas->first();
            $gId = $primeraFactura->SolicitudGerenciaID ?? $primeraFactura->FacturaGerenciaID;
            $gNombre = $gId ? optional($gerenciaMap->get($gId))->NombreGerencia : null;

            $totalFacturado = $facturas->sum(fn($f) => (float)($f->Costo ?? 0));
            $totalCortes    = $cortes->sum(fn($c)   => (float)($c->CostoTotal ?? $c->Costo ?? 0));

            $seleccionada = $cotizaciones->first(
                fn($c) => strtolower($c->Estatus ?? '') === 'seleccionada'
            );
            $cotSelTotal  = $seleccionada
                ? (float)($seleccionada->Precio ?? 0) + (float)($seleccionada->CostoEnvio ?? 0)
                : null;

            $allTotals = $cotizaciones->map(
                fn($c) => (float)($c->Precio ?? 0) + (float)($c->CostoEnvio ?? 0)
            );
            $mejorCot = $allTotals->count() ? $allTotals->min() : null;
            $peorCot  = $allTotals->count() ? $allTotals->max() : null;

            $desvCotFactMonto = ($cotSelTotal !== null && $totalFacturado > 0)
                ? round($totalFacturado - $cotSelTotal, 2) : null;
            $desvCotFactPct   = ($cotSelTotal > 0 && $totalFacturado > 0)
                ? round((($totalFacturado - $cotSelTotal) / $cotSelTotal) * 100, 2) : null;

            $desvCortFactMonto = ($totalCortes > 0 && $totalFacturado > 0)
                ? round($totalFacturado - $totalCortes, 2) : null;
            $desvCortFactPct   = ($totalCortes > 0 && $totalFacturado > 0)
                ? round((($totalFacturado - $totalCortes) / $totalCortes) * 100, 2) : null;

            $ahorro = ($peorCot !== null && $cotSelTotal !== null)
                ? round($peorCot - $cotSelTotal, 2) : null;

            return [
                'nombre'       => $nombreInsumo,
                'gerencia_id'  => $gId,
                'gerencia'     => $gNombre,
                'metricas'     => [
                    'mejor_cotizacion'            => $mejorCot,
                    'cotizacion_seleccionada'     => $cotSelTotal,  
                    'total_facturado'             => $totalFacturado,
                    'total_cortes'                => $totalCortes,
                    'desviacion_cot_fact_monto'   => $desvCotFactMonto,
                    'desviacion_cot_fact_pct'     => $desvCotFactPct,
                    'desviacion_corte_fact_monto' => $desvCortFactMonto,
                    'desviacion_corte_fact_pct'   => $desvCortFactPct,
                    'ahorro_vs_peor_cotizacion'   => $ahorro,
                ],
            ];
        })->filter(fn($i) => $i['metricas']['cotizacion_seleccionada'] || $i['metricas']['total_facturado'])
        ->values();

        return response()->json([
            'insumos' => $resultado,
            'meta'    => ['total' => $resultado->count()],
        ]);
    }
}