<?php

namespace App\Http\Controllers;

use App\Models\ActualizacionCapitalHumano;
use App\Services\SincronizacionCapitalHumano;
use Flash;

class ActualizacionCapitalHumanoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('permission:ver-empleados')->only('index');
        $this->middleware('permission:editar-empleados')->only('traer', 'registrarBaja');
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

        $altas = ActualizacionCapitalHumano::query()
            ->with('empleado')
            ->where('tipo', 'ALTA')
            ->where('estado', 'aplicado')
            ->orderByDesc('id')
            ->limit(120)
            ->get()
            ->unique(function ($alta) {
                return $alta->empleado_ti_id ?: $alta->nombre;
            })
            ->values();

        return view('actualizaciones.index', compact('bajas', 'altas'));
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
