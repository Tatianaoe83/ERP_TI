<?php

namespace App\Http\Controllers;

use App\DataTables\CategoriasDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCategoriasRequest;
use App\Http\Requests\UpdateCategoriasRequest;
use App\Repositories\CategoriasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Categorias;
use Yajra\DataTables\DataTables;

class CategoriasController extends AppBaseController
{
    /** @var CategoriasRepository $categoriasRepository*/
    private $categoriasRepository;

    public function __construct(CategoriasRepository $categoriasRepo)
    {
        $this->categoriasRepository = $categoriasRepo;
        $this->middleware('permission:ver-categorias|crear-categorias|editar-categorias|borrar-categorias')->only('index');
        $this->middleware('permission:crear-categorias', ['only' => ['create','store']]);
        $this->middleware('permission:editar-categorias', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-categorias', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Categorias.
     *
     * @param CategoriasDataTable $categoriasDataTable
     *
     * @return Response
     */
    public function index(CategoriasDataTable $categoriasDataTable)
    {
        if (request()->ajax()) {
            $unidades = Categorias::join('tiposdecategorias', 'categorias.TipoID', '=', 'tiposdecategorias.ID')
            ->select([
                'categorias.ID',
                'tiposdecategorias.Categoria as nombre_categoria',
                'categorias.Categoria'
            ]);
    
            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('categorias.datatables_actions', ['id' => $row->ID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $categoriasDataTable->render('categorias.index');
    }

    /**
     * Show the form for creating a new Categorias.
     *
     * @return Response
     */
    public function create()
    {
        return view('categorias.create');
    }

    /**
     * Store a newly created Categorias in storage.
     *
     * @param CreateCategoriasRequest $request
     *
     * @return Response
     */
    public function store(CreateCategoriasRequest $request)
    {
        $input = $request->all();

        $categorias = $this->categoriasRepository->create($input);

        Flash::success('Categorias saved successfully.');

        return redirect(route('categorias.index'));
    }

    /**
     * Display the specified Categorias.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $categorias = $this->categoriasRepository->find($id);

        if (empty($categorias)) {
            Flash::error('Categorias not found');

            return redirect(route('categorias.index'));
        }

        return view('categorias.show')->with('categorias', $categorias);
    }

    /**
     * Show the form for editing the specified Categorias.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $categorias = $this->categoriasRepository->find($id);

        if (empty($categorias)) {
            Flash::error('Categorias not found');

            return redirect(route('categorias.index'));
        }

        return view('categorias.edit')->with('categorias', $categorias);
    }

    /**
     * Update the specified Categorias in storage.
     *
     * @param int $id
     * @param UpdateCategoriasRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCategoriasRequest $request)
    {
        $categorias = $this->categoriasRepository->find($id);

        if (empty($categorias)) {
            Flash::error('Categorias not found');

            return redirect(route('categorias.index'));
        }

        $categorias = $this->categoriasRepository->update($request->all(), $id);

        Flash::success('Categorias updated successfully.');

        return redirect(route('categorias.index'));
    }

    /**
     * Remove the specified Categorias from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $categorias = $this->categoriasRepository->find($id);

        if (empty($categorias)) {
            Flash::error('Categorias not found');

            return redirect(route('categorias.index'));
        }

        $this->categoriasRepository->delete($id);

        Flash::success('Categorias deleted successfully.');

        return redirect(route('categorias.index'));
    }
}
