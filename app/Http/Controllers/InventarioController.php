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
use App\Models\Planes;
use App\Models\Obras;
use App\Models\UnidadesDeNegocio;
use App\Models\Insumos;
use App\Models\Gerencia;
use App\Models\Equipos;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Helpers\PagoMeses;
use App\Helpers\PresupuestoAsignacion;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use DB;
use PDF;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

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
                'empleados.tipo_persona',
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
                    'tipo_persona' => $row->tipo_persona ?? 'FISICA',
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
            ->editColumn('tipo_persona', function ($row) {
                $tipo = strtoupper((string) ($row->tipo_persona ?? 'FISICA'));

                $map = [
                    'FISICA' => ['label' => 'Física', 'class' => 'inv-tipo-fisica', 'hint' => 'Stock + Extra'],
                    'REFERENCIADO' => ['label' => 'Referenciado', 'class' => 'inv-tipo-referenciado', 'hint' => 'Solo stock'],
                    'EXTRAORDINARIO' => ['label' => 'Extraordinario', 'class' => 'inv-tipo-extraordinario', 'hint' => 'Todo extra'],
                ];

                $meta = $map[$tipo] ?? $map['FISICA'];

                return '<span class="inv-tipo-badge ' . $meta['class'] . '" title="' . e($meta['hint']) . '">'
                    . e($meta['label'])
                    . '</span>'
                    . '<div style="font-size:10px;color:#64748b;margin-top:3px;">' . e($meta['hint']) . '</div>';
            })
            ->rawColumns(['action', 'Estado', 'tipo_persona'])
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

        $LineasAsignados = InventarioLineas::with('lineastelefonicas.obras')->where('EmpleadoID', '=', $id)->get();
        $Lineas = LineasTelefonicas::with(['planes', 'obras'])->where('Disponible', '=', 1)->get();
        $planesLinea = Planes::with('companiaslineastelefonicas')->orderBy('NombrePlan')->get();
        $obrasLinea = Obras::orderBy('NombreObra')->get(['ObraID', 'NombreObra']);
        $tiposLinea = collect(['VOZ', 'DATOS', 'GPS'])
            ->merge(LineasTelefonicas::query()
                ->whereNotNull('TipoLinea')
                ->where('TipoLinea', '!=', '')
                ->distinct()
                ->orderBy('TipoLinea')
                ->pluck('TipoLinea'))
            ->unique()
            ->values();

        return view('inventarios.edit')->with([
            'inventario' => $inventario,
            'empleadoActivo' => (bool) $inventario->Estado,
            // La columna/filtro "Presupuestado" aplica a los tipos de persona que
            // alimentan los reportes de presupuesto.
            'permitePresupuestado' => in_array($inventario->tipo_persona, ['FISICA', 'EXTRAORDINARIO']),
            // En EXTRAORDINARIO todo lo asignado es presupuestado por definición: no
            // se muestra el switch y el valor se fuerza en el servidor.
            'presupuestadoForzado' => $inventario->tipo_persona === 'EXTRAORDINARIO',
            'equiposAsignados' => $EquiposAsignados,
            'equipos' => $Equipos,
            'insumosAsignados' => $InsumosAsignados,
            'insumos' => $Insumos,
            'LineasAsignados' => $LineasAsignados,
            'Lineas' => $Lineas,
            'planesLinea' => $planesLinea,
            'obrasLinea' => $obrasLinea,
            'tiposLinea' => $tiposLinea,
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

        if ($respuesta = $this->validarEquipo($request)) {
            return $respuesta;
        }

        // Validar unicidad del Folio (excluyendo el registro actual)
        $folio = trim((string) $request->Folio);
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
        $data = $this->resolverGerenciaEquipo($data, $request->GerenciaEquipoID);
        $data = $this->vaciarCamposEstimacion($data);
        $data = $this->conservarCamposCatalogoEquipo($data, $inventarioEquipo);

        $data = $this->forzarPresupuestado($data, (int) $inventarioEquipo->EmpleadoID, PresupuestoAsignacion::COLUMNA_EQUIPOS);
        $data = $this->aplicarMesesDePago($data, false);

        $inventarioEquipo->update($data);

        return response()->json([
            'equipo' => $inventarioEquipo,
            'success' => true
        ]);
    }

    /**
     * Validación de alta/edición de equipo.
     *
     * Un equipo propio (tipoEquipo = 3) es del empleado, no de la empresa: no tiene
     * costo, ni folio interno, ni fecha de compra ni mes de pago, así que esos cuatro
     * campos dejan de ser obligatorios. El resto se sigue exigiendo igual.
     *
     * @return \Illuminate\Http\JsonResponse|null  null si todo está bien.
     */
    private function validarEquipo(Request $request)
    {
        $esPropio = (int) $request->input('tipoEquipo', 0) === InventarioEquipo::TIPO_PROPIO;
        $obligatorioSalvoPropio = $esPropio ? 'nullable' : 'required';

        $validador = Validator::make($request->all(), [
            'CategoriaEquipo'  => ['required', 'string', 'max:150'],
            'Marca'            => ['required', 'string', 'max:150'],
            'Caracteristicas'  => ['required', 'string', 'max:255'],
            'Modelo'           => ['required', 'string', 'max:100'],
            'NumSerie'         => ['required', 'string', 'max:100'],
            'FechaAsignacion'  => ['required', 'date'],
            'GerenciaEquipoID' => ['required', 'integer'],
            'Comentarios'      => ['nullable', 'string', 'max:400'],
            'tipoEquipo'       => ['nullable', 'integer', 'in:0,1,2,3'],

            'Precio'           => [$obligatorioSalvoPropio, 'numeric', 'min:0'],
            'Folio'            => [$obligatorioSalvoPropio, 'string', 'max:50'],
            'FechaDeCompra'    => [$obligatorioSalvoPropio, 'date'],
            'MesDePago'        => ['nullable', 'string', 'max:20'],
        ], [
            'required' => 'Este campo es requerido',
            'numeric'  => 'Debe ser un número',
            'date'     => 'Debe ser una fecha válida',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validador->errors(),
            ], 422);
        }

        return null;
    }

    /**
     * Rellena lo que el equipo propio deja vacío: Precio y Folio son NOT NULL en BD,
     * y las fechas/mes vacíos deben guardarse como NULL, no como cadena vacía.
     */
    private function normalizarDatosEquipo(array $data): array
    {
        if ((int) ($data['tipoEquipo'] ?? 0) === InventarioEquipo::TIPO_PROPIO) {
            // Laravel convierte los campos vacíos del formulario en null, así que hay
            // que cubrir ambos casos: Precio y Folio son NOT NULL en la tabla.
            $data['Precio'] = in_array($data['Precio'] ?? null, [null, ''], true) ? 0 : $data['Precio'];
            $data['Folio']  = trim((string) ($data['Folio'] ?? ''));
        }

        foreach (['FechaDeCompra', 'MesDePago', 'FechaAsignacion'] as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] === '') {
                $data[$campo] = null;
            }
        }

        return $data;
    }

    public function crearequipo($id, Request $request)
    {
        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $id)) {
            return $respuesta;
        }

        if ($respuesta = $this->validarEquipo($request)) {
            return $respuesta;
        }

        // Validar unicidad del Folio
        $folio = trim((string) $request->Folio);
        if ($folio) {
            $folioExistente = InventarioEquipo::where('Folio', $folio)->exists();
            if ($folioExistente) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Folio' => ['El folio "' . $folio . '" ya está registrado en otro equipo. Debe ser único e irrepetible.']]
                ], 422);
            }
        }

        $data = $this->normalizarDatosEquipo($request->all());
        $data['EmpleadoID'] = $id;
        $data = $this->resolverGerenciaEquipo($data, $request->GerenciaEquipoID);
        $data = $this->vaciarCamposEstimacion($data);

        $data = $this->forzarPresupuestado($data, (int) $id, PresupuestoAsignacion::COLUMNA_EQUIPOS);
        $data = $this->aplicarMesesDePago($data, false);

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
        $data['CateogoriaInsumo'] = $this->categoriaDesdeCatalogoInsumo((int) $data['InsumoID'], $data['CateogoriaInsumo'] ?? null);

        // Limpiar FechaRenovacion: si es un string no-fecha, convertir a null
        $invalidValues = ['Sin asignar', 'Sin asigna', '0000-00-00', ''];
        if (isset($data['FechaRenovacion']) && (in_array($data['FechaRenovacion'], $invalidValues) || empty($data['FechaRenovacion']))) {
            $data['FechaRenovacion'] = null;
        }

        $data = $this->forzarPresupuestado($data, (int) $inventarioinsumo->EmpleadoID);
        $data = $this->aplicarMesesDePago($data, true);
        $data = $this->vaciarCamposEstimacion($data);

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
        $data['CateogoriaInsumo'] = $this->categoriaDesdeCatalogoInsumo((int) $data['InsumoID'], $data['CateogoriaInsumo'] ?? null);
        
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

        $data = $this->forzarPresupuestado($data, (int) $id);
        $data = $this->aplicarMesesDePago($data, true);
        $data = $this->vaciarCamposEstimacion($data);

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

            $data = $this->datosLineaDesdePlan($data, $request);
            $data = $this->forzarPresupuestado($data, (int) $inventariotelf->EmpleadoID);
            $data = $this->aplicarMesesDePago($data, false);
            $data = $this->vaciarCamposEstimacion($data);

            foreach (['PlanID', 'TipoLinea', 'ObraID', 'NumTelefonico', 'CuentaPadre', 'CuentaHija', 'FechaFianza', 'FechaAsignacion', 'CostoFianza'] as $campo) {
                if (array_key_exists($campo, $data) && $data[$campo] === '') {
                    unset($data[$campo]);
                }
            }

            $modo = PresupuestoAsignacion::normalizar($data['Presupuestado'] ?? $inventariotelf->Presupuestado);
            if ($modo !== PresupuestoAsignacion::EXTRA && empty($inventariotelf->LineaID)) {
                $catalogo = $this->crearLineaEnCatalogo($request, $data);
                if ($catalogo instanceof \Illuminate\Http\JsonResponse) {
                    return $catalogo;
                }
                $data['LineaID'] = $catalogo->LineaID;
                $data['NumTelefonico'] = $catalogo->NumTelefonico;
                $data['CuentaPadre'] = $catalogo->CuentaPadre;
                $data['CuentaHija'] = $catalogo->CuentaHija;
                $data['FechaFianza'] = $catalogo->FechaFianza;
                $data['TipoLinea'] = $catalogo->TipoLinea;
                $data['ObraID'] = $catalogo->ObraID;
                $data['CostoFianza'] = $catalogo->CostoFianza;
                $data['MontoRenovacionFianza'] = $catalogo->MontoRenovacionFianza;
                $data['FechaRenovacion'] = $catalogo->FechaRenovacion;
            }

            $inventariotelf->update($data);
            $inventariotelf->refresh();
            $inventariotelf->load('lineastelefonicas.obras');

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

        $linea = LineasTelefonicas::select('obras.NombreObra AS Obra', 'lineastelefonicas.NumTelefonico', 'companiaslineastelefonicas.Compania', 'planes.NombrePlan', 'planes.PrecioPlan AS CostoRentaMensual', 'lineastelefonicas.CuentaPadre', 'lineastelefonicas.CuentaHija', 'lineastelefonicas.TipoLinea', 'lineastelefonicas.FechaFianza', 'lineastelefonicas.CostoFianza', 'lineastelefonicas.MontoRenovacionFianza', 'lineastelefonicas.FechaRenovacion', 'lineastelefonicas.LineaID', 'lineastelefonicas.PlanID', 'lineastelefonicas.ObraID', 'planes.NombrePlan AS PlanTel')
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

        $data = $this->forzarPresupuestado($data, (int) $id);
        $data['MesDePago'] = $request->input('MesDePago');
        $data = $this->aplicarMesesDePago($data, false);
        $data = $this->vaciarCamposEstimacion($data);

        $inventariotelf = InventarioLineas::create($data);
        $inventariotelf->load('lineastelefonicas.obras');

        $Lineas = DB::table('lineastelefonicas')
            ->where('LineaID', $telf)
            ->update(['Disponible' => 0]);

        $inventarioinsumo = InventarioInsumo::where('InventarioID', $id)->first();

            return response()->json([
                'telefono' => $inventariotelf,
                'success' => true
            ]);
    }

    public function crearlineaextra($id, Request $request)
    {
        if ($respuesta = $this->respuestaSiEmpleadoInactivo((int) $id)) {
            return $respuesta;
        }

        $data = $request->all();
        $data['EmpleadoID'] = $id;
        $data['Estado'] = 'True';
        $data['LineaID'] = null;
        $data['NumTelefonico'] = null;
        $data['CuentaPadre'] = null;
        $data['CuentaHija'] = null;
        $data['FechaFianza'] = null;
        $data['FechaAsignacion'] = null;
        $data['Presupuestado'] = PresupuestoAsignacion::EXTRA;

        $data = $this->datosLineaDesdePlan($data, $request);
        if (empty($data['PlanID']) || empty($data['TipoLinea']) || empty($data['ObraID'])) {
            return response()->json([
                'success' => false,
                'message' => 'La proyección extra requiere plan, tipo de línea y obra. El número y las cuentas se capturan al pasarla a stock.',
            ], 422);
        }

        $data = $this->forzarPresupuestado($data, (int) $id);
        $data['Presupuestado'] = PresupuestoAsignacion::EXTRA;
        $data = $this->aplicarMesesDePago($data, false);
        $data = $this->vaciarCamposEstimacion($data);
        if (! isset($data['CostoFianza']) || $data['CostoFianza'] === null || $data['CostoFianza'] === '') {
            $data['CostoFianza'] = 0;
        }

        $inventariotelf = InventarioLineas::create($data);
        $inventariotelf->load('lineastelefonicas.obras');

        return response()->json([
            'telefono' => $inventariotelf,
            'success' => true
        ]);
    }

    public function cambiarAsignacionMasiva(Request $request)
    {
        $tipo = $request->input('tipo');
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
        // Equipos mandan la modalidad en "tipoEquipo"; insumos y líneas en "Presupuestado".
        $modo = PresupuestoAsignacion::normalizar(
            $request->input($tipo === 'equipo' ? 'tipoEquipo' : 'Presupuestado')
        );

        if (! in_array($tipo, ['equipo', 'insumo', 'linea'], true) || $ids === []) {
            return response()->json(['success' => false, 'message' => 'Seleccione al menos un registro.'], 422);
        }

        if (! in_array($modo, [PresupuestoAsignacion::STOCK, PresupuestoAsignacion::COMPARTIDO], true)) {
            return response()->json(['success' => false, 'message' => 'Solo se puede pasar entre Stock y Compartido.'], 422);
        }

        $modelo = match ($tipo) {
            'equipo' => InventarioEquipo::class,
            'insumo' => InventarioInsumo::class,
            default => InventarioLineas::class,
        };

        $registros = $modelo::whereIn('InventarioID', $ids)->get();
        if ($registros->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No se encontraron los registros.'], 404);
        }

        $empleadoIds = $registros->pluck('EmpleadoID')->unique();
        if ($empleadoIds->count() > 1) {
            return response()->json(['success' => false, 'message' => 'Los registros deben ser del mismo empleado.'], 422);
        }

        $empleadoId = (int) $empleadoIds->first();
        if ($respuesta = $this->respuestaSiEmpleadoInactivo($empleadoId)) {
            return $respuesta;
        }

        $tipoPersona = Empleados::where('EmpleadoID', $empleadoId)->value('tipo_persona');
        if ($tipoPersona === 'EXTRAORDINARIO') {
            return response()->json(['success' => false, 'message' => 'En extraordinario todo es extra; no aplica stock ni compartido.'], 422);
        }
        if ($tipoPersona === 'REFERENCIADO') {
            return response()->json(['success' => false, 'message' => 'El referenciado solo maneja stock.'], 422);
        }

        // Los equipos guardan la modalidad en "tipoEquipo", no en "Presupuestado"
        // (esa columna ni existe en su tabla): usar el nombre fijo aquí habría
        // fallado el UPDATE en silencio o tronado la consulta.
        $columna = $tipo === 'equipo' ? PresupuestoAsignacion::COLUMNA_EQUIPOS : PresupuestoAsignacion::COLUMNA_DEFAULT;

        $actualizados = [];
        $omitidos = 0;

        foreach ($registros as $registro) {
            $actual = PresupuestoAsignacion::normalizar($registro->{$columna});
            if ($actual === PresupuestoAsignacion::EXTRA) {
                $omitidos++;
                continue;
            }

            if ($tipo === 'linea' && empty($registro->LineaID) && $modo === PresupuestoAsignacion::COMPARTIDO) {
                $omitidos++;
                continue;
            }

            // Texto, no entero: tipoEquipo es ENUM, un int lo lee como índice.
            $registro->{$columna} = (string) $modo;
            $registro->save();
            $actualizados[] = (int) $registro->InventarioID;
        }

        return response()->json([
            'success' => true,
            'modo' => $modo,
            'actualizados' => $actualizados,
            'omitidos' => $omitidos,
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

        if ($inventarioLineas->LineaID) {
            DB::table('lineastelefonicas')
                ->where('LineaID', $inventarioLineas->LineaID)
                ->update(['Disponible' => 1]);
        } elseif (!empty($inventarioLineas->NumTelefonico)) {
            DB::table('lineastelefonicas')
                ->where('NumTelefonico', $inventarioLineas->NumTelefonico)
                ->update(['Disponible' => 1]);
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

        if (!$inventario->Estado) {
            Flash::warning('No se puede transferir inventario de un empleado dado de baja.');
            return redirect(route('inventarios.index'));
        }

        if ($redir = $this->bloquearSiExtraordinario((int) $id, 'transferir')) {
            return $redir;
        }

        $EquiposAsignados = InventarioEquipo::query()
            ->where('EmpleadoID', $id);
        PresupuestoAsignacion::aplicarWhere($EquiposAsignados, 'inventario');
        $EquiposAsignados = $EquiposAsignados->get();

        $InsumosAsignados = InventarioInsumo::query()
            ->where('EmpleadoID', $id);
        PresupuestoAsignacion::aplicarWhere($InsumosAsignados, 'inventario');
        $InsumosAsignados = $InsumosAsignados->get();

        $LineasAsignados = InventarioLineas::query()
            ->where('EmpleadoID', $id);
        PresupuestoAsignacion::aplicarWhere($LineasAsignados, 'inventario');
        $LineasAsignados = $LineasAsignados->get();

        $Empleados = Empleados::query()
            ->where('Estado', 1)
            ->whereIn('tipo_persona', ['FISICA', 'EXTRAORDINARIO'])
            ->where('EmpleadoID', '!=', $id)
            ->orderBy('NombreEmpleado')
            ->get();

        return view('inventarios.transferir')->with([
            'inventario' => $inventario,
            'equiposAsignados' => $EquiposAsignados,
            'insumosAsignados' => $InsumosAsignados,
            'LineasAsignados' => $LineasAsignados,
            'Empleados' => $Empleados
        ]);
    }

    public function formTraspaso(Request $request, $inventario)
    {
        $origenId = (int) $inventario;

        if ($redir = $this->bloquearSiExtraordinario($origenId, 'transferir')) {
            return $redir;
        }

        $equiposSeleccionados = array_map('intval', (array) $request->input('equipos', []));
        $insumosSeleccionados = array_map('intval', (array) $request->input('insumos', []));
        $lineasSeleccionadas = array_map('intval', (array) $request->input('lineas', []));
        $empleadoSeleccionado = (int) $request->input('empleado_id');

        if ($empleadoSeleccionado === $origenId) {
            Flash::error('Seleccione un empleado distinto para la transferencia.');
            return back();
        }

        $destino = Empleados::where('EmpleadoID', $empleadoSeleccionado)->first();
        $tipoDestino = strtoupper((string) ($destino->tipo_persona ?? ''));

        if (! $destino || ! $destino->Estado || ! in_array($tipoDestino, ['FISICA', 'EXTRAORDINARIO'], true)) {
            Flash::error('El destino debe ser una persona física o extraordinaria activa.');
            return back();
        }

        $hoy = Carbon::now()->toDateString();
        $destinoExtraordinario = $tipoDestino === 'EXTRAORDINARIO';
        $movidos = 0;

        if ($equiposSeleccionados) {
            $equipos = InventarioEquipo::query()
                ->where('EmpleadoID', $origenId)
                ->whereIn('InventarioID', $equiposSeleccionados);
            PresupuestoAsignacion::aplicarWhere($equipos, 'inventario');
            $equipos = $equipos->get();

            foreach ($equipos as $equipo) {
                $equipo->EmpleadoID = $empleadoSeleccionado;
                $equipo->FechaAsignacion = $hoy;
                if ($destinoExtraordinario) {
                    $equipo->tipoEquipo = (string) PresupuestoAsignacion::EXTRA;
                }
                $equipo->save();
                $movidos++;
            }

            if ($equipos->isNotEmpty()) {
                Mantenimiento::whereIn('InventarioID', $equipos->pluck('InventarioID'))
                    ->where('Estatus', 'Pendiente')
                    ->update(['EmpleadoID' => $empleadoSeleccionado]);
            }
        }

        if ($insumosSeleccionados) {
            $insumos = InventarioInsumo::query()
                ->where('EmpleadoID', $origenId)
                ->whereIn('InventarioID', $insumosSeleccionados);
            PresupuestoAsignacion::aplicarWhere($insumos, 'inventario');

            foreach ($insumos->get() as $insumo) {
                $insumo->EmpleadoID = $empleadoSeleccionado;
                $insumo->FechaAsignacion = $hoy;
                if ($destinoExtraordinario) {
                    $insumo->Presupuestado = PresupuestoAsignacion::EXTRA;
                }
                $insumo->save();
                $movidos++;
            }
        }

        if ($lineasSeleccionadas) {
            $lineas = InventarioLineas::query()
                ->where('EmpleadoID', $origenId)
                ->whereIn('InventarioID', $lineasSeleccionadas);
            PresupuestoAsignacion::aplicarWhere($lineas, 'inventario');

            foreach ($lineas->get() as $linea) {
                $linea->EmpleadoID = $empleadoSeleccionado;
                $linea->FechaAsignacion = $hoy;
                if ($destinoExtraordinario) {
                    $linea->Presupuestado = PresupuestoAsignacion::EXTRA;
                }
                $linea->save();
                $movidos++;
            }
        }

        if ($movidos === 0) {
            Flash::error('No hay elementos de stock o compartido para transferir.');
            return back();
        }

        Flash::success('Se transfirieron ' . $movidos . ' registro(s) de inventario.');

        return redirect(route('inventarios.index'));
    }


    public function cartas($id)
    {
        if ($redir = $this->bloquearSiExtraordinario((int) $id, 'cartas')) {
            return $redir;
        }

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
            ->where('EmpleadoID', '=', $id);
        PresupuestoAsignacion::aplicarWhere($data, 'inventario');
        $data = $data->get();

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
            ->where('CateogoriaInsumo', '=', 'ACCESORIOS');
        PresupuestoAsignacion::aplicarWhere($insumos, 'inventario');
        $insumos = $insumos->get();

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
            ->where('empleadoID', '=', $id);
        PresupuestoAsignacion::aplicarWhere($telefono, 'inventario');
        $telefono = $telefono->get();


        $inventario = $data->concat($insumos)->concat($telefono);


        return view('inventarios.cartas', compact('id', 'inventario', 'empleado'));
    }




    public function pdffile(request $request, $id)
    {
        if ($redir = $this->bloquearSiExtraordinario((int) $id, 'cartas')) {
            return $redir;
        }

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
        if ($redir = $this->bloquearSiExtraordinario((int) $id, 'transferir')) {
            return $redir;
        }

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

        // Los equipos guardan la modalidad en "tipoEquipo"; insumos y líneas en
        // "Presupuestado". Los valores (0 stock, 1 extra, 2 compartido, 3 propio)
        // son los mismos en las tres tablas.
        $aplicarFiltro = function ($query, string $columna = PresupuestoAsignacion::COLUMNA_DEFAULT) use ($filtro) {
            if ($filtro === 'presupuestados') {
                PresupuestoAsignacion::aplicarWhere($query, 'presupuesto', $columna);
            } elseif ($filtro === 'no_presupuestados') {
                PresupuestoAsignacion::aplicarWhere($query, 'inventario', $columna);
            } elseif ($filtro === 'compartidos') {
                $query->where($columna, PresupuestoAsignacion::COMPARTIDO);
            }

            return $query;
        };

        $siNo = fn($valor) => PresupuestoAsignacion::etiqueta($valor);
        $etiquetaTipoEquipo = $siNo;
        $fecha = fn($valor) => (empty($valor) || in_array($valor, ['Sin asignar', 'Sin asigna', '0000-00-00']))
            ? 'Sin asignar'
            : Carbon::parse($valor)->format('d/m/Y');

        // La columna "Presupuestado" sólo aporta información cuando se exporta "Todos";
        // en las otras pestañas el valor ya está implícito en el filtro.
        $incluirPresupuestado = $filtro === 'todos';

        if ($tipo === 'equipos') {
            $registros = $aplicarFiltro(InventarioEquipo::where('EmpleadoID', $id), 'tipoEquipo')->get();

            $encabezados = ['Categoria', 'Marca', 'Caracteristicas', 'Modelo', 'Precio', 'Fecha Asignacion', 'Fecha de Compra', 'Num. Serie', 'Folio', 'Gerencia Equipo', 'Comentarios', 'Mes de pago'];

            $filas = $registros->map(function ($e) use ($fecha, $etiquetaTipoEquipo, $incluirPresupuestado) {
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
                    $fila[] = $etiquetaTipoEquipo($e->tipoEquipo);
                }

                return $fila;
            })->toArray();

            $titulo = 'Equipos';
        } elseif ($tipo === 'insumos') {
            $registros = $aplicarFiltro(InventarioInsumo::where('EmpleadoID', $id))->get();

            $encabezados = ['Categoria Insumo', 'Nombre Insumo', 'Costo Mensual', 'Costo Anual', 'Fecha de Renovacion', 'Observaciones', 'Fecha de Asignacion', 'Num. Serie', 'Comentarios', 'Mes de pago'];

            $filas = $registros->map(function ($i) use ($fecha, $siNo, $incluirPresupuestado) {
                $fila = [
                    $i->CateogoriaInsumo,
                    $i->NombreInsumo,
                    $i->CostoMensual,
                    $i->CostoAnual,
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
            $encabezados[] = $tipo === 'equipos' ? 'Tipo de equipo' : 'Presupuestado';
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

    /**
     * Regla de negocio: todo lo que se asigna a una persona EXTRAORDINARIO es
     * presupuestado, sin importar lo que llegue del formulario. El switch manual
     * sólo existe para personas FISICA.
     */
    private function aplicarMesesDePago(array $data, bool $derivarFrecuencia): array
    {
        $raw = $data['MesDePago'] ?? $data['editMesDePago'] ?? '';
        $data['MesDePago'] = PagoMeses::fromRequest($raw);
        unset($data['editMesDePago'], $data['meses_pago']);

        if ($derivarFrecuencia) {
            $data['FrecuenciaDePago'] = PagoMeses::frecuenciaDerivada($data['MesDePago']);
        }

        return $data;
    }

    private function bloquearSiExtraordinario(int $empleadoId, string $accion = 'transferir')
    {
        $tipo = strtoupper((string) (Empleados::where('EmpleadoID', $empleadoId)->value('tipo_persona') ?? ''));
        if ($tipo !== 'EXTRAORDINARIO') {
            return null;
        }

        Flash::warning(
            $accion === 'cartas'
                ? 'Las personas extraordinarias no generan cartas de entrega.'
                : 'Las personas extraordinarias no pueden transferir inventario.'
        );

        return redirect(route('inventarios.index'));
    }

    private function forzarPresupuestado(array $data, int $empleadoId, string $columna = PresupuestoAsignacion::COLUMNA_DEFAULT): array
    {
        $tipoPersona = Empleados::where('EmpleadoID', $empleadoId)->value('tipo_persona');

        // tipoEquipo es un ENUM('0','1','2','3') en MySQL: si se manda como entero
        // nativo, el motor lo toma como ÍNDICE del enum (1-based), no como el valor,
        // y truena o guarda otra cosa. Por eso siempre en texto, aunque a insumos y
        // líneas (TINYINT) el string no les afecta en nada.
        if ($tipoPersona === 'EXTRAORDINARIO') {
            $data[$columna] = (string) PresupuestoAsignacion::EXTRA;
        } elseif (array_key_exists($columna, $data)) {
            $data[$columna] = (string) PresupuestoAsignacion::normalizar($data[$columna]);
        }

        return $data;
    }

    private function resolverGerenciaEquipo(array $data, $gerenciaId): array
    {
        if (empty($gerenciaId)) {
            $data['GerenciaEquipoID'] = null;
            $data['GerenciaEquipo'] = null;

            return $data;
        }

        $nombre = Gerencia::where('GerenciaID', $gerenciaId)->value('NombreGerencia');
        $data['GerenciaEquipoID'] = $gerenciaId;
        $data['GerenciaEquipo'] = $nombre;

        return $data;
    }

    private function vaciarCamposEstimacion(array $data): array
    {
        foreach ([
            'FechaAsignacion', 'FechaDeCompra', 'FechaRenovacion',
            'NumSerie', 'Folio', 'GerenciaEquipoID', 'GerenciaEquipo',
            'Precio', 'Comentarios',
        ] as $campo) {
            if (array_key_exists($campo, $data) && ($data[$campo] === '' || $data[$campo] === '0')) {
                if ($campo === 'Precio' && $data[$campo] === '0') {
                    $data[$campo] = null;
                } elseif ($data[$campo] === '') {
                    $data[$campo] = null;
                }
            }
        }

        return $data;
    }

    private function conservarCamposCatalogoEquipo(array $data, InventarioEquipo $equipo): array
    {
        foreach (['CategoriaEquipo', 'Marca', 'Caracteristicas', 'Modelo'] as $campo) {
            if (! array_key_exists($campo, $data)) {
                continue;
            }

            $valor = $data[$campo];
            if ($valor === null || trim((string) $valor) === '') {
                $data[$campo] = $equipo->{$campo};
            }
        }

        return $data;
    }

    private function categoriaDesdeCatalogoInsumo(int $insumoId, $fallback = null): ?string
    {
        $master = Insumos::with('categorias')->find($insumoId);
        $nombre = optional(optional($master)->categorias)->Categoria;
        if ($nombre !== null && trim((string) $nombre) !== '') {
            return (string) $nombre;
        }

        return $fallback !== null ? (string) $fallback : null;
    }

    private function datosLineaDesdePlan(array $data, Request $request): array
    {
        $planId = $request->input('PlanID', $data['PlanID'] ?? null);
        if (! $planId) {
            unset($data['PlanID']);

            return $data;
        }

        $plan = Planes::with('companiaslineastelefonicas')->find($planId);
        if (! $plan) {
            return $data;
        }

        $data['PlanID'] = $plan->ID;
        $data['PlanTel'] = $plan->NombrePlan;
        $data['CostoRentaMensual'] = $plan->PrecioPlan;
        $data['Compania'] = optional($plan->companiaslineastelefonicas)->Compania ?? ($data['Compania'] ?? '');

        if ($request->filled('ObraID')) {
            $data['ObraID'] = $request->input('ObraID');
            $data['Obra'] = Obras::where('ObraID', $data['ObraID'])->value('NombreObra');
        }

        if ($request->filled('TipoLinea')) {
            $data['TipoLinea'] = $request->input('TipoLinea');
        }

        if ($request->exists('CostoFianza')) {
            $data['CostoFianza'] = $request->input('CostoFianza') === '' ? null : $request->input('CostoFianza');
        }

        return $data;
    }

    private function crearLineaEnCatalogo(Request $request, array $data)
    {
        $numero = trim((string) $request->input('NumTelefonico', ''));
        $planId = $request->input('PlanID', $data['PlanID'] ?? null);
        $cuentaPadre = trim((string) $request->input('CuentaPadre', ''));
        $cuentaHija = trim((string) $request->input('CuentaHija', ''));
        $tipo = trim((string) ($request->input('TipoLinea') ?: ($data['TipoLinea'] ?? '')));
        $obraId = $request->input('ObraID', $data['ObraID'] ?? null);

        if ($numero === '' || ! $planId || $cuentaPadre === '' || $cuentaHija === '' || $tipo === '' || ! $obraId) {
            return response()->json([
                'success' => false,
                'message' => 'Para pasar la línea a stock o compartido capture número, plan, cuentas, tipo y obra. Se creará en el catálogo como las demás.',
            ], 422);
        }

        if (LineasTelefonicas::where('NumTelefonico', $numero)->exists()) {
            return response()->json([
                'success' => false,
                'errors' => ['NumTelefonico' => ['Ese número telefónico ya existe en el catálogo.']],
            ], 422);
        }

        return LineasTelefonicas::create([
            'NumTelefonico' => $numero,
            'PlanID' => $planId,
            'CuentaPadre' => $cuentaPadre,
            'CuentaHija' => $cuentaHija,
            'TipoLinea' => $tipo,
            'ObraID' => $obraId,
            'FechaFianza' => $request->input('FechaFianza') ?: null,
            'CostoFianza' => $request->input('CostoFianza') ?: ($data['CostoFianza'] ?? 0),
            'Activo' => 1,
            'Disponible' => 0,
            'MontoRenovacionFianza' => $request->input('MontoRenovacionFianza') ?: null,
            'FechaRenovacion' => $request->input('FechaRenovacion') ?: null,
        ]);
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
