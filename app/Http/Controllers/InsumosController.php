<?php

namespace App\Http\Controllers;

use App\DataTables\InsumosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInsumosRequest;
use App\Http\Requests\UpdateInsumosRequest;
use App\Repositories\InsumosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Insumos;
use Yajra\DataTables\DataTables;
use App\Models\InventarioInsumo;
use App\Models\Categorias;


class InsumosController extends AppBaseController
{
    /** @var InsumosRepository $insumosRepository*/
    private $insumosRepository;

    public function __construct(InsumosRepository $insumosRepo)
    {
        $this->insumosRepository = $insumosRepo;
        $this->middleware('permission:ver-insumos|crear-insumos|editar-insumos|borrar-insumos')->only('index');
        $this->middleware('permission:crear-insumos', ['only' => ['create','store']]);
        $this->middleware('permission:editar-insumos', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-insumos', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Insumos.
     *
     * @param InsumosDataTable $insumosDataTable
     *
     * @return Response
     */
    public function index(InsumosDataTable $insumosDataTable)
    {

        if (request()->ajax()) {
            $unidades = Insumos::join('categorias', 'insumos.CategoriaID', '=', 'categorias.ID')
            ->select([
                'insumos.ID',
                'insumos.NombreInsumo',
                'categorias.Categoria as nombre_categoria',
                'insumos.CostoMensual',
                'insumos.CostoAnual',
                'insumos.Importe',
                'insumos.FrecuenciaDePago',
                'insumos.Observaciones'
            ]);

            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('insumos.datatables_actions', ['id' => $row->ID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

       

        return $insumosDataTable->render('insumos.index');
    }

    public function getInventarioRecords()
    {
        try {
            $insumoId = request('insumo_id');
            
            if (!$insumoId) {
                return response()->json(['records' => []]);
            }

            // Buscar el insumo actual
            $insumo = Insumos::find($insumoId);
            if (!$insumo) {
                return response()->json(['records' => []]);
            }

          
            // Buscar registros que coincidan con el nombre del insumo ACTUAL
            // Esto mostrará cuántos registros se actualizarán si se modifica el insumo
            $records = InventarioInsumo::where('NombreInsumo', $insumo->NombreInsumo)
                ->leftJoin('empleados', 'inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID')
                ->select([
                    'inventarioinsumo.InventarioID as id',
                    'inventarioinsumo.FechaAsignacion',
                    'inventarioinsumo.CateogoriaInsumo',
                    'inventarioinsumo.NombreInsumo',
                    'inventarioinsumo.CostoMensual',
                    'inventarioinsumo.CostoAnual',
                    'inventarioinsumo.FrecuenciaDePago',
                    'inventarioinsumo.Observaciones',
                    'empleados.NombreEmpleado as empleado'
                ])
                ->get()
                ->map(function($record) {
                    return [
                        'id' => $record->id,
                        'nombre_insumo' => $record->NombreInsumo,
                        'categoria' => $record->CateogoriaInsumo,
                        'costo_mensual' => $record->CostoMensual,
                        'costo_anual' => $record->CostoAnual,
                        'frecuencia_pago' => $record->FrecuenciaDePago,
                        'observaciones' => $record->Observaciones,
                        'empleado' => $record->empleado,
                        'fecha_asignacion' => $record->FechaAsignacion ? \Carbon\Carbon::parse($record->FechaAsignacion)->format('d/m/Y') : null
                    ];
                });

            return response()->json(['records' => $records]);
        } catch (\Exception $e) {
            \Log::error('Error en getInventarioRecords: ' . $e->getMessage());
            return response()->json(['records' => [], 'error' => 'Error interno del servidor'], 500);
        }
    }


    /**
     * Show the form for creating a new Insumos.
     *
     * @return Response
     */
    public function create()
    {
        return view('insumos.create');
    }

    /**
     * Store a newly created Insumos in storage.
     *
     * @param CreateInsumosRequest $request
     *
     * @return Response
     */
    public function store(CreateInsumosRequest $request)
    {
        $input = $request->all();

        $insumos = $this->insumosRepository->create($input);

        Flash::success('Insumos saved successfully.');

        return redirect(route('insumos.index'));
    }

    /**
     * Display the specified Insumos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        return view('insumos.show')->with('insumos', $insumos);
    }

    /**
     * Show the form for editing the specified Insumos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $insumos = $this->insumosRepository->find($id);
        
        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        // Pasar los campos con nombres diferentes al blade
        $costoMensual_fields = $insumos->CostoMensual;
        $costoAnual_fields = $insumos->CostoAnual;

        return view('insumos.edit')
            ->with('insumos', $insumos)
            ->with('costoMensual_fields', $costoMensual_fields)
            ->with('costoAnual_fields', $costoAnual_fields);
    }

    /**
     * Update the specified Insumos in storage.
     *
     * @param int $id
     * @param UpdateInsumosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInsumosRequest $request)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

      // Guardar los datos originales para buscar en inventario
        $datosOriginales = $insumos->toArray();

        // Verificar si hay cambios en los campos que se sincronizan con inventario
        $camposSincronizacion = ['CategoriaID', 'NombreInsumo', 'CostoMensual', 'CostoAnual', 'Importe', 'FrecuenciaDePago', 'Observaciones'];
        $hayCambios = false;
        $camposModificados = [];

        foreach ($camposSincronizacion as $campo) {
            if ($datosOriginales[$campo] != $request->input($campo)) {
                $hayCambios = true;
                $camposModificados[] = $campo;
            }
        }
        
        // Actualizar el insumo
        $insumos = $this->insumosRepository->update($request->all(), $id);

        // Obtener los datos actualizados
        $insumosActualizado = $this->insumosRepository->find($id);

        // Si hay cambios, sincronizar automáticamente con inventario usando datos originales
        if ($hayCambios) {
            $this->sincronizarConInventario($insumosActualizado, $datosOriginales);
            $mensaje = 'Insumo actualizado exitosamente y sincronizado automáticamente con inventario.';
        } else {
            $mensaje = 'Insumo actualizado exitosamente.';
        }

        // Si es una petición AJAX, devolver JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $insumosActualizado,
                'cambios_realizados' => $hayCambios,
                'campos_modificados' => $camposModificados
            ]);
        }

        Flash::success($mensaje);
        return redirect(route('insumos.index'));
    }

    private function sincronizarConInventario($insumoActualizado, $datosOriginales)
    {
        // Obtener el nombre de la categoría actualizada
        $categoria = Categorias::find($insumoActualizado->CategoriaID);
        $nombreCategoria = $categoria ? $categoria->Categoria : '';
        
     
        // Buscar registros de inventario usando los datos ORIGINALES del insumo
        // Esto es crucial porque los registros en inventario tienen los datos viejos
        $registrosInventario = InventarioInsumo::where('NombreInsumo', $datosOriginales['NombreInsumo'])
            ->get();
        
       
        
        $registrosActualizados = 0;
        
        // Actualizar cada registro encontrado con los datos NUEVOS del equipo
        foreach ($registrosInventario as $registro) {
            $registro->update([
                'CateogoriaInsumo' => $nombreCategoria,
                'NombreInsumo' => $insumoActualizado->NombreInsumo,
                'CostoMensual' => $insumoActualizado->CostoMensual,
                'CostoAnual' => $insumoActualizado->CostoAnual,
                'Importe' => $insumoActualizado->Importe,
                'FrecuenciaDePago' => $insumoActualizado->FrecuenciaDePago,
                'Observaciones' => $insumoActualizado->Observaciones
            ]);
            $registrosActualizados++;
            
          
        }
    
    }


    /**
     * Remove the specified Insumos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $insumos = $this->insumosRepository->find($id);

        if (empty($insumos)) {
            Flash::error('Insumos not found');

            return redirect(route('insumos.index'));
        }

        $this->insumosRepository->delete($id);

        Flash::success('Insumos deleted successfully.');

        return redirect(route('insumos.index'));
    }
}
