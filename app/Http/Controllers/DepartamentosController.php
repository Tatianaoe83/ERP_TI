<?php

namespace App\Http\Controllers;

use App\DataTables\DepartamentosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateDepartamentosRequest;
use App\Http\Requests\UpdateDepartamentosRequest;
use App\Repositories\DepartamentosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Departamentos;
use Yajra\DataTables\DataTables;

class DepartamentosController extends AppBaseController
{
    /** @var DepartamentosRepository $departamentosRepository*/
    private $departamentosRepository;

    public function __construct(DepartamentosRepository $departamentosRepo)
    {
        $this->departamentosRepository = $departamentosRepo;
    }

    /**
     * Display a listing of the Departamentos.
     *
     * @param DepartamentosDataTable $departamentosDataTable
     *
     * @return Response
     */
    public function index(DepartamentosDataTable $departamentosDataTable)
    {
        if (request()->ajax()) {
            $unidades = Departamentos::select([
                'DepartamentoID',
                'NombreDepartamento',
                'GerenciaID'
            ]);
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('departamentos.datatables_actions', ['id' => $row->DepartamentoID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $departamentosDataTable->render('departamentos.index');
    }

    /**
     * Show the form for creating a new Departamentos.
     *
     * @return Response
     */
    public function create()
    {
        return view('departamentos.create');
    }

    /**
     * Store a newly created Departamentos in storage.
     *
     * @param CreateDepartamentosRequest $request
     *
     * @return Response
     */
    public function store(CreateDepartamentosRequest $request)
    {
        $input = $request->all();

        $departamentos = $this->departamentosRepository->create($input);

        Flash::success('Departamentos saved successfully.');

        return redirect(route('departamentos.index'));
    }

    /**
     * Display the specified Departamentos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            Flash::error('Departamentos not found');

            return redirect(route('departamentos.index'));
        }

        return view('departamentos.show')->with('departamentos', $departamentos);
    }

    /**
     * Show the form for editing the specified Departamentos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            Flash::error('Departamentos not found');

            return redirect(route('departamentos.index'));
        }

        return view('departamentos.edit')->with('departamentos', $departamentos);
    }

    /**
     * Update the specified Departamentos in storage.
     *
     * @param int $id
     * @param UpdateDepartamentosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDepartamentosRequest $request)
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            Flash::error('Departamentos not found');

            return redirect(route('departamentos.index'));
        }

        $departamentos = $this->departamentosRepository->update($request->all(), $id);

        Flash::success('Departamentos updated successfully.');

        return redirect(route('departamentos.index'));
    }

    /**
     * Remove the specified Departamentos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            Flash::error('Departamentos not found');

            return redirect(route('departamentos.index'));
        }

        $this->departamentosRepository->delete($id);

        Flash::success('Departamentos deleted successfully.');

        return redirect(route('departamentos.index'));
    }
}
