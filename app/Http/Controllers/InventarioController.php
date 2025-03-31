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
use App\Models\UnidadesDeNegocio;
use App\Models\Insumos;
use App\Models\Gerencia;
use App\Models\Equipos;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use DB;
use PDF;
use Carbon\Carbon;

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
                ->addColumn('action', function ($row) {
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


        $EquiposAsignados = InventarioEquipo::select("*")->where('EmpleadoID', '=', $id)->get();
        $Equipos = Equipos::select("*")->get();

        $InsumosAsignados = InventarioInsumo::select("*")->where('EmpleadoID', '=', $id)->get();
        $Insumos = Insumos::select("*")->get();

        $LineasAsignados = InventarioLineas::select("*")->where('EmpleadoID', '=', $id)->get();
        $Lineas = LineasTelefonicas::select("*")->where('Disponible', '=', 1)->get();



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

        $data = $request->all();

        $gerencianombre = Gerencia::select("NombreGerencia")->where('GerenciaID', $request->GerenciaEquipoID)->get();

        $data['GerenciaEquipo'] = $gerencianombre[0]->NombreGerencia;

        $inventarioEquipo->update($data);

        return response()->json([
            'equipo' => $inventarioEquipo,
            'success' => true
        ]);
    }

    public function crearequipo($id, Request $request)
    {

        $data = $request->all();
        $data['EmpleadoID'] = $id;

        $gerencianombre = Gerencia::select("NombreGerencia")->where('GerenciaID', $request->GerenciaEquipoID)->get();

        $data['GerenciaEquipo'] = $gerencianombre[0]->NombreGerencia;
      
        $inventarioEquipo = InventarioEquipo::create($data);

        return response()->json([
            'equipo' => $inventarioEquipo, 
            'success' => true
        ]);

        
    }


    /**
     * Remove the specified Inventario from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy(InventarioEquipo $inventario)
    {
        $inventario->delete();
    
        return response()->json([
            'success' => true,
            'message' => 'Equipo eliminado correctamente.',
            'equipo' => $inventario
        ]);
    }


    public function editarinsumo($id, Request $request)
    {


        $inventarioinsumo = InventarioInsumo::where('InventarioID', $id)->first();

        if (!$inventarioinsumo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $data = $request->all();
        $idinsumo = Insumos::select("ID")->where('NombreInsumo', $request->NombreInsumo)->get();
        $data['InsumoID'] = $idinsumo[0]->ID;

        $inventarioinsumo->update($data);

        return response()->json([
            'insumo' => $inventarioinsumo,
            'success' => true
        ]);

    }

    public function crearinsumo($id, Request $request)
    {

        $data = $request->all();
        $data['EmpleadoID'] = $id;
        $idinsumo = Insumos::select("ID")->where('NombreInsumo', $request->NombreInsumo)->get();
        $data['InsumoID'] = $idinsumo[0]->ID;
        $inventarioinsumo = InventarioInsumo::create($data);

        return response()->json([
            'insumo' => $inventarioinsumo, 
            'success' => true
        ]);

    }


    public function destroyInsumo($id)
        {
            // Buscar el insumo por InventarioID
            $inventaInsumo = InventarioInsumo::where('InventarioID', $id)->first();

            // Verificar si el insumo existe
            if (!$inventaInsumo) {
                return response()->json(['error' => 'Insumo no encontrado'], 404);
            }

            // Eliminar el insumo
            $inventaInsumo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Insumo eliminado correctamente.',
                'insumo' => $inventaInsumo
            ]);
        }




    public function editarlinea($id, Request $request)
    {


        $inventariotelf = InventarioLineas::where('InventarioID', $id)->first();

        if (!$inventariotelf) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $data = $request->all();


        $inventariotelf->update($data);

        return response()->json([
            'telefono' => $inventariotelf,
            'success' => true
        ]);
    }

    public function crearlinea($id,$telf, Request $request)
    {


        $linea = LineasTelefonicas::select('lineastelefonicas.NumTelefonico','companiaslineastelefonicas.Compania','planes.NombrePlan','planes.PrecioPlan AS CostoRentaMensual','lineastelefonicas.CuentaPadre','lineastelefonicas.CuentaHija','lineastelefonicas.TipoLinea','lineastelefonicas.FechaFianza','lineastelefonicas.CostoFianza','lineastelefonicas.MontoRenovacionFianza','lineastelefonicas.LineaID','planes.NombrePlan AS PlanTel')
        ->join('planes', 'lineastelefonicas.PlanID', '=', 'planes.ID')
        ->join('companiaslineastelefonicas', 'companiaslineastelefonicas.ID', '=', 'planes.CompaniaID')
        ->where('lineastelefonicas.LineaID', $telf)->get();


        $lineaData = $linea->first();
        $data = $request->all();
        $data['EmpleadoID'] = $id;
        $data['Estado'] = 'True';

        $data = array_merge($data, $lineaData->toArray());

        $empleado = Empleados::select('obras.ObraID','obras.NombreObra AS Obra')
        ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
        ->where('EmpleadoID', $id)->get();

        $empleadoData = $empleado->first();

        $data = array_merge($data, $empleadoData->toArray());

   
        $inventariotelf = InventarioLineas::create($data);
     
        $Lineas = DB::table('lineastelefonicas')
              ->where('LineaID', $telf)
              ->update(['Disponible' => 0]);

        $inventarioinsumo = InventarioInsumo::where('InventarioID', $id)->first();

        return response()->json([
            'telefono' => $inventariotelf, 
            'success' => true
        ]);
    }

    public function destroylinea($id)
    {

         // Buscar el insumo por InventarioID
         $inventarioLineas = InventarioLineas::where('InventarioID', $id)->first();

        $Lineas = DB::table('lineastelefonicas')
        ->where('NumTelefonico', $inventarioLineas->NumTelefonico)
        ->update(['Disponible' => 1]);

         // Verificar si el insumo existe
         if (!$inventarioLineas) {
             return response()->json(['error' => 'Linea no encontrado'], 404);
         }


        $inventarioLineas->delete();
    
        return response()->json([
            'success' => true,
            'message' => 'Linea eliminado correctamente.',
            'telefono' => $inventarioLineas
        ]);
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


        $EquiposAsignados = InventarioEquipo::select("*")->where('EmpleadoID', '=', $id)->get();
        $InsumosAsignados = InventarioInsumo::select("*")->where('EmpleadoID', '=', $id)->get();
        $LineasAsignados = InventarioLineas::select("*")->where('EmpleadoID', '=', $id)->get();
        $Empleados = Empleados::select("*")->get();





        return view('inventarios.transferir')->with([
            'inventario' => $inventario,
            'equiposAsignados' => $EquiposAsignados,
            'insumosAsignados' => $InsumosAsignados,
            'LineasAsignados' => $LineasAsignados,
            'Empleados' => $Empleados

        ]);
    }

    public function formTraspaso(Request $request)
    {

        $equiposSeleccionados = $request->input('equipos', []);
        $insumosSeleccionados = $request->input('insumos', []);
        $lineasSeleccionadas = $request->input('lineas', []);

        $empleadoSeleccionado = (int) $request->input('empleado_id');

  

        if (!empty($equiposSeleccionados)) {
            $equipos = InventarioEquipo::whereIn('InventarioID', $equiposSeleccionados)
                           ->select('InventarioID')
                           ->get();
            foreach ($equipos as $equipo) {
                $equipo->EmpleadoID = $empleadoSeleccionado;
                $equipo->save();
            }
        } else {
            Flash::error('Inventario no encontrado');
        }
        if (!empty($insumosSeleccionados)) {

            $insumos = InventarioInsumo::whereIn('InventarioID', $insumosSeleccionados)
                           ->select('InventarioID')
                           ->get();

            foreach ($insumos as $insumo) {
                $insumo->EmpleadoID = $empleadoSeleccionado;
                $insumo->save();
            }
        } else {
            Flash::error('Inventario no encontrado');
        }
        if (!empty($lineasSeleccionadas)) {

            $lineas = InventarioLineas::whereIn('InventarioID', $lineasSeleccionadas)
                           ->select('InventarioID')
                           ->get();

            foreach ($lineas as $linea) {
                $linea->EmpleadoID = $empleadoSeleccionado;
                $linea->save();
            }
        } else {
            Flash::error('Inventario no encontrado');
        }

        

        
        return back();
    }


    public function cartas($id)
    {

        $empleado = Empleados::select("*")
        ->where('EmpleadoID', '=', $id)
        ->first();
      
        $data = InventarioEquipo::select(
                'InventarioID as id',
                'CategoriaEquipo as categoria',
                'Marca',
                'Caracteristicas',
                'Modelo',
                'NumSerie',
                'FechaAsignacion',
                DB::raw('"EQUIPO" as tipo')
            )
            ->where('EmpleadoID', '=', $id)
            ->get();

        $insumos = InventarioInsumo::select(
                'InventarioID as id',
                'CateogoriaInsumo as categoria',
                'NombreInsumo as Marca',
                'Comentarios as Caracteristicas',
                DB::raw('NULL as Modelo'),
                'NumSerie',
                DB::raw('NULL as FechaAsignacion'),
                DB::raw('"INSUMO" as tipo')
            )
            ->where('EmpleadoID', '=', $id)
            ->where('CateogoriaInsumo', '=', 'ACCESORIOS')
            ->get();

        $telefono = InventarioLineas::
                select(
                    'InventarioID as id',
                    DB::raw('"LINEA TELEFONICA" as categoria'),
                    'Compania as Marca',
                    'PlanTel as Caracteristicas',
                    DB::raw('NULL as Modelo'),
                    DB::raw('NULL as NumSerie'),
                    'NumTelefonico as FechaAsignacion',
                    DB::raw('"TELEFONO" as tipo')
                )
            ->where('empleadoID', '=', $id)
          
            ->get();


        $inventario = $data->concat($insumos)->concat($telefono);


        return view('inventarios.cartas', compact('id','inventario','empleado'));
    }




    public function pdffile(request $request, $id)
    {
      
        $empleadoid= $id;

        $seleccionados = $request->input('inventarioSeleccionado', []);

        $entrega=auth()->id();
     
        $username = User::select('name')
            ->where('id', '=', $entrega)
                    ->first();
       

        if (empty($seleccionados)) {
            return back()->with('error', 'No seleccionaste ningún elemento.');
        }
    
        $datosInventario = [];

        foreach ($seleccionados as $item) {
            list($id, $tipo) = explode('|', $item); 
        
            if ($tipo == "EQUIPO") {
                $equipo = InventarioEquipo::select(
                        'InventarioID as id',
                        'CategoriaEquipo as categoria',
                        'Marca',
                        'Caracteristicas',
                        'Modelo',
                        'NumSerie',
                        'Folio as FechaAsignacion',
                        DB::raw('"EQUIPO" as tipo')
                    )
                    ->where('InventarioID', '=', $id) 
                    ->first(); 
        
                if ($equipo) {
                    $datosInventario[] = $equipo; 
                }
            
            } elseif ($tipo == "INSUMO") {
                $insumo = InventarioInsumo::select(
                        'InventarioID as id',
                        'CateogoriaInsumo as categoria',
                        'NombreInsumo as Marca',
                        'Comentarios as Caracteristicas',
                        DB::raw('NULL as Modelo'),
                        'NumSerie',
                        DB::raw('NULL as FechaAsignacion'),
                        DB::raw('"INSUMO" as tipo')
                    )
                    ->where('InventarioID', '=', $id)
                    ->first();
        
                if ($insumo) {
                    $datosInventario[] = $insumo;
                }
            
            } elseif ($tipo == "TELEFONO") {
                $telefono = InventarioLineas::
                    select(
                        'InventarioID as id',
                        DB::raw('"LINEA TELEFONICA" as categoria'),
                        'Compania as Marca',
                        'PlanTel as Caracteristicas',
                        DB::raw('NULL as Modelo'),
                        DB::raw('NULL as NumSerie'),
                        'NumTelefonico as FechaAsignacion',
                        DB::raw('"TELEFONO" as tipo')
                    )
                    ->where('EmpleadoID', '=', (INT)$empleadoid)
                    ->first();
        
                if ($telefono) {
                    $datosInventario[] = $telefono;
                }
            }
        }
        

       Carbon::setLocale('es'); 
        setlocale(LC_TIME, 'es_ES.UTF-8'); 

        

        $entrega = Empleados::select('empleados.NombreEmpleado','empleados.NumTelefono','puestos.NombrePuesto','unidadesdenegocio.NombreEmpresa','obras.NombreObra','obras.EncargadoDeObra','gerencia.NombreGerencia','unidadesdenegocio.NombreEmpresa')
                ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
                ->join('obras', 'obras.ObraID', '=', 'empleados.ObraID')
                ->join('unidadesdenegocio', 'obras.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
                ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
                ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
                ->where('empleados.EmpleadoID', '=', $empleadoid)
                ->get();

       

        $recibe = Empleados::select('empleados.NombreEmpleado','puestos.NombrePuesto','empleados.NumTelefono')
                ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
                ->where('empleados.NombreEmpleado', '=', $username->name)
                ->get();
        
    


        $data = [
            'fecha' => Carbon::now()->translatedFormat('j \d\e F \d\e Y'),
            'entrega' => $entrega[0]->NombreEmpleado,
            'entregapuesto' => $entrega[0]->NombrePuesto,
            'entreganumero' => $entrega[0]->NumTelefono,
            'recibe' => $recibe[0]->NombreEmpleado,
            'recibepuesto' => $recibe[0]->NombrePuesto,
            'obra' => $entrega[0]->NombreObra,
            'gerencia' =>  $entrega[0]->NombreGerencia,
            'datosInventario' => $datosInventario,
            
        ];


      
        $pdf = PDF::loadView('inventarios.pdffile', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream("Incidencia.pdf", array("Attachment" => false));
    }
}
