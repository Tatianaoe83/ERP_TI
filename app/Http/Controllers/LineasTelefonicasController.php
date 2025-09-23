<?php

namespace App\Http\Controllers;

use App\DataTables\LineasTelefonicasDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateLineasTelefonicasRequest;
use App\Http\Requests\UpdateLineasTelefonicasRequest;
use App\Repositories\LineasTelefonicasRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\LineasTelefonicas;
use App\Models\InventarioLineas;
use App\Models\Planes;
use App\Models\Obras;
use App\Models\CompaniasLineasTelefonicas;
use Illuminate\Database\QueryException;
use Laracasts\Flash\Flash as FlashFlash;
use Yajra\DataTables\DataTables;

class LineasTelefonicasController extends AppBaseController
{
    /** @var LineasTelefonicasRepository $lineasTelefonicasRepository*/
    private $lineasTelefonicasRepository;

    public function __construct(LineasTelefonicasRepository $lineasTelefonicasRepo)
    {
        $this->lineasTelefonicasRepository = $lineasTelefonicasRepo;
        $this->middleware('permission:ver-Lineastelefonicas|crear-Lineastelefonicas|editar-Lineastelefonicas|borrar-Lineastelefonicas')->only('index');
        $this->middleware('permission:crear-Lineastelefonicas', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-Lineastelefonicas', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-Lineastelefonicas', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the LineasTelefonicas.
     *
     * @param LineasTelefonicasDataTable $lineasTelefonicasDataTable
     *
     * @return Response
     */
    public function index(LineasTelefonicasDataTable $lineasTelefonicasDataTable)
    {
        if (request()->ajax()) {
            $unidades = LineasTelefonicas::join('obras', 'obras.ObraID', '=', 'lineastelefonicas.ObraID')
                ->join('planes', 'planes.ID', '=', 'lineastelefonicas.PlanID')
                ->select([
                    'lineastelefonicas.LineaID',
                    'lineastelefonicas.NumTelefonico',
                    'planes.NombrePlan as nombre_plan',
                    'lineastelefonicas.CuentaPadre',
                    'lineastelefonicas.CuentaHija',
                    'lineastelefonicas.TipoLinea',
                    'obras.NombreObra as nombre_obra',
                    'lineastelefonicas.FechaFianza',
                    'lineastelefonicas.CostoFianza',
                    'lineastelefonicas.Disponible',
                    'lineastelefonicas.MontoRenovacionFianza'

                ]);

            return DataTables::of($unidades)
                ->addColumn('action', function ($row) {
                    return view('lineas_telefonicas.datatables_actions', ['id' => $row->LineaID])->render();
                })
                ->addColumn('estado_disponibilidad', function ($row) {
                    if ($row->Disponible == 1) {
                        return '<span class="badge badge-success" style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Disponible</span>';
                    } else {
                        return '<span class="badge badge-danger" style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Asignada</span>';
                    }
                })
                ->rawColumns(['action', 'estado_disponibilidad'])
                ->make(true);
        }


        return $lineasTelefonicasDataTable->render('lineas_telefonicas.index');
    }

    public function getInventarioRecords()
    {
        try {
            $lineaId = request('linea_id');
            
            if (!$lineaId) {
                return response()->json(['records' => []]);
            }

            // Buscar la línea actual
            $linea = LineasTelefonicas::find($lineaId);
            if (!$linea) {
                return response()->json(['records' => []]);
            }
            
            // Buscar registros de inventario que coincidan con el número telefónico
            $records = InventarioLineas::where('NumTelefonico', $linea->NumTelefonico)
                ->leftJoin('empleados', 'inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID')
                ->leftJoin('obras', 'inventariolineas.ObraID', '=', 'obras.ObraID')
                ->select([
                    'inventariolineas.InventarioID as id',
                    'inventariolineas.NumTelefonico',
                    'inventariolineas.Compania',
                    'inventariolineas.PlanTel',
                    'inventariolineas.CostoRentaMensual',
                    'inventariolineas.CuentaPadre',
                    'inventariolineas.CuentaHija',
                    'inventariolineas.TipoLinea',
                    'inventariolineas.Obra',
                    'inventariolineas.FechaFianza',
                    'inventariolineas.CostoFianza',
                    'inventariolineas.FechaAsignacion',
                    'inventariolineas.Estado',
                    'inventariolineas.Comentarios',
                    'inventariolineas.MontoRenovacionFianza',
                    'empleados.NombreEmpleado as empleado',
                    'obras.NombreObra as obra'
                ])
                ->get()
                ->map(function($record) {
                    return [
                        'id' => $record->id,
                        'num_telefonico' => $record->NumTelefonico,
                        'compania' => $record->Compania,
                        'plan_tel' => $record->PlanTel,
                        'costo_renta_mensual' => $record->CostoRentaMensual,
                        'cuenta_padre' => $record->CuentaPadre,
                        'cuenta_hija' => $record->CuentaHija,
                        'tipo_linea' => $record->TipoLinea,
                        'obra' => $record->obra,
                        'fecha_fianza' => $record->FechaFianza ? \Carbon\Carbon::parse($record->FechaFianza)->format('d/m/Y') : null,
                        'costo_fianza' => $record->CostoFianza,
                        'fecha_asignacion' => $record->FechaAsignacion ? \Carbon\Carbon::parse($record->FechaAsignacion)->format('d/m/Y') : null,
                        'estado' => $record->Estado,
                        'comentarios' => $record->Comentarios,
                        'monto_renovacion_fianza' => $record->MontoRenovacionFianza,
                        'empleado' => $record->empleado
                    ];
                });

            return response()->json(['records' => $records]);
        } catch (\Exception $e) {
            \Log::error('Error en getInventarioRecords líneas: ' . $e->getMessage());
            return response()->json(['records' => [], 'error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Show the form for creating a new LineasTelefonicas.
     *
     * @return Response
     */
    public function create()
    {
        return view('lineas_telefonicas.create');
    }

    /**
     * Store a newly created LineasTelefonicas in storage.
     *
     * @param CreateLineasTelefonicasRequest $request
     *
     * @return Response
     */
    public function store(CreateLineasTelefonicasRequest $request)
    {
        $input = $request->all();

        try {
            $lineasTelefonicas = $this->lineasTelefonicasRepository->create($input);
            Flash::success('Linea telefonica guardada correctamente');
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                session()->flash('swal', [
                    'icon' => 'error',
                    'title' => 'Duplicado',
                    'text' => 'El número telefónico ya existe.'
                ]);
                return redirect()->back()->withInput();
            }
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Error al guardar la línea: ' . $e->getMessage()
            ]);
            return redirect()->back()->withInput();
        }

        return redirect(route('lineasTelefonicas.index'));
    }

    /**
     * Display the specified LineasTelefonicas.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        return view('lineas_telefonicas.show')->with('lineasTelefonicas', $lineasTelefonicas);
    }

    /**
     * Show the form for editing the specified LineasTelefonicas.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        return view('lineas_telefonicas.edit')->with('lineasTelefonicas', $lineasTelefonicas);
    }

    /**
     * Update the specified LineasTelefonicas in storage.
     *
     * @param int $id
     * @param UpdateLineasTelefonicasRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateLineasTelefonicasRequest $request)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        // Guardar los datos originales para buscar en inventario
        $datosOriginales = $lineasTelefonicas->toArray();

        // Verificar si hay cambios en los campos que se sincronizan con inventario
        $camposSincronizacion = ['PlanID', 'CuentaPadre', 'CuentaHija', 'TipoLinea', 'ObraID', 'FechaFianza', 'CostoFianza', 'MontoRenovacionFianza','NumTelefonico','Activo'];
        $hayCambios = false;
        $camposModificados = [];

        foreach ($camposSincronizacion as $campo) {
            if ($datosOriginales[$campo] != $request->input($campo)) {
                $hayCambios = true;
                $camposModificados[] = $campo;
            }
        }

        // Verificar cambios indirectos en el plan (compañía, nombre, precio)
        $planOriginal = null;
        $planNuevo = null;
        
        if ($datosOriginales['PlanID']) {
            $planOriginal = Planes::find($datosOriginales['PlanID']);
        }
        
        if ($request->input('PlanID')) {
            $planNuevo = Planes::find($request->input('PlanID'));
        }

        // Verificar si cambió el plan o si el plan actual cambió su compañía
        $cambioEnPlan = false;
        if ($planOriginal && $planNuevo) {
            // Verificar si cambió el plan
            if ($planOriginal->ID != $planNuevo->ID) {
                $cambioEnPlan = true;
                $camposModificados[] = 'PlanID';
            }
            // Verificar si el plan actual cambió su compañía, nombre o precio
            elseif ($planOriginal->CompaniaID != $planNuevo->CompaniaID || 
                    $planOriginal->NombrePlan != $planNuevo->NombrePlan || 
                    $planOriginal->PrecioPlan != $planNuevo->PrecioPlan) {
                $cambioEnPlan = true;
                $camposModificados[] = 'PlanData';
            }
        } elseif ($planOriginal || $planNuevo) {
            // Si uno es null y el otro no, hay cambio
            $cambioEnPlan = true;
            $camposModificados[] = 'PlanID';
        }

        if ($cambioEnPlan) {
            $hayCambios = true;
        }
        
        // Actualizar la línea telefónica
        $lineasTelefonicas = $this->lineasTelefonicasRepository->update($request->all(), $id);

        // Obtener los datos actualizados
        $lineaActualizada = $this->lineasTelefonicasRepository->find($id);

        // Si hay cambios, sincronizar automáticamente con inventario usando datos originales
        if ($hayCambios) {
            \Log::info("Cambios detectados en línea {$lineaActualizada->NumTelefonico}: " . implode(', ', $camposModificados));
            $this->sincronizarConInventario($lineaActualizada, $datosOriginales);
            $mensaje = 'Línea telefónica actualizada exitosamente y sincronizada automáticamente con inventario.';
        } else {
            $mensaje = 'Línea telefónica actualizada exitosamente.';
        }

        // Si es una petición AJAX, devolver JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $lineaActualizada,
                'cambios_realizados' => $hayCambios,
                'campos_modificados' => $camposModificados
            ]);
        }

        Flash::success($mensaje);
        return redirect(route('lineasTelefonicas.index'));
    }

    private function sincronizarConInventario($lineaActualizada, $datosOriginales)
    {
        try {
            // Obtener información del plan actualizado
            $plan = Planes::find($lineaActualizada->PlanID);
            $nombrePlan = $plan ? $plan->NombrePlan : '';
            $precioPlan = $plan ? $plan->PrecioPlan : 0;
            
            // Asegurar que el nombre del plan esté en UTF-8
            $nombrePlan = mb_convert_encoding($nombrePlan, 'UTF-8', 'auto');
            
            // Obtener información de la obra actualizada
            $obra = Obras::find($lineaActualizada->ObraID);
            $nombreObra = $obra ? $obra->NombreObra : '';

            // Obtener información de la compañía del plan actualizado
            $nombreCompania = '';
            if ($plan && $plan->CompaniaID) {
                $compania = CompaniasLineasTelefonicas::find($plan->CompaniaID);
                $nombreCompania = $compania ? $compania->Compania : '';
            }
            
            // Asegurar que el nombre de la obra esté en UTF-8
            $nombreObra = mb_convert_encoding($nombreObra, 'UTF-8', 'auto');
            
            // Buscar registros de inventario usando los datos ORIGINALES de la línea
            $registrosInventario = InventarioLineas::where('NumTelefonico', $datosOriginales['NumTelefonico'])
                ->get();
            
            $registrosActualizados = 0;
            
            // Actualizar cada registro encontrado con los datos NUEVOS de la línea
            foreach ($registrosInventario as $registro) {
                // Determinar el estado basado en los campos de la línea
                $estado = $lineaActualizada->Activo ? 'Activo' : 'Inactivo';
                if (!$lineaActualizada->Disponible) {
                    $estado = 'Asignado';
                }
                
                $registro->update([
                    'NumTelefonico' => $lineaActualizada->NumTelefonico,
                    'PlanTel' => $nombrePlan,
                    'Compania' => $nombreCompania,
                    'CostoRentaMensual' => $precioPlan,
                    'CuentaPadre' => $lineaActualizada->CuentaPadre,
                    'CuentaHija' => $lineaActualizada->CuentaHija,
                    'TipoLinea' => $lineaActualizada->TipoLinea,
                    'ObraID' => $lineaActualizada->ObraID,
                    'Obra' => $nombreObra,
                    'FechaFianza' => $lineaActualizada->FechaFianza,
                    'CostoFianza' => $lineaActualizada->CostoFianza,
                    'MontoRenovacionFianza' => $lineaActualizada->MontoRenovacionFianza,
                    
                ]);
                $registrosActualizados++;
            }
            
            \Log::info("Sincronización de línea completada: {$registrosActualizados} registros de inventario actualizados para línea {$lineaActualizada->NumTelefonico}. Plan: {$nombrePlan}, Compañía: {$nombreCompania}, Obra: {$nombreObra}");
            
        } catch (\Exception $e) {
            \Log::error('Error en sincronización de línea con inventario: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove the specified LineasTelefonicas from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $lineasTelefonicas = $this->lineasTelefonicasRepository->find($id);

        if (empty($lineasTelefonicas)) {
            Flash::error('Lineas Telefonicas not found');

            return redirect(route('lineasTelefonicas.index'));
        }

        $this->lineasTelefonicasRepository->delete($id);

        Flash::success('Lineas Telefonicas deleted successfully.');

        return redirect(route('lineasTelefonicas.index'));
    }
}
