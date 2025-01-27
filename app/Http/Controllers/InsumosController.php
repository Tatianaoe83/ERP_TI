<?php

namespace App\Http\Controllers;

use App\DataTables\InsumosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInsumosRequest;
use App\Http\Requests\UpdateInsumosRequest;
use App\Repositories\InsumosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class InsumosController extends AppBaseController
{
    /** @var InsumosRepository $insumosRepository*/
    private $insumosRepository;

    public function __construct(InsumosRepository $insumosRepo)
    {
        $this->insumosRepository = $insumosRepo;
    }

    /**
     * Display a listing of the Insumos.
     *
     * @param InsumosDataTable $insumosDataTable
     *
     * @return Response
     */
    public function index(InsumosDataTable $insumosDataTable)
    {
        return $insumosDataTable->render('insumos.index');
    }

    /**
     * Show the form for creating a new Insumos.
     *
     * @return Response
     */
    public function create()
    {
        return view('insumos.create');
    }

    /**
     * Store a newly created Insumos in storage.
     *
     * @param CreateInsumosRequest $request
     *
     * @return Response
     */
    public function store(CreateInsumosRequest $request)
    {
        $input = $request->all();

        $insumos = $this->insumosRepository->create($input);

        Flash::success('Insumos saved successfully.');

        return redirect(route('insumos.index'));
    }

    /**
     * Display the specified Insumos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        return view('insumos.show')->with('insumos', $insumos);
    }

    /**
     * Show the form for editing the specified Insumos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        return view('insumos.edit')->with('insumos', $insumos);
    }

    /**
     * Update the specified Insumos in storage.
     *
     * @param int $id
     * @param UpdateInsumosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInsumosRequest $request)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        $insumos = $this->insumosRepository->update($request->all(), $id);

        Flash::success('Insumos updated successfully.');

        return redirect(route('insumos.index'));
    }

    /**
     * Remove the specified Insumos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        $this->insumosRepository->delete($id);

        Flash::success('Insumos deleted successfully.');

        return redirect(route('insumos.index'));
    }
}
