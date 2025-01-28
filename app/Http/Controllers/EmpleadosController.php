<?php

namespace App\Http\Controllers;

use App\DataTables\EmpleadosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateEmpleadosRequest;
use App\Http\Requests\UpdateEmpleadosRequest;
use App\Repositories\EmpleadosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Empleados;
use Yajra\DataTables\DataTables;

class EmpleadosController extends AppBaseController
{
    /** @var EmpleadosRepository $empleadosRepository*/
    private $empleadosRepository;

    public function __construct(EmpleadosRepository $empleadosRepo)
    {
        $this->empleadosRepository = $empleadosRepo;
    }

    /**
     * Display a listing of the Empleados.
     *
     * @param EmpleadosDataTable $empleadosDataTable
     *
     * @return Response
     */
    public function index(EmpleadosDataTable $empleadosDataTable)
    {

        if (request()->ajax()) {
            $unidades = Empleados::join('Obras', 'Empleados.ObraID', '=', 'Obras.ObraID')
            ->join('Puestos', 'Empleados.PuestoID', '=', 'Puestos.PuestoID')
            ->select([
                'Empleados.EmpleadoID',
                'Empleados.NombreEmpleado',
                'Puestos.NombrePuesto as nombre_puesto',
                'Obras.NombreObra as nombre_obra',
                'Empleados.NumTelefono',
                'Empleados.Correo',
                'Empleados.Estado'
            ]);

            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('empleados.datatables_actions', ['id' => $row->EmpleadoID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $empleadosDataTable->render('empleados.index');
    }

    /**
     * Show the form for creating a new Empleados.
     *
     * @return Response
     */
    public function create()
    {
        return view('empleados.create');
    }

    /**
     * Store a newly created Empleados in storage.
     *
     * @param CreateEmpleadosRequest $request
     *
     * @return Response
     */
    public function store(CreateEmpleadosRequest $request)
    {
        $input = $request->all();

        $empleados = $this->empleadosRepository->create($input);

        Flash::success('Empleados saved successfully.');

        return redirect(route('empleados.index'));
    }

    /**
     * Display the specified Empleados.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $empleados = $this->empleadosRepository->find($id);

        if (empty($empleados)) {
            Flash::error('Empleados not found');

            return redirect(route('empleados.index'));
        }

        return view('empleados.show')->with('empleados', $empleados);
    }

    /**
     * Show the form for editing the specified Empleados.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $empleados = $this->empleadosRepository->find($id);

        if (empty($empleados)) {
            Flash::error('Empleados not found');

            return redirect(route('empleados.index'));
        }

        return view('empleados.edit')->with('empleados', $empleados);
    }

    /**
     * Update the specified Empleados in storage.
     *
     * @param int $id
     * @param UpdateEmpleadosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateEmpleadosRequest $request)
    {
        $empleados = $this->empleadosRepository->find($id);

        if (empty($empleados)) {
            Flash::error('Empleados not found');

            return redirect(route('empleados.index'));
        }

        $empleados = $this->empleadosRepository->update($request->all(), $id);

        Flash::success('Empleados updated successfully.');

        return redirect(route('empleados.index'));
    }

    /**
     * Remove the specified Empleados from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $empleados = $this->empleadosRepository->find($id);

        if (empty($empleados)) {
            Flash::error('Empleados not found');

            return redirect(route('empleados.index'));
        }

        $this->empleadosRepository->delete($id);

        Flash::success('Empleados deleted successfully.');

        return redirect(route('empleados.index'));
    }
}
