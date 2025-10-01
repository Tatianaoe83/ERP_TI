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
     * Obtener datos para los filtros de empleados.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filtros()
    {
        try {
            $puestos = \DB::table('puestos')
                ->join('empleados', 'puestos.PuestoID', '=', 'empleados.PuestoID')
                ->select('puestos.NombrePuesto')
                ->distinct()
                ->orderBy('puestos.NombrePuesto')
                ->pluck('puestos.NombrePuesto')
                ->toArray();

            $departamentos = \DB::table('departamentos')
                ->join('puestos', 'departamentos.DepartamentoID', '=', 'puestos.DepartamentoID')
                ->join('empleados', 'puestos.PuestoID', '=', 'empleados.PuestoID')
                ->select('departamentos.NombreDepartamento')
                ->distinct()
                ->orderBy('departamentos.NombreDepartamento')
                ->pluck('departamentos.NombreDepartamento')
                ->toArray();

            $obras = \DB::table('obras')
                ->join('empleados', 'obras.ObraID', '=', 'empleados.ObraID')
                ->select('obras.NombreObra')
                ->distinct()
                ->orderBy('obras.NombreObra')
                ->pluck('obras.NombreObra')
                ->toArray();

            $gerencias = \DB::table('gerencia')
                ->join('departamentos', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
                ->join('puestos', 'departamentos.DepartamentoID', '=', 'puestos.DepartamentoID')
                ->join('empleados', 'puestos.PuestoID', '=', 'empleados.PuestoID')
                ->select('gerencia.NombreGerencia')
                ->distinct()
                ->orderBy('gerencia.NombreGerencia')
                ->pluck('gerencia.NombreGerencia')
                ->toArray();

            return response()->json([
                'puestos' => $puestos,
                'departamentos' => $departamentos,
                'obras' => $obras,
                'gerencias' => $gerencias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos de filtros',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        // Cambiar estado a 0 (inactivo) en lugar de soft delete
        $empleados = $this->empleadosRepository->find($id);

        //Revisar inventario
        $inventario = $empleados->inventarioequipo->count();
        $inventarioinsumo = $empleados->inventarioinsumo->count();
        $inventariolineas = $empleados->inventariolineas->count();
        
        if ($inventario > 0 || $inventarioinsumo > 0 || $inventariolineas > 0) {
            $tiposInventario = [];
            if ($inventario > 0) $tiposInventario[] = "equipos ($inventario)";
            if ($inventarioinsumo > 0) $tiposInventario[] = "insumos ($inventarioinsumo)";
            if ($inventariolineas > 0) $tiposInventario[] = "líneas telefónicas ($inventariolineas)";
            
            $mensaje = 'El empleado ' . $empleados->NombreEmpleado . ' tiene inventario asociado y no puede ser dado de baja.';
            return redirect(route('empleados.index'))->with('sweetalert_error', $mensaje);
        }

        if (empty($empleados)) {
            return redirect(route('empleados.index'))->with('sweetalert_error', 'Empleado no encontrado.');
        }

        $empleados->update(['Estado' => 0]);

        return redirect(route('empleados.index'))->with('sweetalert_success', 'Empleado dado de baja exitosamente.');
    }
}
