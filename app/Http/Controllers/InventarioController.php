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
use App\Models\InventarioInsumo;
use App\Models\InventarioLineas;
use App\Models\LineasTelefonicas;
use App\Models\Insumos;
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
            $unidades = Empleados::join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->select([
                'empleados.EmpleadoID',
                'empleados.NombreEmpleado',
                'puestos.NombrePuesto as nombre_puesto',
                'obras.NombreObra as nombre_obra',
                'empleados.NumTelefono',
                'empleados.Correo',
                'empleados.Estado'
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
        $inventario = DB::table('empleados')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
            ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
            ->join('unidadesdenegocio', 'unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID')
            ->select(
                'empleados.*',
                'puestos.PuestoID',
                'departamentos.DepartamentoID',
                'obras.ObraID',
                'gerencia.GerenciaID',
                'unidadesdenegocio.UnidadNegocioID'
                
            )
            ->where('empleados.EmpleadoID', $id)
            ->first();

        if (empty($inventario)) {
            Flash::error('Inventario no encontrado');
            return redirect(route('inventarios.index'));
        }

       
        $EquiposAsignados = InventarioEquipo::select("*")->where('EmpleadoID','=',$id)->get();
        $Equipos = Equipos::select("*")->get();

        $InsumosAsignados = InventarioInsumo::select("*")->where('EmpleadoID','=',$id)->get();
        $Insumos = Insumos::select("*")->get();

        $LineasAsignados = InventarioLineas::select("*")->where('EmpleadoID','=',$id)->get();
        $Lineas = LineasTelefonicas::select("*")->where('Disponible','=',0)->get();



        return view('inventarios.edit')->with([
            'inventario' => $inventario,
            'equiposAsignados' => $EquiposAsignados,
            'equipos' => $Equipos,
            'insumosAsignados' => $InsumosAsignados,
            'insumos' => $Insumos,
            'LineasAsignados' => $LineasAsignados,
            'Lineas' => $Lineas
          
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
    public function editarequipo($id, Request $request)
    {
     
        $inventarioEquipo = InventarioEquipo::where('InventarioID', $id)->first(); 

        if (!$inventarioEquipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $inventarioEquipo->update($request->all());

        return response()->json(['message' => 'Inventario actualizado correctamente']);

       

    }

    public function crearequipo($id, Request $request)
    {

        $data = $request->all();
        $data['EmpleadoID'] = $id; 
    
        InventarioEquipo::create($data);


        return response()->json(['message' => 'Inventario actualizado correctamente']);

       

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
       
        $inventario = InventarioEquipo::where('InventarioID', $id)->first(); 


        if (empty($inventario)) {
            Flash::error('Inventario not found');

            return redirect(route('inventarios.index'));
        }

         $inventario->delete($id);

        Flash::success('Inventario deleted successfully.');

        return redirect()->route('inventarios.edit', ['inventario' => $inventario->EmpleadoID]);
    }


    public function editarinsumo($id, Request $request)
    {
     
     
        $inventarioinsumo = InventarioInsumo::where('InventarioID', $id)->first(); 

        if (!$inventarioinsumo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $inventarioinsumo->update($request->all());

        return response()->json(['message' => 'Inventario actualizado correctamente']);

       

    }

    public function crearinsumo($id, Request $request)
    {

       
        $categorias = Insumos::select("*")->where('NombreInsumo', $request->NombreInsumo)->first(); 

        $data = $request->all();
        $data['EmpleadoID'] = $id; 
        $data['InsumoID'] = $categorias->ID; 
    
    
        InventarioInsumo::create($data);


        return response()->json(['message' => 'Inventario actualizado correctamente']);

       

    }

    public function destroyInsumo($id)
    {
       
      
        $InventarioInsumo = InventarioInsumo::where('InventarioID', $id)->first(); 


        if (empty($InventarioInsumo)) {
            Flash::error('Inventario not found');

            return redirect(route('inventarios.index'));
        }

         $InventarioInsumo->delete($id);

        Flash::success('Inventario deleted successfully.');

        return redirect()->route('inventarios.edit', ['inventario' => $InventarioInsumo->EmpleadoID]);
    }

    
    public function editarlinea($id, Request $request)
    {
     
        dd ('editar linea');
     
        $inventarioinsumo = InventarioInsumo::where('InventarioID', $id)->first(); 

        if (!$inventarioinsumo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $inventarioinsumo->update($request->all());

        return response()->json(['message' => 'Inventario actualizado correctamente']);

       

    }

    public function crearlinea($id, Request $request)
    {

        dd ($request->all());
        dd ('crear linea');
       
        $categorias = Insumos::select("*")->where('NombreInsumo', $request->NombreInsumo)->first(); 

        $data = $request->all();
        $data['EmpleadoID'] = $id; 
        $data['InsumoID'] = $categorias->ID; 
    
    
        InventarioInsumo::create($data);


        return response()->json(['message' => 'Inventario actualizado correctamente']);

       

    }

    public function destroylinea($id)
    {
       
      dd ('destroy linea');
        $InventarioInsumo = InventarioInsumo::where('InventarioID', $id)->first(); 


        if (empty($InventarioInsumo)) {
            Flash::error('Inventario not found');

            return redirect(route('inventarios.index'));
        }

         $InventarioInsumo->delete($id);

        Flash::success('Inventario deleted successfully.');

        return redirect()->route('inventarios.edit', ['inventario' => $InventarioInsumo->EmpleadoID]);
    }


    public function transferir($id)
    {
     
           // Obtener el inventario con joins
           $inventario = DB::table('empleados')
           ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
           ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
           ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
           ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
           ->join('unidadesdenegocio', 'unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID')
           ->select(
               'empleados.*',
               'puestos.PuestoID',
               'departamentos.DepartamentoID',
               'obras.ObraID',
               'gerencia.GerenciaID',
               'unidadesdenegocio.UnidadNegocioID'
               
           )
           ->where('empleados.EmpleadoID', $id)
           ->first();

       if (empty($inventario)) {
           Flash::error('Inventario no encontrado');
           return redirect(route('inventarios.index'));
       }

      
       $EquiposAsignados = InventarioEquipo::select("*")->where('EmpleadoID','=',$id)->get();
       $InsumosAsignados = InventarioInsumo::select("*")->where('EmpleadoID','=',$id)->get();
       $LineasAsignados = InventarioLineas::select("*")->where('EmpleadoID','=',$id)->get();




       return view('inventarios.transferir')->with([
           'inventario' => $inventario,
           'equiposAsignados' => $EquiposAsignados,
           'insumosAsignados' => $InsumosAsignados,
           'LineasAsignados' => $LineasAsignados,
         
       ]);

       
    }

}
