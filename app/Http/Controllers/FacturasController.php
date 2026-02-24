<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFacturasRequest;
use App\Http\Requests\UpdateFacturasRequest;
use App\Repositories\FacturasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Gerencia;
use Carbon\Carbon;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Response;
use Yajra\DataTables\Facades\DataTables;

class FacturasController extends AppBaseController
{
    /** @var FacturasRepository $facturasRepository*/
    private $facturasRepository;

    public function __construct(FacturasRepository $facturasRepo)
    {
        $this->facturasRepository = $facturasRepo;

        $this->middleware('permission:facturas.view', ['only' => ['index']]);
        $this->middleware('permission:facturas.create', ['only' => ['create', 'store']]);
    }
    /**
     * Display a listing of the Facturas.
     * 
     * @return Response
     */
    public function index()
    {
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

        $currentDate = (int) Carbon::now()->format('Y');
        $years = range($currentDate, $currentDate + 8);

        // Gerencias que tienen al menos una solicitud_activo con factura y empleado con gerencia (solo estado = 1)
        $gerenciasConFacturas = Gerencia::query()
            ->where('estado', 1)
            ->whereIn('GerenciaID', function ($q) {
                $q->select('departamentos.GerenciaID')
                    ->from('solicitud_activos')
                    ->join('empleados', 'solicitud_activos.EmpleadoID', '=', 'empleados.EmpleadoID')
                    ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
                    ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
                    ->whereNotNull('solicitud_activos.FacturaPath')
                    ->where('solicitud_activos.FacturaPath', '!=', '')
                    ->whereNull('solicitud_activos.deleted_at');
            })
            ->orderBy('NombreGerencia')
            ->pluck('NombreGerencia', 'GerenciaID')
            ->toArray();

        $gerencia = ['' => 'Selecciona una opción'] + $gerenciasConFacturas;

        return view('facturas.index', compact('meses', 'years', 'gerencia'));
    }

    /**
     * Datos para la tabla de facturas: solicitud_activos con factura asignada a empleado con gerencia.
     */
    public function indexVista(Request $request)
    {
        $gerenciaID = $request->input('gerenci_id');
        $mes = $request->input('mes');
        $año = $request->input('año');

        if ($request->ajax()) {
            $query = DB::table('solicitud_activos')
                ->select([
                    'solicitud_activos.SolicitudActivoID',
                    'solicitud_activos.FacturaPath',
                    'solicitud_activos.FechaEntrega',
                    'solicitud_activos.CotizacionID',
                    'departamentos.GerenciaID',
                    'gerencia.NombreGerencia',
                    DB::raw('COALESCE(cotizaciones.Descripcion, cotizaciones.NombreEquipo, \'—\') as NombreInsumo'),
                    DB::raw('COALESCE(cotizaciones.Precio, 0) as Costo'),
                ])
                ->join('empleados', 'solicitud_activos.EmpleadoID', '=', 'empleados.EmpleadoID')
                ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
                ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
                ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
                ->join('cotizaciones', 'solicitud_activos.CotizacionID', '=', 'cotizaciones.CotizacionID')
                ->whereNotNull('solicitud_activos.FacturaPath')
                ->where('solicitud_activos.FacturaPath', '!=', '')
                ->whereNull('solicitud_activos.deleted_at');

            if ($gerenciaID) {
                $query->where('departamentos.GerenciaID', $gerenciaID);
            }
            if ($mes) {
                $mesesNum = [
                    'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6,
                    'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12,
                ];
                $numMes = $mesesNum[$mes] ?? null;
                if ($numMes) {
                    $query->whereMonth('solicitud_activos.FechaEntrega', $numMes);
                }
            }
            if ($año) {
                $query->whereYear('solicitud_activos.FechaEntrega', $año);
            }

            $query->orderBy('solicitud_activos.FechaEntrega', 'desc');

            return DataTables::of($query)
                ->addColumn('Mes', function ($row) {
                    if (empty($row->FechaEntrega)) {
                        return '—';
                    }
                    $fecha = \Carbon\Carbon::parse($row->FechaEntrega);
                    $meses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ];
                    return $meses[(int) $fecha->format('n')] ?? $row->FechaEntrega;
                })
                ->rawColumns(['Mes'])
                ->make(true);
        }

        return redirect()->route('facturas.index');
    }

    /**
     * Show the form for creating a new Facturas.
     *
     * @return Response
     */
    public function create()
    {
        return view('facturas.create');
    }

    /**
     * Store a newly created Facturas in storage.
     *
     * @param CreateFacturasRequest $request
     *
     * @return Response
     */
    public function store(CreateFacturasRequest $request)
    {
        $input = $request->all();

        $facturas = $this->facturasRepository->create($input);

        Flash::success('Facturas saved successfully.');

        return redirect(route('facturas.index'));
    }

    /**
     * Display the specified Facturas.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $facturas = $this->facturasRepository->find($id);

        if (empty($facturas)) {
            Flash::error('Facturas not found');

            return redirect(route('facturas.index'));
        }

        return view('facturas.show')->with('facturas', $facturas);
    }

    /**
     * Show the form for editing the specified Facturas.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $facturas = $this->facturasRepository->find($id);

        if (empty($facturas)) {
            Flash::error('Facturas not found');

            return redirect(route('facturas.index'));
        }

        return view('facturas.edit')->with('facturas', $facturas);
    }

    /**
     * Update the specified Facturas in storage.
     *
     * @param int $id
     * @param UpdateFacturasRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateFacturasRequest $request)
    {
        $facturas = $this->facturasRepository->find($id);

        if (empty($facturas)) {
            Flash::error('Facturas not found');

            return redirect(route('facturas.index'));
        }

        $facturas = $this->facturasRepository->update($request->all(), $id);

        Flash::success('Facturas updated successfully.');

        return redirect(route('facturas.index'));
    }

    /**
     * Remove the specified Facturas from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $facturas = $this->facturasRepository->find($id);

        if (empty($facturas)) {
            Flash::error('Facturas not found');

            return redirect(route('facturas.index'));
        }

        $this->facturasRepository->delete($id);

        Flash::success('Facturas deleted successfully.');

        return redirect(route('facturas.index'));
    }
}
