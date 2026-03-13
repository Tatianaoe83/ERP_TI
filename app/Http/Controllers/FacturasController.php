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
use Response;
use Yajra\DataTables\Facades\DataTables;

class FacturasController extends AppBaseController
{
    /** @var FacturasRepository */
    private $facturasRepository;

    public function __construct(FacturasRepository $facturasRepo)
    {
        $this->facturasRepository = $facturasRepo;

        $this->middleware('permission:facturas.view',   ['only' => ['index']]);
        $this->middleware('permission:facturas.create', ['only' => ['create', 'store']]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Parseo de XML CFDI
    // ══════════════════════════════════════════════════════════════════════════

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

            // Mes como número 1-12
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

            $catalogoInsumos = Insumos::query()
                ->whereNull('deleted_at')
                ->get(['ID', 'NombreInsumo'])
                ->map(fn($i) => [
                    'id'     => (int) $i->ID,
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

    // ══════════════════════════════════════════════════════════════════════════
    // index — vista principal
    // ══════════════════════════════════════════════════════════════════════════

    public function index()
    {
        $meses = [
            1  => 'Enero',    2  => 'Febrero',   3  => 'Marzo',
            4  => 'Abril',    5  => 'Mayo',       6  => 'Junio',
            7  => 'Julio',    8  => 'Agosto',     9  => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $currentYear = (int) Carbon::now()->format('Y');
        $years       = range($currentYear - 2, $currentYear + 3);

        // Solo gerencias que tienen facturas registradas
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

        return view('facturas.index', compact('meses', 'years', 'gerencia'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // indexVista — DataTables AJAX
    // Query: facturas → solicitudes → gerencia → insumos
    // Columnas devueltas:
    //   Nombre, SolicitudID, NombreGerencia, Costo, Mes (número), Anio,
    //   NombreInsumo, PdfRuta
    // ══════════════════════════════════════════════════════════════════════════

    public function indexVista(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('facturas.index');
        }

        $gerenciaID = $request->input('gerenci_id');
        $mesParam   = $request->input('mes');    // puede llegar como número o nombre
        $año        = $request->input('año');

        // Convertir mes a número si llega como nombre
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
                'gerencia.NombreGerencia',
                'insumos.NombreInsumo',
            ])
            ->join('solicitudes', 'facturas.SolicitudID', '=', 'solicitudes.SolicitudID')
            ->join('gerencia',    'solicitudes.GerenciaID', '=', 'gerencia.GerenciaID')
            ->leftJoin('insumos', 'facturas.InsumoID', '=', 'insumos.ID')
            ->whereNull('facturas.deleted_at');

        if ($gerenciaID) {
            $query->where('solicitudes.GerenciaID', $gerenciaID);
        }

        if ($numMes) {
            $query->where('facturas.Mes', $numMes);
        }

        if ($año) {
            $query->where('facturas.Anio', (int) $año);
        }

        $query->orderBy('facturas.created_at', 'desc');

        $mesesNombres = [
            1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
            5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
            9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre',
        ];

        return DataTables::of($query)
            ->addColumn('MesNombre', function ($row) use ($mesesNombres) {
                return $mesesNombres[(int) $row->Mes] ?? '—';
            })
            ->rawColumns(['MesNombre'])
            ->make(true);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CRUD original
    // ══════════════════════════════════════════════════════════════════════════

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
}