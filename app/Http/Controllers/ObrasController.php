<?php

namespace App\Http\Controllers;

use App\DataTables\ObrasDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateObrasRequest;
use App\Http\Requests\UpdateObrasRequest;
use App\Repositories\ObrasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Obras;
use Yajra\DataTables\DataTables;


class ObrasController extends AppBaseController
{
    /** @var ObrasRepository $obrasRepository*/
    private $obrasRepository;

    public function __construct(ObrasRepository $obrasRepo)
    {
        $this->obrasRepository = $obrasRepo;

        $this->middleware('permission:ver-obras|crear-obras|editar-obras|borrar-obras')->only('index');
        $this->middleware('permission:crear-obras', ['only' => ['create','store']]);
        $this->middleware('permission:editar-obras', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-obras', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Obras.
     *
     * @param ObrasDataTable $obrasDataTable
     *
     * @return Response
     */
    public function index(ObrasDataTable $obrasDataTable)
    {
        
        if (request()->ajax()) {
            $unidades = Obras::join('unidadesdenegocio', 'obras.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->select([
                'obras.ObraID',
                'obras.NombreObra',
                'obras.Direccion',
                'obras.EncargadoDeObra',
                'unidadesdenegocio.NombreEmpresa as nombre_empresa'
            ]);
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('obras.datatables_actions', ['id' => $row->ObraID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }


        return $obrasDataTable->render('obras.index');
    }

    /**
     * Show the form for creating a new Obras.
     *
     * @return Response
     */
    public function create()
    {
        return view('obras.create');
    }

    /**
     * Store a newly created Obras in storage.
     *
     * @param CreateObrasRequest $request
     *
     * @return Response
     */
    public function store(CreateObrasRequest $request)
    {
        $input = $request->all();

        $obras = $this->obrasRepository->create($input);

        Flash::success('Obras saved successfully.');

        return redirect(route('obras.index'));
    }

    /**
     * Display the specified Obras.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $obras = $this->obrasRepository->find($id);

        if (empty($obras)) {
            Flash::error('Obras not found');

            return redirect(route('obras.index'));
        }

        return view('obras.show')->with('obras', $obras);
    }

    /**
     * Show the form for editing the specified Obras.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $obras = $this->obrasRepository->find($id);

        if (empty($obras)) {
            Flash::error('Obras not found');

            return redirect(route('obras.index'));
        }

        return view('obras.edit')->with('obras', $obras);
    }

    /**
     * Update the specified Obras in storage.
     *
     * @param int $id
     * @param UpdateObrasRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateObrasRequest $request)
    {
        $obras = $this->obrasRepository->find($id);

        if (empty($obras)) {
            Flash::error('Obras not found');

            return redirect(route('obras.index'));
        }

        $obras = $this->obrasRepository->update($request->all(), $id);

        Flash::success('Obras updated successfully.');

        return redirect(route('obras.index'));
    }

    /**
     * Remove the specified Obras from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $obras = $this->obrasRepository->find($id);

        if (empty($obras)) {
            Flash::error('Obras not found');

            return redirect(route('obras.index'));
        }

        $this->obrasRepository->delete($id);

        Flash::success('Obras deleted successfully.');

        return redirect(route('obras.index'));
    }
}
