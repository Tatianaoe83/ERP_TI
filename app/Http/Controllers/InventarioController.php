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
use App\Models\Mantenimiento;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use DB;
use PDF;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class InventarioController extends AppBaseController
{
    /** @var InventarioRepository $inventarioRepository*/
    private $inventarioRepository;

    public function __construct(InventarioRepository $inventarioRepo)
    {
        $this->inventarioRepository = $inventarioRepo;
        $this->middleware('permission:transferir-inventario|cartas-inventario|asignar-inventario|ver-inventario')->only('index');
        $this->middleware('permission:asignar-inventario', ['only' => ['edit', 'update']]);
    }

    /**
     * Display a listing of the Inventario.
     *
     * @param InventarioDataTable $inventarioDataTable
     *
     * @return Response
     */
    public function index()
    {
        return view('inventarios.index');
    }

    public function indexVista(Request $request)
    {
        $buscando = $request->filled('nombre') || $request->filled('filtro_inventario');
        $estatusTodos = $request->has('estatus') && $request->estatus === '';

        $unidades = Empleados::join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->when($request->filled('tipo_persona'), function ($q) use ($request) {
                $q->where('empleados.tipo_persona', $request->tipo_persona);
            }, function ($q) use ($buscando, $estatusTodos) {
                if ($buscando || $estatusTodos) {
                    return;
                }

                $q->whereIn('empleados.tipo_persona', ['FISICA', 'REFERENCIADO']);
            })
            ->select([
                'empleados.EmpleadoID',
                'empleados.NombreEmpleado',
                'puestos.NombrePuesto as nombre_puesto',
                'obras.NombreObra as nombre_obra',
                'empleados.NumTelefono',
                'empleados.Correo',
                'empleados.Estado'
            ])
            ->orderBy('empleados.EmpleadoID', 'desc')
            ->when($request->nombre, fn($q) => $q->where(function ($sub) use ($request) {
                $sub->where('empleados.NombreEmpleado', 'like', '%' . $request->nombre . '%')
                    ->orWhere('empleados.NumTelefono', 'like', '%' . $request->nombre . '%')
                    ->orWhere('empleados.Correo', 'like', '%' . $request->nombre . '%');
            }))
            ->when($request->obra, fn($q) => $q->where('obras.NombreObra', 'like', '%' . $request->obra . '%'))
            ->when($request->puesto, fn($q) => $q->where('puestos.NombrePuesto', 'like', '%' . $request->puesto . '%'))
            ->when($request->has('estatus'), function ($q) use ($request) {
                // Treat '2' as "Todos": do not apply any Estado filter
                if ($request->estatus !== '' && $request->estatus !== '2') {
                    $q->where('empleados.Estado', (int) $request->estatus);
                }
            }, function ($q) {
                $q->where('empleados.Estado', 1);
            });


        if ($request->filled('filtro_inventario')) {
            $unidades->where(function ($q) use ($request) {
                $q->whereHas('inventarioequipo', function ($sub) use ($request) {
                    $sub->where('CategoriaEquipo', 'like', "%{$request->filtro_inventario}%")
                        ->orWhere('Marca', 'like', "%{$request->filtro_inventario}%")
                        ->orWhere('Modelo', 'like', "%{$request->filtro_inventario}%")
                        ->orWhere('NumSerie', 'like', "%{$request->filtro_inventario}%")
                        ->orWhere('Folio', 'like', "%{$request->filtro_inventario}%");
                })
                    ->orWhereHas('inventarioinsumo', function ($sub) use ($request) {
                        $sub->where('CateogoriaInsumo', 'like', "%{$request->filtro_inventario}%")
                            ->orWhere('NombreInsumo', 'like', "%{$request->filtro_inventario}%")
                            ->orWhere('NumSerie', 'like', "%{$request->filtro_inventario}%");
                    })
                    ->orWhereHas('inventariolineas', function ($sub) use ($request) {
                        $sub->where('Compania', 'like', "%{$request->filtro_inventario}%")
                            ->orWhere('NumTelefonico', 'like', "%{$request->filtro_inventario}%")
                            ->orWhere('Compania', 'like', "%{$request->filtro_inventario}%")
                            ->orWhere('PlanTel', 'like', "%{$request->filtro_inventario}%");
                    });
            });
        }


        return DataTables::of($unidades)
            ->addColumn('action', function ($row) {
                return view('inventarios.datatables_actions', [
                    'id' => $row->EmpleadoID,
                    'activo' => $row->Estado == 1 || $row->Estado === true,
                    
                ])->render();
            })
            ->editColumn('Estado', function ($row) {
                
                if ($row->Estado == 1 || $row->Estado === true) {
                    return '<span class="badge badge-success" style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Activo</span>';
                }
    
                else {
                    return '<span class="badge badge-danger" style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Inactivo</span>';
                }

            })
            ->rawColumns(['action', 'Estado'])
            ->make(true);
    }

    public function inventario($id)
    {
        $empleadoid = (int)$id;

        $equipos = InventarioEquipo::select(
            'InventarioID as id',
            'CategoriaEquipo as categoria',
            'Marca',
            'Caracteristicas',
            'Modelo',
            'NumSerie',
            'FechaAsignacion',
            'Folio',
            DB::raw('"EQUIPO" as tipo')
        )
            ->where('EmpleadoID', $empleadoid)
            ->get();

        $insumos = InventarioInsumo::select(
            'InventarioID as id',
            'CateogoriaInsumo as categoria',
            'NombreInsumo as Marca',
            'Comentarios as Caracteristicas',
            DB::raw('NULL as Modelo'),
            'NumSerie',
            DB::raw('NULL as FechaAsignacion'),
            DB::raw('NULL as Folio'),
            DB::raw('"INSUMO" as tipo')
        )
            ->where('EmpleadoID', $empleadoid)
            ->get();

        $telefonos = InventarioLineas::select(
            'InventarioID as id',
            DB::raw('"LINEA TELEFONICA" as categoria'),
            'Compania as Marca',
            'PlanTel as Caracteristicas',
            DB::raw('NULL as Modelo'),
            DB::raw('NULL as NumSerie'),
            'NumTelefonico as FechaAsignacion',
            DB::raw('NULL as Folio'),
            DB::raw('"TELEFONO" as tipo')
        )
            ->where('EmpleadoID', $empleadoid)
            ->get();

        $datos = collect($equipos)->merge($insumos)->merge($telefonos);

        return view('empleados.inventario-detalle', compact('datos'));
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
            'empleadoActivo' => (bool) $inventario->Estado,
            // El switch "Presupuestado" sólo aplica a los tipos de persona que alimentan
            // los reportes de presupuesto.
            'permitePresupuestado' => in_array($inventario->tipo_persona, ['FISICA', 'EXTRAORDINARIO']),
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

        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $inventarioEquipo->EmpleadoID)) {
            return $respuesta;
        }

        // Validar unicidad del Folio (excluyendo el registro actual)
        $folio = trim($request->Folio);
        if ($folio) {
            $folioExistente = InventarioEquipo::where('Folio', $folio)
                ->where('InventarioID', '!=', $id)
                ->exists();

            if ($folioExistente) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Folio' => ['El folio "' . $folio . '" ya está registrado en otro equipo. Debe ser único e irrepetible.']]
                ], 422);
            }
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
        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $id)) {
            return $respuesta;
        }

        // Validar unicidad del Folio
        $folio = trim($request->Folio);
        if ($folio) {
            $folioExistente = InventarioEquipo::where('Folio', $folio)->exists();
            if ($folioExistente) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Folio' => ['El folio "' . $folio . '" ya está registrado en otro equipo. Debe ser único e irrepetible.']]
                ], 422);
            }
        }

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
     * Verifica si un Folio ya existe en la base de datos.
     * Usado por el frontend para validación en tiempo real.
     */
    public function verificarFolio(Request $request)
    {
        $folio = trim($request->folio);
        $excluirId = $request->excluir_id; // InventarioID del registro que se está editando

        // Obtener los últimos 3 folios registrados
        $ultimosFolios = InventarioEquipo::whereNotNull('Folio')
            ->where('Folio', '!=', '')
            ->orderBy('InventarioID', 'desc')
            ->limit(3)
            ->pluck('Folio')
            ->toArray();

        if (!$folio) {
            return response()->json([
                'disponible' => true,
                'ultimos_folios' => $ultimosFolios
            ]);
        }

        $query = InventarioEquipo::where('Folio', $folio);

        if ($excluirId) {
            $query->where('InventarioID', '!=', $excluirId);
        }

        $existe = $query->exists();

        return response()->json([
            'disponible' => !$existe,
            'mensaje' => $existe ? 'El folio "' . $folio . '" ya está registrado. Debe ser único e irrepetible.' : 'Folio disponible.',
            'ultimos_folios' => $ultimosFolios
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
        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $inventario->EmpleadoID)) {
            return $respuesta;
        }

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

        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $inventarioinsumo->EmpleadoID)) {
            return $respuesta;
        }

        $data = $request->all();
        $idinsumo = Insumos::select("ID")->where('NombreInsumo', $request->NombreInsumo)->get();
        $data['InsumoID'] = $idinsumo[0]->ID;

        // Limpiar FechaRenovacion: si es un string no-fecha, convertir a null
        $invalidValues = ['Sin asignar', 'Sin asigna', '0000-00-00', ''];
        if (isset($data['FechaRenovacion']) && (in_array($data['FechaRenovacion'], $invalidValues) || empty($data['FechaRenovacion']))) {
            $data['FechaRenovacion'] = null;
        }

        $inventarioinsumo->update($data);

        return response()->json([
            'insumo' => $inventarioinsumo,
            'success' => true
        ]);
    }

    public function crearinsumo($id, Request $request)
    {
        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $id)) {
            return $respuesta;
        }

        $data = $request->all();
        $data['EmpleadoID'] = $id;
        $idinsumo = Insumos::select("ID")->where('NombreInsumo', $request->NombreInsumo)->get();
        $data['InsumoID'] = $idinsumo[0]->ID;
        
        // Limpiar FechaRenovacion: si es un string no-fecha, convertir a null
        $invalidValues = ['Sin asignar', 'Sin asigna', '0000-00-00', ''];
        if (isset($data['FechaRenovacion']) && (in_array($data['FechaRenovacion'], $invalidValues) || empty($data['FechaRenovacion']))) {
            $data['FechaRenovacion'] = null;
        }

        // Si no viene fecha en el request, intentar obtenerla del catálogo
        if (!$request->filled('FechaRenovacion') || $data['FechaRenovacion'] === null) {
            $insumoMaster = Insumos::find($data['InsumoID']);
            if ($insumoMaster && !empty($insumoMaster->FechaRenovacion) && !in_array($insumoMaster->FechaRenovacion, $invalidValues)) {
                $data['FechaRenovacion'] = $insumoMaster->FechaRenovacion;
            }
        }

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

        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $inventaInsumo->EmpleadoID)) {
            return $respuesta;
        }

        // Eliminar el insumo
        $inventaInsumo->delete();

        return response()->json([
            'success' => true,
            'insumo' => $inventaInsumo
        ]);
    }




    public function editarlinea($id, Request $request)
    {
        try {
            $inventariotelf = InventarioLineas::where('InventarioID', $id)->first();

            if (!$inventariotelf) {
                return response()->json(['success' => false, 'message' => 'Registro de telefonía no encontrado.'], 404);
            }

            if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $inventariotelf->EmpleadoID)) {
                return $respuesta;
            }

            $data = $request->all();

            // Limpiar FechaRenovacion: si es un string no-fecha, convertir a null
            $invalidValues = ['Sin asignar', 'Sin asigna', '0000-00-00', ''];
            if (isset($data['FechaRenovacion']) && (in_array($data['FechaRenovacion'], $invalidValues) || empty($data['FechaRenovacion']))) {
                $data['FechaRenovacion'] = null;
            }

            $inventariotelf->update($data);

            return response()->json([
                'telefono' => $inventariotelf,
                'success' => true
            ]);

        } catch (\Exception $e) {
            \Log::error("Error al editar línea asignada: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Ocurrió un error al guardar los datos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearlinea($id, $telf, Request $request)
    {
        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $id)) {
            return $respuesta;
        }

        $linea = LineasTelefonicas::select('obras.NombreObra AS Obra', 'lineastelefonicas.NumTelefonico', 'companiaslineastelefonicas.Compania', 'planes.NombrePlan', 'planes.PrecioPlan AS CostoRentaMensual', 'lineastelefonicas.CuentaPadre', 'lineastelefonicas.CuentaHija', 'lineastelefonicas.TipoLinea', 'lineastelefonicas.FechaFianza', 'lineastelefonicas.CostoFianza', 'lineastelefonicas.MontoRenovacionFianza', 'lineastelefonicas.FechaRenovacion', 'lineastelefonicas.LineaID', 'planes.NombrePlan AS PlanTel')
                ->join('planes', 'lineastelefonicas.PlanID', '=', 'planes.ID')
                ->join('companiaslineastelefonicas', 'companiaslineastelefonicas.ID', '=', 'planes.CompaniaID')
                ->join('obras', 'obras.ObraID', '=', 'lineastelefonicas.ObraID')
            ->where('lineastelefonicas.LineaID', $telf)->get();


        $lineaData = $linea->first();
        $data = $request->all();
        $data['EmpleadoID'] = $id;
        $data['Estado'] = 'True';

        $data = array_merge($data, $lineaData->toArray());

            $empleado = Empleados::select('obras.ObraID', 'obras.NombreObra AS NombreObra')
                ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->where('EmpleadoID', $id)->get();

        $empleadoData = $empleado->first();

        $data = array_merge($data, $empleadoData->toArray());
        $fechaRenovRaw = $request->input('FechaRenovacion', $lineaData->FechaRenovacion);
        
        if ($fechaRenovRaw == 'Sin asignar' || $fechaRenovRaw == 'Sin asigna' || empty($fechaRenovRaw)) {
            $data['FechaRenovacion'] = null;
        } else {
            // Intentamos convertir DD/MM/YYYY a YYYY-MM-DD para SQL
            try {
                $data['FechaRenovacion'] = \Carbon\Carbon::parse(str_replace('/', '-', $fechaRenovRaw))->format('Y-m-d');
            } catch (\Exception $e) {
                $data['FechaRenovacion'] = null; 
            }
        }

        // 2. Limpieza y Formateo de Fecha de Fianza (Evita el error de fianza vacía)
        $fechaFianzaRaw = $request->input('FechaFianza', $lineaData->FechaFianza);
        
        if ($fechaFianzaRaw == 'Sin asignar' || $fechaFianzaRaw == 'Sin asigna' || empty($fechaFianzaRaw)) {
            $data['FechaFianza'] = null;
        } else {
            try {
                $data['FechaFianza'] = \Carbon\Carbon::parse(str_replace('/', '-', $fechaFianzaRaw))->format('Y-m-d');
            } catch (\Exception $e) {
                $data['FechaFianza'] = null;
            }
        }
            
        // Asegurar que los campos de fecha se transfieran correctamente (Prioridad al modal si tiene datos)
        if ($lineaData) {
            // Obtener valores crudos
            $rawFechaFianza = $request->input('FechaFianza', $lineaData->FechaFianza);
            $rawFechaRenov = $request->input('FechaRenovacion', $lineaData->FechaRenovacion);
            
            // Limpiar: si es un string no-fecha, convertir a null
            $invalidValues = ['Sin asignar', 'Sin asigna', '0000-00-00', ''];
            
            if (in_array($rawFechaFianza, $invalidValues) || empty($rawFechaFianza)) {
                $data['FechaFianza'] = null;
            } else {
                try {
                    $data['FechaFianza'] = \Carbon\Carbon::parse(str_replace('/', '-', $rawFechaFianza))->format('Y-m-d');
                } catch (\Exception $e) {
                    $data['FechaFianza'] = null;
                }
            }
            
            if (in_array($rawFechaRenov, $invalidValues) || empty($rawFechaRenov)) {
                $data['FechaRenovacion'] = null;
            } else {
                try {
                    $data['FechaRenovacion'] = \Carbon\Carbon::parse(str_replace('/', '-', $rawFechaRenov))->format('Y-m-d');
                } catch (\Exception $e) {
                    $data['FechaRenovacion'] = null;
                }
            }
            
            $data['MontoRenovacionFianza'] = $request->input('MontoRenovacionFianza', $lineaData->MontoRenovacionFianza);
            $data['CostoFianza'] = $lineaData->CostoFianza;
        }

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
        $inventarioLineas = InventarioLineas::where('InventarioID', $id)->first();

        if (!$inventarioLineas) {
            return response()->json(['error' => 'Linea no encontrado'], 404);
        }

        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $inventarioLineas->EmpleadoID)) {
            return $respuesta;
        }

        DB::table('lineastelefonicas')
            ->where('NumTelefonico', $inventarioLineas->NumTelefonico)
            ->update(['Disponible' => 1]);

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

        if (!$inventario->Estado) {
            Flash::warning('No se puede transferir inventario de un empleado dado de baja.');
            return redirect(route('inventarios.index'));
        }


        $EquiposAsignados = InventarioEquipo::select("*")->where('EmpleadoID', '=', $id)->get();
        $InsumosAsignados = InventarioInsumo::select("*")->where('EmpleadoID', '=', $id)->get();
        $LineasAsignados = InventarioLineas::select("*")->where('EmpleadoID', '=', $id)->get();
        $Empleados = Empleados::select("*")->where('Estado', 1)->get();






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

        $hoy = Carbon::now()->toDateString();

        if (!empty($equiposSeleccionados)) {
            $equipos = InventarioEquipo::whereIn('InventarioID', $equiposSeleccionados)
                ->select('InventarioID', 'FechaAsignacion')
                ->get();
            foreach ($equipos as $equipo) {
                $equipo->EmpleadoID = $empleadoSeleccionado;
                $equipo->FechaAsignacion = $hoy;
                $equipo->save();
            }

            Mantenimiento::whereIn('InventarioID', $equiposSeleccionados)
                ->where('Estatus', 'Pendiente')
                ->update(['EmpleadoID' => $empleadoSeleccionado]);
        } else {
            Flash::error('Inventario no encontrado');
        }
        if (!empty($insumosSeleccionados)) {

            $insumos = InventarioInsumo::whereIn('InventarioID', $insumosSeleccionados)
                ->select('InventarioID', 'FechaAsignacion')
                ->get();

            foreach ($insumos as $insumo) {
                $insumo->EmpleadoID = $empleadoSeleccionado;
                $insumo->FechaAsignacion = $hoy;
                $insumo->save();
            }
        } else {
            Flash::error('Inventario no encontrado');
        }
        if (!empty($lineasSeleccionadas)) {

            $lineas = InventarioLineas::whereIn('InventarioID', $lineasSeleccionadas)
                ->select('InventarioID', 'FechaAsignacion')
                ->get();

            foreach ($lineas as $linea) {
                $linea->EmpleadoID = $empleadoSeleccionado;
                $linea->FechaAsignacion = $hoy;
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

        $telefono = InventarioLineas::select(
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


        return view('inventarios.cartas', compact('id', 'inventario', 'empleado'));
    }




    public function pdffile(request $request, $id)
    {

        $empleadoid = $id;

        $seleccionados = $request->input('inventarioSeleccionado', []);
       

        $entrega = auth()->id();

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
                $telefono = InventarioLineas::select(
                    'InventarioID as id',
                    DB::raw('"LINEA TELEFONICA" as categoria'),
                    'Compania as Marca',
                    'PlanTel as Caracteristicas',
                    DB::raw('NULL as Modelo'),
                    DB::raw('NULL as NumSerie'),
                    'NumTelefonico as FechaAsignacion',
                    DB::raw('"TELEFONO" as tipo')
                )
                    ->where('InventarioID', '=', $id)
                    ->first();

                if ($telefono) {
                    $datosInventario[] = $telefono;
                }
            }
        }


        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8');



        $entrega = Empleados::select('empleados.NombreEmpleado', 'empleados.NumTelefono', 'puestos.NombrePuesto', 'unidadesdenegocio.NombreEmpresa', 'obras.NombreObra', 'obras.EncargadoDeObra', 'gerencia.NombreGerencia', 'unidadesdenegocio.NombreEmpresa')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->join('obras', 'obras.ObraID', '=', 'empleados.ObraID')
            ->join('unidadesdenegocio', 'obras.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
            ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
            ->where('empleados.EmpleadoID', '=', $empleadoid)
            ->get();



        $recibe = Empleados::select('empleados.NombreEmpleado', 'puestos.NombrePuesto', 'empleados.NumTelefono')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->where('empleados.NombreEmpleado', '=', $username->name)
            ->get();




        $data = [
            'fecha' => Carbon::now()->translatedFormat('j \d\e F \d\e Y'),
            'entrega' => $entrega[0]->NombreEmpleado ?? '',
            'entregapuesto' => $entrega[0]->NombrePuesto ?? '',
            'entreganumero' => $entrega[0]->NumTelefono,
            'recibe' => $recibe[0]->NombreEmpleado ?? '',
            'recibepuesto' => $recibe[0]->NombrePuesto ?? '',
            'obra' => $entrega[0]->NombreEmpresa,
            'obraubi' => $entrega[0]->NombreObra,
            'gerencia' =>  $entrega[0]->NombreGerencia,
            'datosInventario' => $datosInventario,

        ];



        $pdf = PDF::loadView('inventarios.pdffile', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream("Responsiva.pdf", array("Attachment" => false));
    }

    public function mantenimiento(request $request, $id)
    {

        $empleadoid = $id;

        $seleccionados = $request->input('inventarioPreven', []);

        $tareas = [
            1 => 'Desarme y ensamble de equipo',
            2 => 'Formateo e instalación del sistema operativo',
            3 => 'Limpieza interna',
            4 => 'Respaldo de información',
            6 => 'Cambio de pasta térmica',
            7 => 'Limpieza de periféricos (Puertos USB, red, etc.)',
            8 => 'Actualizaciones de software',
            9 => 'Eliminación de temporales',
            10 => 'Limpieza de ventiladores',
            11 => 'Limpieza de fuente de poder',
            12 => 'Instalación de software por licencia',
            14 => 'Limpieza del teclado',
            15 => 'Cambio de piezas (Disco duro, tarjeta madre, memoria RAM, cambio de batería, etc.)',
            16 => 'Cambio de pasta térmica en la tarjeta grafica',
            17 => 'Cambio de equipo de computo',
        ];

        $equipo = InventarioEquipo::select(
            DB::raw("CONCAT(Folio,' - ', CategoriaEquipo) AS NombreEq")
        )
            ->where('InventarioID', $request->IdEquipo)
            ->get();


        $entrega = auth()->id();

        $username = User::select('name')
            ->where('id', '=', $entrega)
            ->first();


        if (empty($seleccionados)) {
            return back()->with('error', 'No seleccionaste ningún elemento.');
        }


        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8');





        $entrega = Empleados::select('empleados.NombreEmpleado', 'empleados.NumTelefono', 'puestos.NombrePuesto', 'unidadesdenegocio.NombreEmpresa', 'obras.NombreObra', 'obras.EncargadoDeObra', 'gerencia.NombreGerencia', 'unidadesdenegocio.NombreEmpresa')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->join('obras', 'obras.ObraID', '=', 'empleados.ObraID')
            ->join('unidadesdenegocio', 'obras.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
            ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
            ->where('empleados.EmpleadoID', '=', $empleadoid)
            ->get();



        $recibe = Empleados::select('empleados.NombreEmpleado', 'puestos.NombrePuesto', 'empleados.NumTelefono')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->where('empleados.NombreEmpleado', '=', $username->name)
            ->get();




        $data = [
            'fecha' => Carbon::now()->translatedFormat('j \d\e F \d\e Y'),
            'entrega' => $entrega[0]->NombreEmpleado ?? '',
            'entregapuesto' => $entrega[0]->NombrePuesto ?? '',
            'recibe' => $recibe[0]->NombreEmpleado ?? '',
            'recibepuesto' => $recibe[0]->NombrePuesto ?? '',
            'tareas' => $tareas,
            'seleccionados' => $seleccionados,
            'equipofolio' => $equipo[0]->NombreEq

        ];



        $pdf = PDF::loadView('inventarios.pdfMante', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream("Mantenimiento.pdf", array("Attachment" => false));
    }

    /**
     * Descarga en Excel el inventario asignado de un empleado para el tipo indicado
     * (equipos, insumos o lineas), aplicando el filtro de la pestaña seleccionada.
     */
    public function exportarAsignados($id, $tipo, Request $request)
    {
        $empleado = Empleados::find((int) $id);

        if (!$empleado) {
            Flash::error('Empleado no encontrado');
            return redirect(route('inventarios.index'));
        }

        $tipo = strtolower($tipo);
        $filtro = $request->input('filtro', 'todos');

        if (!in_array($tipo, ['equipos', 'insumos', 'lineas'])) {
            Flash::error('Tipo de inventario no válido');
            return back();
        }

        $aplicarFiltro = function ($query) use ($filtro) {
            if ($filtro === 'presupuestados') {
                $query->where('Presupuestado', 1);
            } elseif ($filtro === 'no_presupuestados') {
                $query->where(function ($q) {
                    $q->where('Presupuestado', 0)->orWhereNull('Presupuestado');
                });
            }

            return $query;
        };

        $siNo = fn($valor) => $valor ? 'Si' : 'No';
        $fecha = fn($valor) => (empty($valor) || in_array($valor, ['Sin asignar', 'Sin asigna', '0000-00-00']))
            ? 'Sin asignar'
            : Carbon::parse($valor)->format('d/m/Y');

        // La columna "Presupuestado" sólo aporta información cuando se exporta "Todos";
        // en las otras pestañas el valor ya está implícito en el filtro.
        $incluirPresupuestado = $filtro === 'todos';

        if ($tipo === 'equipos') {
            $registros = $aplicarFiltro(InventarioEquipo::where('EmpleadoID', $id))->get();

            $encabezados = ['Categoria', 'Marca', 'Caracteristicas', 'Modelo', 'Precio', 'Fecha Asignacion', 'Fecha de Compra', 'Num. Serie', 'Folio', 'Gerencia Equipo', 'Comentarios', 'Mes de pago'];

            $filas = $registros->map(function ($e) use ($fecha, $siNo, $incluirPresupuestado) {
                $fila = [
                    $e->CategoriaEquipo,
                    $e->Marca,
                    $e->Caracteristicas,
                    $e->Modelo,
                    $e->Precio,
                    $fecha($e->FechaAsignacion),
                    $fecha($e->FechaDeCompra),
                    $e->NumSerie,
                    $e->Folio,
                    $e->GerenciaEquipo,
                    $e->Comentarios,
                    $e->MesDePago,
                ];

                if ($incluirPresupuestado) {
                    $fila[] = $siNo($e->Presupuestado);
                }

                return $fila;
            })->toArray();

            $titulo = 'Equipos';
        } elseif ($tipo === 'insumos') {
            $registros = $aplicarFiltro(InventarioInsumo::where('EmpleadoID', $id))->get();

            $encabezados = ['Categoria Insumo', 'Nombre Insumo', 'Costo Mensual', 'Costo Anual', 'Frecuencia de Pago', 'Fecha de Renovacion', 'Observaciones', 'Fecha de Asignacion', 'Num. Serie', 'Comentarios', 'Mes de pago'];

            $filas = $registros->map(function ($i) use ($fecha, $siNo, $incluirPresupuestado) {
                $fila = [
                    $i->CateogoriaInsumo,
                    $i->NombreInsumo,
                    $i->CostoMensual,
                    $i->CostoAnual,
                    $i->FrecuenciaDePago,
                    $fecha($i->FechaRenovacion),
                    $i->Observaciones,
                    $fecha($i->FechaAsignacion),
                    $i->NumSerie,
                    $i->Comentarios,
                    $i->MesDePago,
                ];

                if ($incluirPresupuestado) {
                    $fila[] = $siNo($i->Presupuestado);
                }

                return $fila;
            })->toArray();

            $titulo = 'Insumos';
        } else {
            $registros = $aplicarFiltro(InventarioLineas::where('EmpleadoID', $id))->get();

            $encabezados = ['Num. Tel.', 'Compania', 'Plan', 'Costo Renta Mensual', 'Cuenta Padre', 'Cuenta Hija', 'Tipo Linea', 'Obra', 'Fecha Fianza', 'Costo Fianza', 'Fecha Asignacion', 'Comentario', 'Monto Renovacion Fianza', 'Fecha Renovacion'];

            $filas = $registros->map(function ($l) use ($fecha, $siNo, $incluirPresupuestado) {
                $fila = [
                    $l->NumTelefonico,
                    $l->Compania,
                    $l->PlanTel,
                    $l->CostoRentaMensual,
                    $l->CuentaPadre,
                    $l->CuentaHija,
                    $l->TipoLinea,
                    $l->lineastelefonicas->obras->NombreObra ?? 'Sin asignar',
                    $fecha($l->FechaFianza),
                    $l->CostoFianza,
                    $fecha($l->FechaAsignacion),
                    $l->Comentarios,
                    $l->MontoRenovacionFianza,
                    $fecha($l->FechaRenovacion),
                ];

                if ($incluirPresupuestado) {
                    $fila[] = $siNo($l->Presupuestado);
                }

                return $fila;
            })->toArray();

            $titulo = 'Lineas';
        }

        if ($incluirPresupuestado) {
            $encabezados[] = 'Presupuestado';
        }

        $etiquetaFiltro = [
            'todos' => 'Todos',
            'presupuestados' => 'Presupuestados',
            'no_presupuestados' => 'Asignados',
        ][$filtro] ?? 'Todos';

        $nombreEmpleado = preg_replace('/[^A-Za-z0-9_\- ]/', '', $empleado->NombreEmpleado);
        $nombreArchivo = $titulo . '_' . str_replace(' ', '_', $nombreEmpleado) . '_' . str_replace(' ', '_', $etiquetaFiltro) . '.xlsx';

        return Excel::download(
            new \App\Exports\InventarioAsignadoExport($filas, $encabezados, $titulo),
            $nombreArchivo
        );
    }

    private function respuestaSiEmpleadoInactivo(int $empleadoId)
    {
        $empleado = Empleados::find($empleadoId);

        if (!$empleado || !$empleado->Estado) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden realizar acciones de inventario porque el empleado está dado de baja.',
            ], 422);
        }

        return null;
    }
}
