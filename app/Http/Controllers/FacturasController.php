<?php

namespace App\Http\Controllers;

use App\DataTables\FacturasDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateFacturasRequest;
use App\Http\Requests\UpdateFacturasRequest;
use App\Repositories\FacturasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

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
     * @param FacturasDataTable $facturasDataTable
     *
     * @return Response
     */
    public function index(FacturasDataTable $facturasDataTable)
    {
        return $facturasDataTable->render('facturas.index');
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
