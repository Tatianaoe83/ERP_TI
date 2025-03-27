<?php

namespace App\Http\Controllers;

use App\DataTables\GerenciaDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateGerenciaRequest;
use App\Http\Requests\UpdateGerenciaRequest;
use App\Repositories\GerenciaRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Gerencia;
use Yajra\DataTables\DataTables;

class GerenciaController extends AppBaseController
{
    /** @var GerenciaRepository $gerenciaRepository*/
    private $gerenciaRepository;

    public function __construct(GerenciaRepository $gerenciaRepo)
    {
        $this->gerenciaRepository = $gerenciaRepo;
    }

    /**
     * Display a listing of the gerencia.
     *
     * @param GerenciaDataTable $gerenciaDataTable
     *
     * @return Response
     */
    public function index(GerenciaDataTable $gerenciaDataTable)
    {
        if (request()->ajax()) {
            $unidades = Gerencia::join('unidadesdenegocio', 'gerencia.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->select([
                'gerencia.GerenciaID',
                'gerencia.NombreGerencia',
                'gerencia.NombreGerente',
                'unidadesdenegocio.NombreEmpresa as nombre_empresa'
            ]);
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('gerencias.datatables_actions', ['id' => $row->GerenciaID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

    
        return $gerenciaDataTable->render('gerencias.index');
    }

    /**
     * Show the form for creating a new gerencia.
     *
     * @return Response
     */
    public function create()
    {
        return view('gerencias.create');
    }

    /**
     * Store a newly created Gerencia in storage.
     *
     * @param CreateGerenciaRequest $request
     *
     * @return Response
     */
    public function store(CreateGerenciaRequest $request)
    {
        $input = $request->all();

        $gerencia = $this->gerenciaRepository->create($input);

        Flash::success('Gerencia saved successfully.');

        return redirect(route('gerencias.index'));
    }

    /**
     * Display the specified gerencia.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $gerencia = $this->gerenciaRepository->find($id);

        if (empty($gerencia)) {
            Flash::error('Gerencia not found');

            return redirect(route('gerencias.index'));
        }

        return view('gerencias.show')->with('gerencia', $gerencia);
    }

    /**
     * Show the form for editing the specified gerencia.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $gerencia = $this->gerenciaRepository->find($id);

        if (empty($gerencia)) {
            Flash::error('Gerencia not found');

            return redirect(route('gerencias.index'));
        }

        return view('gerencias.edit')->with('gerencia', $gerencia);
    }

    /**
     * Update the specified Gerencia in storage.
     *
     * @param int $id
     * @param UpdateGerenciaRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateGerenciaRequest $request)
    {
        $gerencia = $this->gerenciaRepository->find($id);

        if (empty($gerencia)) {
            Flash::error('Gerencia not found');

            return redirect(route('gerencias.index'));
        }

        $gerencia = $this->gerenciaRepository->update($request->all(), $id);

        Flash::success('Gerencia updated successfully.');

        return redirect(route('gerencias.index'));
    }

    /**
     * Remove the specified Gerencia from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $gerencia = $this->gerenciaRepository->find($id);

        if (empty($gerencia)) {
            Flash::error('Gerencia not found');

            return redirect(route('gerencias.index'));
        }

        $this->gerenciaRepository->delete($id);

        Flash::success('Gerencia deleted successfully.');

        return redirect(route('gerencias.index'));
    }
}
