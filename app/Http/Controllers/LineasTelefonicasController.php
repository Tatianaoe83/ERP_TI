<?php

namespace App\Http\Controllers;

use App\DataTables\LineasTelefonicasDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateLineasTelefonicasRequest;
use App\Http\Requests\UpdateLineasTelefonicasRequest;
use App\Repositories\LineasTelefonicasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\LineasTelefonicas;
use Illuminate\Database\QueryException;
use Laracasts\Flash\Flash as FlashFlash;
use Yajra\DataTables\DataTables;

class LineasTelefonicasController extends AppBaseController
{
    /** @var LineasTelefonicasRepository $lineasTelefonicasRepository*/
    private $lineasTelefonicasRepository;

    public function __construct(LineasTelefonicasRepository $lineasTelefonicasRepo)
    {
        $this->lineasTelefonicasRepository = $lineasTelefonicasRepo;
        $this->middleware('permission:ver-Lineastelefonicas|crear-Lineastelefonicas|editar-Lineastelefonicas|borrar-Lineastelefonicas')->only('index');
        $this->middleware('permission:crear-Lineastelefonicas', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-Lineastelefonicas', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-Lineastelefonicas', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the LineasTelefonicas.
     *
     * @param LineasTelefonicasDataTable $lineasTelefonicasDataTable
     *
     * @return Response
     */
    public function index(LineasTelefonicasDataTable $lineasTelefonicasDataTable)
    {
        if (request()->ajax()) {
            $unidades = LineasTelefonicas::join('obras', 'obras.ObraID', '=', 'lineastelefonicas.ObraID')
                ->join('planes', 'planes.ID', '=', 'lineastelefonicas.PlanID')
                ->select([
                    'lineastelefonicas.LineaID',
                    'lineastelefonicas.NumTelefonico',
                    'planes.NombrePlan as nombre_plan',
                    'lineastelefonicas.CuentaPadre',
                    'lineastelefonicas.CuentaHija',
                    'lineastelefonicas.TipoLinea',
                    'obras.NombreObra as nombre_obra',
                    'lineastelefonicas.FechaFianza',
                    'lineastelefonicas.CostoFianza',
                    'lineastelefonicas.Activo',
                    'lineastelefonicas.Disponible',
                    'lineastelefonicas.MontoRenovacionFianza'

                ]);

            return DataTables::of($unidades)
                ->addColumn('action', function ($row) {
                    return view('lineas_telefonicas.datatables_actions', ['id' => $row->LineaID])->render();
                })
                ->addColumn('estado_disponibilidad', function ($row) {
                    if ($row->Disponible == 1) {
                        return '<span class="badge badge-success">Disponible</span>';
                    } else {
                        return '<span class="badge badge-danger">Asignada</span>';
                    }
                })
                ->rawColumns(['action', 'estado_disponibilidad'])
                ->make(true);
        }


        return $lineasTelefonicasDataTable->render('lineas_telefonicas.index');
    }

    /**
     * Show the form for creating a new LineasTelefonicas.
     *
     * @return Response
     */
    public function create()
    {
        return view('lineas_telefonicas.create');
    }

    /**
     * Store a newly created LineasTelefonicas in storage.
     *
     * @param CreateLineasTelefonicasRequest $request
     *
     * @return Response
     */
    public function store(CreateLineasTelefonicasRequest $request)
    {
        $input = $request->all();

        try {
            $lineasTelefonicas = $this->lineasTelefonicasRepository->create($input);
            Flash::success('Linea telefonica guardada correctamente');
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                session()->flash('swal', [
                    'icon' => 'error',
                    'title' => 'Duplicado',
                    'text' => 'El número telefónico ya existe.'
                ]);
                return redirect()->back()->withInput();
            }
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Error al guardar la línea: ' . $e->getMessage()
            ]);
            return redirect()->back()->withInput();
        }

        return redirect(route('lineasTelefonicas.index'));
    }

    /**
     * Display the specified LineasTelefonicas.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        return view('lineas_telefonicas.show')->with('lineasTelefonicas', $lineasTelefonicas);
    }

    /**
     * Show the form for editing the specified LineasTelefonicas.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        return view('lineas_telefonicas.edit')->with('lineasTelefonicas', $lineasTelefonicas);
    }

    /**
     * Update the specified LineasTelefonicas in storage.
     *
     * @param int $id
     * @param UpdateLineasTelefonicasRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateLineasTelefonicasRequest $request)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        $lineasTelefonicas = $this->lineasTelefonicasRepository->update($request->all(), $id);

        Flash::success('Lineas Telefonicas updated successfully.');

        return redirect(route('lineasTelefonicas.index'));
    }

    /**
     * Remove the specified LineasTelefonicas from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        $this->lineasTelefonicasRepository->delete($id);

        Flash::success('Lineas Telefonicas deleted successfully.');

        return redirect(route('lineasTelefonicas.index'));
    }
}
