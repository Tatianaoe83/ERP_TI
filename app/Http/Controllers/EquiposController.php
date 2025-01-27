<?php

namespace App\Http\Controllers;

use App\DataTables\EquiposDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateEquiposRequest;
use App\Http\Requests\UpdateEquiposRequest;
use App\Repositories\EquiposRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Equipos;
use Yajra\DataTables\DataTables;

class EquiposController extends AppBaseController
{
    /** @var EquiposRepository $equiposRepository*/
    private $equiposRepository;

    public function __construct(EquiposRepository $equiposRepo)
    {
        $this->equiposRepository = $equiposRepo;
    }

    /**
     * Display a listing of the Equipos.
     *
     * @param EquiposDataTable $equiposDataTable
     *
     * @return Response
     */
    public function index(EquiposDataTable $equiposDataTable)
    {
        if (request()->ajax()) {
            $unidades = Equipos::select([
                'ID',
                'CategoriaID',
                'Marca',
                'Caracteristicas',
                'Modelo',
                'Precio'
            ]);
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('equipos.datatables_actions', ['id' => $row->ID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $equiposDataTable->render('equipos.index');
    }

    /**
     * Show the form for creating a new Equipos.
     *
     * @return Response
     */
    public function create()
    {
        return view('equipos.create');
    }

    /**
     * Store a newly created Equipos in storage.
     *
     * @param CreateEquiposRequest $request
     *
     * @return Response
     */
    public function store(CreateEquiposRequest $request)
    {
        $input = $request->all();

        $equipos = $this->equiposRepository->create($input);

        Flash::success('Equipos saved successfully.');

        return redirect(route('equipos.index'));
    }

    /**
     * Display the specified Equipos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        return view('equipos.show')->with('equipos', $equipos);
    }

    /**
     * Show the form for editing the specified Equipos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        return view('equipos.edit')->with('equipos', $equipos);
    }

    /**
     * Update the specified Equipos in storage.
     *
     * @param int $id
     * @param UpdateEquiposRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateEquiposRequest $request)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        $equipos = $this->equiposRepository->update($request->all(), $id);

        Flash::success('Equipos updated successfully.');

        return redirect(route('equipos.index'));
    }

    /**
     * Remove the specified Equipos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        $this->equiposRepository->delete($id);

        Flash::success('Equipos deleted successfully.');

        return redirect(route('equipos.index'));
    }
}
