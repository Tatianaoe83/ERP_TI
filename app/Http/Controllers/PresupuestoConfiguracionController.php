<?php

namespace App\Http\Controllers;

use App\Models\PresupuestoConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresupuestoConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-conf-presupuesto');
    }

    public function index()
    {
        $grupos = [];
        foreach (PresupuestoConfiguracion::GRUPOS as $clave => $meta) {
            $seleccionadas = PresupuestoConfiguracion::valores($clave);
            $opciones = PresupuestoConfiguracion::opciones($meta['origen']);

            foreach ($seleccionadas as $valor) {
                $existe = collect($opciones)->contains(
                    fn ($op) => mb_strtoupper($op, 'UTF-8') === mb_strtoupper($valor, 'UTF-8')
                );
                if (! $existe) {
                    $opciones[] = $valor;
                }
            }

            natcasesort($opciones);
            $opciones = array_values($opciones);

            $grupos[$clave] = array_merge($meta, [
                'clave' => $clave,
                'seleccionadas' => $seleccionadas,
                'seleccionadas_upper' => array_map(
                    fn ($v) => mb_strtoupper($v, 'UTF-8'),
                    $seleccionadas
                ),
                'opciones' => $opciones,
            ]);
        }

        return view('presupuesto.configuracion', compact('grupos'));
    }

    public function guardar(Request $request)
    {
        $payload = $request->input('grupos', []);
        if (! is_array($payload)) {
            $payload = [];
        }

        DB::transaction(function () use ($payload, $request) {
            foreach (array_keys(PresupuestoConfiguracion::GRUPOS) as $clave) {
                $valores = $payload[$clave] ?? [];
                if (! is_array($valores)) {
                    $valores = [];
                }

                $extra = trim((string) $request->input('nuevo.' . $clave, ''));
                if ($extra !== '') {
                    $valores[] = $extra;
                }

                $limpios = collect($valores)
                    ->map(fn ($v) => trim((string) $v))
                    ->filter()
                    ->unique(fn ($v) => mb_strtoupper($v, 'UTF-8'))
                    ->values();

                PresupuestoConfiguracion::where('grupo', $clave)->delete();

    foreach ($limpios as $valor) {
                    PresupuestoConfiguracion::create([
                        'grupo' => $clave,
                        'valor' => mb_substr($valor, 0, 150),
                    ]);
                }

                if ($limpios->isEmpty()) {
                    PresupuestoConfiguracion::create([
                        'grupo' => $clave,
                        'valor' => '',
                    ]);
                }
            }
        });

        PresupuestoConfiguracion::flushCache();

        return redirect()
            ->route('presupuesto.configuracion')
            ->with('success', 'La configuración de presupuesto se guardó. Los reportes y KPIs usarán estas categorías.');
    }
}
