<?php

namespace App\Http\Controllers;

use App\DataTables\PuestosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatePuestosRequest;
use App\Http\Requests\UpdatePuestosRequest;
use App\Repositories\PuestosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Puestos;
use Yajra\DataTables\DataTables;
use DB;

class PuestosController extends AppBaseController
{
    /** @var PuestosRepository $puestosRepository*/
    private $puestosRepository;

    public function __construct(PuestosRepository $puestosRepo)
    {
        $this->puestosRepository = $puestosRepo;
        $this->middleware('permission:ver-puestos|crear-puestos|editar-puestos|borrar-puestos')->only('index');
        $this->middleware('permission:crear-puestos', ['only' => ['create','store']]);
        $this->middleware('permission:editar-puestos', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-puestos', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Puestos.
     *
     * @param PuestosDataTable $puestosDataTable
     *
     * @return Response
     */
    public function index(PuestosDataTable $puestosDataTable)
    {
        if (request()->ajax()) {
            $unidades = Puestos::join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
            ->join('gerencia', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
            ->select([
                'puestos.PuestoID',
                'puestos.NombrePuesto',
                DB::raw('CONCAT(departamentos.NombreDepartamento," - ", gerencia.NombreGerencia) AS nombre_departamento')
            ]);
            
        return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('puestos.datatables_actions', ['id' => $row->PuestoID])->render();
                })
                ->filterColumn('nombre_departamento', function($query, $keyword) {
                    $query->whereRaw("CONCAT(departamentos.NombreDepartamento, ' - ', gerencia.NombreGerencia) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['action'])
                ->setRowId('PuestoID')
                ->make(true);
        }

        return $puestosDataTable->render('puestos.index');
    }

    /**
     * Show the form for creating a new Puestos.
     *
     * @return Response
     */
    public function create()
    {
        return view('puestos.create');
    }

    /**
     * Store a newly created Puestos in storage.
     *
     * @param CreatePuestosRequest $request
     *
     * @return Response
     */
    public function store(CreatePuestosRequest $request)
    {
        $input = $request->all();

        $puestos = $this->puestosRepository->create($input);

        Flash::success('Puestos saved successfully.');

        return redirect(route('puestos.index'));
    }

    /**
     * Display the specified Puestos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puestos not found');

            return redirect(route('puestos.index'));
        }

        return view('puestos.show')->with('puestos', $puestos);
    }

    /**
     * Show the form for editing the specified Puestos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puestos not found');

            return redirect(route('puestos.index'));
        }

        return view('puestos.edit')->with('puestos', $puestos);
    }

    /**
     * Update the specified Puestos in storage.
     *
     * @param int $id
     * @param UpdatePuestosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePuestosRequest $request)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puestos not found');

            return redirect(route('puestos.index'));
        }

        $puestos = $this->puestosRepository->update($request->all(), $id);

        Flash::success('Puestos updated successfully.');

        return redirect(route('puestos.index'));
    }

    /**
     * Remove the specified Puestos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puestos not found');

            return redirect(route('puestos.index'));
        }

        $this->puestosRepository->delete($id);

        Flash::success('Puestos deleted successfully.');

        return redirect(route('puestos.index'));
    }
}
