<?php

namespace App\Http\Controllers;

use App\Models\ActualizacionCapitalHumano;
use App\Models\Empleados;
use App\Models\PresupuestoInterno;
use App\Services\SincronizacionCapitalHumano;
use Flash;
use Illuminate\Http\Request;

class ActualizacionCapitalHumanoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('permission:ver-empleados')->only('index');
        $this->middleware('permission:editar-empleados')->only('traer', 'registrarBaja', 'asignarPresupuesto');
    }

    public function index()
    {
        $bajas = ActualizacionCapitalHumano::query()
            ->with(['empleado' => function ($query) {
                $query->withCount(['inventarioequipo', 'inventarioinsumo', 'inventariolineas']);
            }])
            ->where('tipo', 'BAJA')
            ->where('estado', 'pendiente_inventario')
            ->orderByDesc('fecha')
            ->get();

        $bajas = $bajas->unique(function ($baja) {
            return $baja->empleado_ti_id ?: $baja->nombre;
        })->values();

        $idsConBaja = ActualizacionCapitalHumano::query()
            ->where('tipo', 'BAJA')
            ->whereNotNull('empleado_ti_id')
            ->pluck('empleado_ti_id');

        $altas = ActualizacionCapitalHumano::query()
            ->with('empleado')
            ->where('tipo', 'ALTA')
            ->where('estado', 'aplicado')
            ->when($idsConBaja->isNotEmpty(), function ($query) use ($idsConBaja) {
                $query->where(function ($filtro) use ($idsConBaja) {
                    $filtro->whereNull('empleado_ti_id')
                        ->orWhereNotIn('empleado_ti_id', $idsConBaja);
                });
            })
            ->orderByDesc('id')
            ->limit(120)
            ->get()
            ->filter(function ($alta) {
                return $alta->empleado
                    && (int) $alta->empleado->Estado === 1
                    && empty($alta->empleado->PresupuestoInternoID);
            })
            ->unique(function ($alta) {
                return $alta->empleado_ti_id ?: $alta->nombre;
            })
            ->values();

        $presupuestos = PresupuestoInterno::query()->orderBy('Nombre')->get(['PresupuestoInternoID', 'Nombre']);

        return view('actualizaciones.index', compact('bajas', 'altas', 'presupuestos'));
    }

    public function traer(SincronizacionCapitalHumano $sincronizacion)
    {
        try {
            $resultado = $sincronizacion->traer();
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());

            return redirect()->route('actualizaciones.index');
        }

        Flash::success(
            'Plantilla al día. Se agregaron: '.$resultado['altas']
            .'. Bajas sin inventario, registradas: '.($resultado['bajas_auto'] ?? 0)
            .'. Bajas con inventario por traspasar: '.$resultado['bajas']
            .'. Cambios de lugar: '.$resultado['cambios'].'.'
        );

        return redirect()->route('actualizaciones.index');
    }

    public function asignarPresupuesto(Request $request, $empleado)
    {
        $request->merge([
            'PresupuestoInternoID' => $request->input('PresupuestoInternoID') ?: null,
        ]);
        $datos = $request->validate([
            'PresupuestoInternoID' => 'nullable|integer|exists:presupuestos_internos,PresupuestoInternoID',
        ]);

        $persona = Empleados::query()->findOrFail($empleado);
        $persona->PresupuestoInternoID = $datos['PresupuestoInternoID'] ?? null;
        $persona->save();

        ActualizacionCapitalHumano::query()
            ->where('tipo', 'ALTA')
            ->where('empleado_ti_id', $persona->EmpleadoID)
            ->where('estado', 'aplicado')
            ->update(['estado' => 'atendido']);

        Flash::success('Presupuesto interno guardado para '.$persona->NombreEmpleado.'.');

        return redirect()->route('actualizaciones.index');
    }

    public function registrarBaja($id)
    {
        $actualizacion = ActualizacionCapitalHumano::query()->findOrFail($id);
        $empleado = $actualizacion->empleado;

        if (!$empleado || $actualizacion->tipo !== 'BAJA') {
            Flash::error('Esta baja no se puede registrar en TI.');

            return redirect()->route('actualizaciones.index');
        }

        $pendiente = $empleado->inventarioequipo()->count()
            + $empleado->inventarioinsumo()->count()
            + $empleado->inventariolineas()->count();

        if ($pendiente > 0) {
            Flash::error('Todavía tiene inventario. Primero hay que traspasarlo o reasignarlo.');

            return redirect()->route('actualizaciones.index');
        }

        $empleado->Estado = 0;
        $empleado->save();
        $actualizacion->estado = 'aplicado';
        $actualizacion->resumen = 'Baja registrada en TI. Ya no tenía inventario asignado.';
        $actualizacion->save();

        Flash::success('La baja de '.$empleado->NombreEmpleado.' quedó registrada en TI.');

        return redirect()->route('actualizaciones.index');
    }
}
