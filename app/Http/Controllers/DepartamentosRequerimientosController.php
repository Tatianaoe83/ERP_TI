<?php

namespace App\Http\Controllers;

use App\DataTables\DepartamentosRequerimientosDataTable;
use App\Http\Requests\CreateDepartamentosRequerimientosRequest;
use App\Http\Requests\UpdateDepartamentosRequerimientosRequest;
use App\Repositories\DepartamentosRequerimientosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class DepartamentosRequerimientosController extends AppBaseController
{
    /** @var DepartamentosRequerimientosRepository $departamentosRequerimientosRepository*/
    private $departamentosRequerimientosRepository;

    public function __construct(DepartamentosRequerimientosRepository $departamentosRequerimientosRepo)
    {
        $this->departamentosRequerimientosRepository = $departamentosRequerimientosRepo;
    }

    /**
     * Display a listing of the DepartamentosRequerimientos.
     *
     * @param DepartamentosRequerimientosDataTable $departamentosRequerimientosDataTable
     *
     * @return Response
     */
    public function index(DepartamentosRequerimientosDataTable $departamentosRequerimientosDataTable)
    {
        return $departamentosRequerimientosDataTable->render('departamentos_requerimientos.index');
    }

    /**
     * Show the form for creating a new DepartamentosRequerimientos.
     *
     * @return Response
     */
    public function create()
    {
        return view('departamentos_requerimientos.create');
    }

    /**
     * Store a newly created DepartamentosRequerimientos in storage.
     *
     * @param CreateDepartamentosRequerimientosRequest $request
     *
     * @return Response
     */
    public function store(CreateDepartamentosRequerimientosRequest $request)
    {
        $input = $request->all();

        $departamentosRequerimientos = $this->departamentosRequerimientosRepository->create($input);

        Flash::success('Departamentos Requerimientos saved successfully.');

        return redirect(route('departamentosRequerimientos.index'));
    }

    /**
     * Display the specified DepartamentosRequerimientos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $departamentosRequerimientos = $this->departamentosRequerimientosRepository->find($id);

        if (empty($departamentosRequerimientos)) {
            Flash::error('Departamentos Requerimientos not found');

            return redirect(route('departamentosRequerimientos.index'));
        }

        return view('departamentos_requerimientos.show')->with('departamentosRequerimientos', $departamentosRequerimientos);
    }

    /**
     * Show the form for editing the specified DepartamentosRequerimientos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $departamentosRequerimientos = $this->departamentosRequerimientosRepository->find($id);

        if (empty($departamentosRequerimientos)) {
            Flash::error('Departamentos Requerimientos not found');

            return redirect(route('departamentosRequerimientos.index'));
        }

        return view('departamentos_requerimientos.edit')->with('departamentosRequerimientos', $departamentosRequerimientos);
    }

    /**
     * Update the specified DepartamentosRequerimientos in storage.
     *
     * @param int $id
     * @param UpdateDepartamentosRequerimientosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDepartamentosRequerimientosRequest $request)
    {
        $departamentosRequerimientos = $this->departamentosRequerimientosRepository->find($id);

        if (empty($departamentosRequerimientos)) {
            Flash::error('Departamentos Requerimientos not found');

            return redirect(route('departamentosRequerimientos.index'));
        }

        $departamentosRequerimientos = $this->departamentosRequerimientosRepository->update($request->all(), $id);

        Flash::success('Departamentos Requerimientos updated successfully.');

        return redirect(route('departamentosRequerimientos.index'));
    }

    /**
     * Remove the specified DepartamentosRequerimientos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $departamentosRequerimientos = $this->departamentosRequerimientosRepository->find($id);

        if (empty($departamentosRequerimientos)) {
            Flash::error('Departamentos Requerimientos not found');

            return redirect(route('departamentosRequerimientos.index'));
        }

        $this->departamentosRequerimientosRepository->delete($id);

        Flash::success('Departamentos Requerimientos deleted successfully.');

        return redirect(route('departamentosRequerimientos.index'));
    }
}
