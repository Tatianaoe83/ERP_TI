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

        $currentDate = Carbon::now()->format('Y') + 1;
        $years = range($currentDate, $currentDate + 8);

        return view('facturas.index', compact('meses', 'years'));
    }

    public function indexVista(Request $request)
    {
        $gerenciaID = $request->input('gerenci_id');
        $mes = $request->input('mes');
        $año = $request->input('año');

        if ($request->ajax()) {
            if ($gerenciaID && $mes) {
                $query = DB::table('cortes')
                    ->where('GerenciaID', $gerenciaID)
                    ->where('Mes', $mes)
                    ->where('Año', $año);

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
