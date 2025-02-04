<?php

namespace App\Http\Controllers;

use App\DataTables\PlanesDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatePlanesRequest;
use App\Http\Requests\UpdatePlanesRequest;
use App\Repositories\PlanesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Planes;
use Yajra\DataTables\DataTables;

class PlanesController extends AppBaseController
{
    /** @var PlanesRepository $planesRepository*/
    private $planesRepository;

    public function __construct(PlanesRepository $planesRepo)
    {
        $this->planesRepository = $planesRepo;
    }

    /**
     * Display a listing of the Planes.
     *
     * @param PlanesDataTable $planesDataTable
     *
     * @return Response
     */
    public function index(PlanesDataTable $planesDataTable)
    {
        if (request()->ajax()) {
            $unidades = Planes::join('companiaslineastelefonicas', 'planes.companiaID', '=', 'companiaslineastelefonicas.ID')
            ->select([
                'planes.ID',
                'companiaslineastelefonicas.Compania as nombre_compania',
                'planes.NombrePlan',
                'planes.PrecioPlan'
            ]);
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('planes.datatables_actions', ['id' => $row->ID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $planesDataTable->render('planes.index');
    }

    /**
     * Show the form for creating a new Planes.
     *
     * @return Response
     */
    public function create()
    {
        return view('planes.create');
    }

    /**
     * Store a newly created Planes in storage.
     *
     * @param CreatePlanesRequest $request
     *
     * @return Response
     */
    public function store(CreatePlanesRequest $request)
    {
        $input = $request->all();

        $planes = $this->planesRepository->create($input);

        Flash::success('Planes saved successfully.');

        return redirect(route('planes.index'));
    }

    /**
     * Display the specified Planes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $planes = $this->planesRepository->find($id);

        if (empty($planes)) {
            Flash::error('Planes not found');

            return redirect(route('planes.index'));
        }

        return view('planes.show')->with('planes', $planes);
    }

    /**
     * Show the form for editing the specified Planes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $planes = $this->planesRepository->find($id);

        if (empty($planes)) {
            Flash::error('Planes not found');

            return redirect(route('planes.index'));
        }

        return view('planes.edit')->with('planes', $planes);
    }

    /**
     * Update the specified Planes in storage.
     *
     * @param int $id
     * @param UpdatePlanesRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePlanesRequest $request)
    {
        $planes = $this->planesRepository->find($id);

        if (empty($planes)) {
            Flash::error('Planes not found');

            return redirect(route('planes.index'));
        }

        $planes = $this->planesRepository->update($request->all(), $id);

        Flash::success('Planes updated successfully.');

        return redirect(route('planes.index'));
    }

    /**
     * Remove the specified Planes from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $planes = $this->planesRepository->find($id);

        if (empty($planes)) {
            Flash::error('Planes not found');

            return redirect(route('planes.index'));
        }

        $this->planesRepository->delete($id);

        Flash::success('Planes deleted successfully.');

        return redirect(route('planes.index'));
    }
}
