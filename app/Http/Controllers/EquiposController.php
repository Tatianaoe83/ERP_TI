<?php

namespace App\Http\Controllers;

use App\DataTables\EquiposDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateEquiposRequest;
use App\Http\Requests\UpdateEquiposRequest;
use App\Repositories\EquiposRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Equipos;
use App\Models\InventarioEquipo;
use App\Models\Categorias;
use Yajra\DataTables\DataTables;

class EquiposController extends AppBaseController
{
    /** @var EquiposRepository $equiposRepository*/
    private $equiposRepository;

    public function __construct(EquiposRepository $equiposRepo)
    {
        $this->equiposRepository = $equiposRepo;
        $this->middleware('permission:ver-equipos|crear-equipos|editar-equipos|borrar-equipos')->only('index');
        $this->middleware('permission:crear-equipos', ['only' => ['create','store']]);
        $this->middleware('permission:editar-equipos', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-equipos', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Equipos.
     *
     * @param EquiposDataTable $equiposDataTable
     *
     * @return Response
     */
    public function index(EquiposDataTable $equiposDataTable)
    {
        if (request()->ajax()) {
            $unidades = Equipos::join('categorias', 'equipos.CategoriaID', '=', 'categorias.ID')
            ->select([
                'equipos.ID',
                'categorias.Categoria as categoria_name',
                'equipos.Marca',
                'equipos.Caracteristicas',
                'equipos.Modelo',
                'equipos.Precio'
            ]);

            
            return DataTables::of($unidades)
                ->addColumn('action', function($row){
                    return view('equipos.datatables_actions', ['id' => $row->ID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $equiposDataTable->render('equipos.index');
    }

    /**
     * Get statistics for equipos dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get inventario records for a specific equipo
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventarioRecords()
    {
        $equipoId = request('equipo_id');
        
        
        if (!$equipoId) {
           
            return response()->json(['records' => []]);
        }

        // Buscar el equipo actual
        $equipo = Equipos::find($equipoId);
        if (!$equipo) {
         
            return response()->json(['records' => []]);
        }
        // Buscar registros que coincidan con la marca y modelo ACTUALES del equipo
        // Esto mostrará cuántos registros se actualizarán si se modifica el equipo
        $records = InventarioEquipo::where('Marca', $equipo->Marca)
            ->where('Modelo', $equipo->Modelo)
            ->leftJoin('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'inventarioequipo.InventarioID as id',
                'inventarioequipo.FechaAsignacion',
                'inventarioequipo.Marca',
                'inventarioequipo.Modelo',
                'inventarioequipo.CategoriaEquipo',
                'inventarioequipo.Precio',
                'empleados.NombreEmpleado as empleado'
            ])
            ->get()
            ->map(function($record) {
                return [
                    'id' => $record->id,
                    'marca' => $record->Marca,
                    'modelo' => $record->Modelo,
                    'categoria' => $record->CategoriaEquipo,
                    'precio' => $record->Precio,
                    'empleado' => $record->empleado,
                    'fecha_asignacion' => $record->FechaAsignacion ? \Carbon\Carbon::parse($record->FechaAsignacion)->format('d/m/Y') : null
                ];
            });


        return response()->json(['records' => $records]);
    }

    /**
     * Show the form for creating a new Equipos.
     *
     * @return Response
     */
    public function create()
    {
        return view('equipos.create');
    }

    /**
     * Store a newly created Equipos in storage.
     *
     * @param CreateEquiposRequest $request
     *
     * @return Response
     */
    public function store(CreateEquiposRequest $request)
    {
        $input = $request->all();

        $equipos = $this->equiposRepository->create($input);

        Flash::success('Equipos saved successfully.');

        return redirect(route('equipos.index'));
    }

    /**
     * Display the specified Equipos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        return view('equipos.show')->with('equipos', $equipos);
    }

    /**
     * Show the form for editing the specified Equipos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        return view('equipos.edit')->with('equipos', $equipos);
    }

    /**
     * Update the specified Equipos in storage.
     *
     * @param int $id
     * @param UpdateEquiposRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateEquiposRequest $request)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        // Guardar los datos originales para buscar en inventario
        $datosOriginales = $equipos->toArray();
        
        // Verificar si hay cambios en los campos que se sincronizan con inventario
        $camposSincronizacion = ['CategoriaID', 'Marca', 'Caracteristicas', 'Modelo', 'Precio'];
        $hayCambios = false;
        $camposModificados = [];
        
        foreach ($camposSincronizacion as $campo) {
            if ($datosOriginales[$campo] != $request->input($campo)) {
                $hayCambios = true;
                $camposModificados[] = $campo;
            }
        }
           
        // Actualizar el equipo
        $equipos = $this->equiposRepository->update($request->all(), $id);
        
        // Obtener los datos actualizados
        $equiposActualizado = $this->equiposRepository->find($id);
        
        // Si hay cambios, sincronizar automáticamente con inventario usando datos originales
        if ($hayCambios) {
            $this->sincronizarConInventario($equiposActualizado, $datosOriginales);
            $mensaje = 'Equipo actualizado exitosamente y sincronizado automáticamente con inventario.';
        } else {
            $mensaje = 'Equipo actualizado exitosamente.';
        }

        // Si es una petición AJAX, devolver JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $equiposActualizado,
                'cambios_realizados' => $hayCambios,
                'campos_modificados' => $camposModificados
            ]);
        }

        Flash::success($mensaje);
        return redirect(route('equipos.index'));
    }
    
    /**
     * Sincronizar los datos del equipo con la tabla de inventario
     *
     * @param Equipos $equipoActualizado
     * @param array $datosOriginales
     * @return void
     */
    private function sincronizarConInventario($equipoActualizado, $datosOriginales)
    {
        // Obtener el nombre de la categoría actualizada
        $categoria = Categorias::find($equipoActualizado->CategoriaID);
        $nombreCategoria = $categoria ? $categoria->Categoria : '';
        
     
        // Buscar registros de inventario usando los datos ORIGINALES del equipo
        // Esto es crucial porque los registros en inventario tienen los datos viejos
        $registrosInventario = InventarioEquipo::where('Marca', $datosOriginales['Marca'])
            ->where('Modelo', $datosOriginales['Modelo'])
            ->get();
        
       
        
        $registrosActualizados = 0;
        
        // Actualizar cada registro encontrado con los datos NUEVOS del equipo
        foreach ($registrosInventario as $registro) {
            $registro->update([
                'CategoriaEquipo' => $nombreCategoria,
                'Marca' => $equipoActualizado->Marca,
                'Caracteristicas' => $equipoActualizado->Caracteristicas,
                'Modelo' => $equipoActualizado->Modelo,
                'Precio' => $equipoActualizado->Precio
            ]);
            $registrosActualizados++;
            
          
        }
    
    }

    /**
     * Remove the specified Equipos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $equipos = $this->equiposRepository->find($id);

        if (empty($equipos)) {
            Flash::error('Equipos not found');

            return redirect(route('equipos.index'));
        }

        $this->equiposRepository->delete($id);

        Flash::success('Equipos deleted successfully.');

        return redirect(route('equipos.index'));
    }
}
