<?php

namespace App\Http\Controllers;

use App\DataTables\InventarioDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInventarioRequest;
use App\Http\Requests\UpdateInventarioRequest;
use App\Repositories\InventarioRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Empleados;
use App\Models\InventarioEquipo;
use App\Models\Equipos;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use DB;

class InventarioController extends AppBaseController
{
    /** @var InventarioRepository $inventarioRepository*/
    private $inventarioRepository;

    public function __construct(InventarioRepository $inventarioRepo)
    {
        $this->inventarioRepository = $inventarioRepo;
    }

    /**
     * Display a listing of the Inventario.
     *
     * @param InventarioDataTable $inventarioDataTable
     *
     * @return Response
     */
    public function index(InventarioDataTable $inventarioDataTable)
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
                    return view('inventarios.datatables_actions', ['id' => $row->EmpleadoID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $inventarioDataTable->render('inventarios.index');
    }

    /**
     * Show the form for creating a new Inventario.
     *
     * @return Response
     */
    public function create()
    {
        return view('inventarios.create');
    }

    /**
     * Store a newly created Inventario in storage.
     *
     * @param CreateInventarioRequest $request
     *
     * @return Response
     */
    public function store(CreateInventarioRequest $request)
    {
        $input = $request->all();

        $inventario = $this->inventarioRepository->create($input);

        Flash::success('Inventario saved successfully.');

        return redirect(route('inventarios.index'));
    }

    /**
     * Display the specified Inventario.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $inventario = $this->inventarioRepository->find($id);

        if (empty($inventario)) {
            Flash::error('Inventario not found');

            return redirect(route('inventarios.index'));
        }

        return view('inventarios.show')->with('inventario', $inventario);
    }

    /**
     * Show the form for editing the specified Inventario.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        // Obtener el inventario con joins
        $inventario = DB::table('Empleados')
            ->join('Puestos', 'Empleados.PuestoID', '=', 'Puestos.PuestoID')
            ->join('Departamentos', 'Puestos.DepartamentoID', '=', 'Departamentos.DepartamentoID')
            ->join('Obras', 'Empleados.ObraID', '=', 'Obras.ObraID')
            ->join('Gerencia', 'Departamentos.GerenciaID', '=', 'Gerencia.GerenciaID')
            ->join('UnidadesDeNegocio', 'UnidadesDeNegocio.UnidadNegocioID', '=', 'Gerencia.UnidadNegocioID')
            ->select(
                'Empleados.*',
                'Puestos.PuestoID',
                'Departamentos.DepartamentoID',
                'Obras.ObraID',
                'Gerencia.GerenciaID',
                'UnidadesDeNegocio.UnidadNegocioID'
                
            )
            ->where('Empleados.EmpleadoID', $id)
            ->first();

        if (empty($inventario)) {
            Flash::error('Inventario no encontrado');
            return redirect(route('inventarios.index'));
        }

       
        $EquiposAsignados = InventarioEquipo::select("*")->where('EmpleadoID','=',$id)->get();
        $Equipos = Equipos::select("*")->get();


        return view('inventarios.edit')->with([
            'inventario' => $inventario,
            'equiposAsignados' => $EquiposAsignados,
            'equipos' => $Equipos,
          
        ]);
    }

    /**
     * Update the specified Inventario in storage.
     *
     * @param int $id
     * @param UpdateInventarioRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInventarioRequest $request)
    {
        $inventario = $this->inventarioRepository->find($id);

        if (empty($inventario)) {
            Flash::error('Inventario not found');

            return redirect(route('inventarios.index'));
        }

        $inventario = $this->inventarioRepository->update($request->all(), $id);

        Flash::success('Inventario updated successfully.');

        return redirect(route('inventarios.index'));
    }

    /**
     * Remove the specified Inventario from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $inventario = $this->inventarioRepository->find($id);

        if (empty($inventario)) {
            Flash::error('Inventario not found');

            return redirect(route('inventarios.index'));
        }

        $this->inventarioRepository->delete($id);

        Flash::success('Inventario deleted successfully.');

        return redirect(route('inventarios.index'));
    }
}
