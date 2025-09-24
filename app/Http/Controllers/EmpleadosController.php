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
        $this->middleware('permission:ver-empleados|crear-empleados|editar-empleados|borrar-empleados')->only('index');
        $this->middleware('permission:crear-empleados', ['only' => ['create','store']]);
        $this->middleware('permission:editar-empleados', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-empleados', ['only' => ['destroy']]);
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
     * Dar de baja al empleado (cambiar estado a inactivo).
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

        // Cambiar estado a 0 (inactivo) en lugar de soft delete
        $empleados->update(['Estado' => 0]);

        Flash::success('Empleado dado de baja exitosamente.');

        return redirect(route('empleados.index'));
    }
}
