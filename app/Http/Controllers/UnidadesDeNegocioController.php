<?php

namespace App\Http\Controllers;

use App\DataTables\UnidadesDeNegocioDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateUnidadesDeNegocioRequest;
use App\Http\Requests\UpdateUnidadesDeNegocioRequest;
use App\Repositories\UnidadesDeNegocioRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\UnidadesDeNegocio;
use Yajra\DataTables\DataTables;

class UnidadesDeNegocioController extends AppBaseController
{
    /** @var UnidadesDeNegocioRepository $unidadesDeNegocioRepository*/
    private $unidadesDeNegocioRepository;

    public function __construct(UnidadesDeNegocioRepository $unidadesDeNegocioRepo)
    {
        $this->unidadesDeNegocioRepository = $unidadesDeNegocioRepo;
    }

    /**
     * Display a listing of the UnidadesDeNegocio.
     *
     * @param UnidadesDeNegocioDataTable $dataTable
     *
     * @return Response
     */
    public function index(UnidadesDeNegocioDataTable $dataTable)
    {
        if (request()->ajax()) {
            $unidades = UnidadesDeNegocio::select([
                'UnidadNegocioID',
                'NombreEmpresa',
                'RFC',
                'Direccion',
                'NumTelefono'
            ]);
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('unidades_de_negocios.datatables_actions', ['id' => $row->UnidadNegocioID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $dataTable->render('unidades_de_negocios.index');
    }

    /**
     * Show the form for creating a new UnidadesDeNegocio.
     *
     * @return Response
     */
    public function create()
    {
        return view('unidades_de_negocios.create');
    }

    /**
     * Store a newly created UnidadesDeNegocio in storage.
     *
     * @param CreateUnidadesDeNegocioRequest $request
     *
     * @return Response
     */
    public function store(CreateUnidadesDeNegocioRequest $request)
    {
        $input = $request->all();

        $unidadesDeNegocio = $this->unidadesDeNegocioRepository->create($input);

        Flash::success('Unidades De Negocio saved successfully.');

        return redirect(route('unidadesDeNegocios.index'));
    }

    /**
     * Display the specified UnidadesDeNegocio.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
       
        $unidadesDeNegocio = $this->unidadesDeNegocioRepository->find($id);

        if (empty($unidadesDeNegocio)) {
            Flash::error('Unidades De Negocio not found');

            return redirect(route('unidadesDeNegocios.index'));
        }

        return view('unidades_de_negocios.show')->with('unidadesDeNegocio', $unidadesDeNegocio);
    }

    /**
     * Show the form for editing the specified UnidadesDeNegocio.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $unidadesDeNegocio = $this->unidadesDeNegocioRepository->find($id);

        if (empty($unidadesDeNegocio)) {
            Flash::error('Unidades De Negocio not found');

            return redirect(route('unidadesDeNegocios.index'));
        }

        return view('unidades_de_negocios.edit')->with('unidadesDeNegocio', $unidadesDeNegocio);
    }

    /**
     * Update the specified UnidadesDeNegocio in storage.
     *
     * @param int $id
     * @param UpdateUnidadesDeNegocioRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateUnidadesDeNegocioRequest $request)
    {
        $unidadesDeNegocio = $this->unidadesDeNegocioRepository->find($id);

        if (empty($unidadesDeNegocio)) {
            Flash::error('Unidades De Negocio not found');

            return redirect(route('unidadesDeNegocios.index'));
        }

        $unidadesDeNegocio = $this->unidadesDeNegocioRepository->update($request->all(), $id);

        Flash::success('Unidades De Negocio updated successfully.');

        return redirect(route('unidadesDeNegocios.index'));
    }

    /**
     * Remove the specified UnidadesDeNegocio from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $unidadesDeNegocio = $this->unidadesDeNegocioRepository->find($id);

        if (empty($unidadesDeNegocio)) {
            Flash::error('Unidades De Negocio not found');

            return redirect(route('unidadesDeNegocios.index'));
        }

        $this->unidadesDeNegocioRepository->delete($id);

        Flash::success('Unidades De Negocio deleted successfully.');

        return redirect(route('unidadesDeNegocios.index'));
    }

 
}
