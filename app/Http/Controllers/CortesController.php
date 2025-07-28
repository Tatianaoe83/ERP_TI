<?php

namespace App\Http\Controllers;

use App\DataTables\CortesDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCortesRequest;
use App\Http\Requests\UpdateCortesRequest;
use App\Repositories\CortesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Gerencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Response;
use Yajra\DataTables\Html\Editor\Fields\Select;

class CortesController extends AppBaseController
{
    /** @var CortesRepository $cortesRepository*/
    private $cortesRepository;

    public function __construct(CortesRepository $cortesRepo)
    {
        $this->cortesRepository = $cortesRepo;
    }

    /**
     * Display a listing of the Cortes.
     *
     * @param CortesDataTable $cortesDataTable
     *
     * @return Response
     */
    /* public function index(CortesDataTable $cortesDataTable)
    {
        return $cortesDataTable->render('cortes.index');
    } */

    public function index(Request $request)
    {
        $gerencia = Gerencia::all();

        $results = [];
        if ($gerenciaID = $request->input('gerenciaID')) {
            dd("ID recibido: $gerenciaID");
            $results = DB::select('CALL ObtenerInsumosAnualesPorGerencia2(?)', [$gerenciaID]);
            dd($results);
        }
        return view('cortes.index', compact('results', 'gerencia'));
    }

    /**
     * Show the form for creating a new Cortes.
     *
     * @return Response
     */
    public function create()
    {

        return view('cortes.create');
    }

    /**
     * Store a newly created Cortes in storage.
     *
     * @param CreateCortesRequest $request
     *
     * @return Response
     */
    public function store(CreateCortesRequest $request)
    {
        $input = $request->all();

        $cortes = $this->cortesRepository->create($input);

        Flash::success('Cortes saved successfully.');

        return redirect(route('cortes.index'));
    }

    /**
     * Display the specified Cortes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        return view('cortes.show')->with('cortes', $cortes);
    }

    /**
     * Show the form for editing the specified Cortes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        return view('cortes.edit')->with('cortes', $cortes);
    }

    /**
     * Update the specified Cortes in storage.
     *
     * @param int $id
     * @param UpdateCortesRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCortesRequest $request)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        $cortes = $this->cortesRepository->update($request->all(), $id);

        Flash::success('Cortes updated successfully.');

        return redirect(route('cortes.index'));
    }

    /**
     * Remove the specified Cortes from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        $this->cortesRepository->delete($id);

        Flash::success('Cortes deleted successfully.');

        return redirect(route('cortes.index'));
    }
}
