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
use App\Models\CentroCosto;
use App\Models\Empleados;
use App\Models\Obras;
use App\Models\Puestos;
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
        return view('empleados.create', $this->datosFormularioEmpleado());
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

        return view('empleados.edit', array_merge(
            ['empleados' => $empleados],
            $this->datosFormularioEmpleado($empleados)
        ));
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
        $empleados = $this->empleadosRepository->find($id);

        if (empty($empleados)) {
            return redirect(route('empleados.index'))->with('sweetalert_error', 'Empleado no encontrado.');
        }

        if (!$empleados->Estado) {
            [$puedeActivar, $mensaje] = Empleados::puedeActivarse($empleados);

            if (!$puedeActivar) {
                return redirect(route('empleados.index'))->with('sweetalert_error', $mensaje);
            }

            $empleados->update(['Estado' => 1]);

            return redirect(route('empleados.index'))->with('sweetalert_success', 'Empleado activado exitosamente.');
        }

        $inventario = $empleados->inventarioequipo->count();
        $inventarioinsumo = $empleados->inventarioinsumo->count();
        $inventariolineas = $empleados->inventariolineas->count();

        if ($inventario > 0 || $inventarioinsumo > 0 || $inventariolineas > 0) {
            $mensaje = 'El empleado ' . $empleados->NombreEmpleado . ' tiene inventario asociado y no puede ser dado de baja.';
            return redirect(route('empleados.index'))->with('sweetalert_error', $mensaje);
        }

        $empleados->update(['Estado' => 0]);

        return redirect(route('empleados.index'))->with('sweetalert_success', 'Empleado dado de baja exitosamente.');
    }

    private function datosFormularioEmpleado($empleado = null)
    {
        $puestos = Puestos::query()
            ->with([
                'departamentos.gerencia.direccion.unidadesdenegocio.division',
            ])
            ->orderBy('NombrePuesto')
            ->get();

        $orgPuestos = [];
        foreach ($puestos as $puesto) {
            $area = $puesto->departamentos;
            $gerencia = optional($area)->gerencia;
            $direccion = optional($gerencia)->direccion;
            $unidad = optional($direccion)->unidadesdenegocio;
            $division = optional($unidad)->division;

            if (!$area || !$gerencia || !$direccion || !$unidad || !$division) {
                continue;
            }

            $orgPuestos[] = [
                'puesto' => $puesto->PuestoID,
                'puestoNombre' => $puesto->NombrePuesto,
                'area' => $area->DepartamentoID,
                'areaNombre' => $area->NombreDepartamento,
                'gerencia' => $gerencia->GerenciaID,
                'gerenciaNombre' => $gerencia->NombreGerencia,
                'direccion' => $direccion->DireccionID,
                'direccionNombre' => $direccion->NombreDireccion,
                'unidad' => $unidad->UnidadNegocioID,
                'unidadNombre' => $unidad->NombreEmpresa,
                'division' => $division->DivisionID,
                'divisionNombre' => $division->NombreDivision,
            ];
        }

        $cadena = [
            'division' => '',
            'unidad' => '',
            'direccion' => '',
            'gerencia' => '',
            'area' => '',
            'puesto' => '',
        ];

        if ($empleado && $empleado->PuestoID) {
            $empleado->loadMissing('puestos.departamentos.gerencia.direccion.unidadesdenegocio.division');
            $puesto = $empleado->puestos;
            if (!$puesto) {
                return $this->catalogosEmpleado($orgPuestos, $cadena, $empleado);
            }
            $area = $puesto->departamentos;
            $gerencia = optional($area)->gerencia;
            $direccion = optional($gerencia)->direccion;
            $unidad = optional($direccion)->unidadesdenegocio;
            $division = optional($unidad)->division;
            $cadena = [
                'division' => $division->DivisionID ?? '',
                'unidad' => $unidad->UnidadNegocioID ?? '',
                'direccion' => $direccion->DireccionID ?? '',
                'gerencia' => $gerencia->GerenciaID ?? '',
                'area' => $area->DepartamentoID ?? '',
                'puesto' => $puesto->PuestoID ?? '',
            ];
        }

        return $this->catalogosEmpleado($orgPuestos, $cadena, $empleado);
    }

    private function catalogosEmpleado(array $orgPuestos, array $cadena, $empleado = null)
    {
        $jefes = Empleados::query()
            ->when($empleado, function ($query) use ($empleado) {
                $query->where('EmpleadoID', '!=', $empleado->EmpleadoID);
            })
            ->orderBy('NombreEmpleado')
            ->get(['EmpleadoID', 'NombreEmpleado']);

        return [
            'orgPuestos' => $orgPuestos,
            'obrasCatalogo' => Obras::query()->orderBy('NombreObra')->get(['ObraID', 'NombreObra']),
            'centrosCatalogo' => CentroCosto::query()->orderBy('NombreCentro')->get(['CentroCostoID', 'NombreCentro']),
            'jefesCatalogo' => $jefes,
            'cadenaEmpleado' => $cadena,
        ];
    }
}
