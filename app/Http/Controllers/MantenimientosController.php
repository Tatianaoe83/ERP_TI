<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MantenimientosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-mantenimientos')->only('index');
        $this->middleware('permission:editar-mantenimientos')->only(['generar', 'marcarRealizado']);
    }

    public function index()
    {
        return view('mantenimientos.index');
    }

    public function generar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['nullable', 'date'],
        ]);

        $fechaCursor = $request->filled('fecha_inicio')
            ? Carbon::parse($request->input('fecha_inicio'))
            : Carbon::today();

        if ($fechaCursor->isWeekend()) {
            $fechaCursor = $this->siguienteDiaHabil($fechaCursor);
        }

        $registros = DB::table('empleados as e')
            ->join('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->join('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->join('gerencia as g', 'g.GerenciaID', '=', 'd.GerenciaID')
            ->join('inventarioequipo as ie', 'ie.EmpleadoID', '=', 'e.EmpleadoID')
            ->where('e.Estado', 1)
            ->where('e.tipo_persona', 'FISICA')
            ->whereNull('e.deleted_at')
            ->whereNull('g.deleted_at')
            ->whereIn(DB::raw('UPPER(TRIM(ie.CategoriaEquipo))'), ['LAPTOP', 'PC', 'COMPUTADORA', 'COMPUTADOR'])
            ->select([
                'e.EmpleadoID',
                'e.NombreEmpleado',
                'g.NombreGerencia',
                'ie.InventarioID',
                'ie.Folio',
                'ie.FechaDeCompra',
            ])
            ->orderBy('g.NombreGerencia')
            ->orderBy('e.NombreEmpleado')
            ->get();

        if ($registros->isEmpty()) {
            return redirect()->route('mantenimientos.index')
                ->with('sweetalert_warning', 'No se encontraron empleados físicos activos con laptop o PC.');
        }

        DB::transaction(function () use ($registros, $fechaCursor) {
            $fechaProgramada = $fechaCursor->copy();

            foreach ($registros as $fila) {
                $tipo = $this->resolverTipoMantenimiento($fila->FechaDeCompra);
                $fechaProgramada = $this->ajustarDiaHabil($fechaProgramada);

                Mantenimiento::updateOrCreate(
                    [
                        'EmpleadoID' => $fila->EmpleadoID,
                        'InventarioID' => $fila->InventarioID,
                        'FechaMantenimiento' => $fechaProgramada->toDateString(),
                    ],
                    [
                        'NombreEmpleado' => $fila->NombreEmpleado,
                        'NombreGerencia' => $fila->NombreGerencia,
                        'TipoMantenimiento' => $tipo,
                        'Estatus' => 'Pendiente',
                    ]
                );

                $fechaProgramada = $this->siguienteDiaHabil($fechaProgramada);
            }
        });

        return redirect()->route('mantenimientos.index')
            ->with('sweetalert_success', 'Programación de mantenimientos generada correctamente.');
    }

    public function marcarRealizado(Request $request, Mantenimiento $mantenimiento)
    {
        $request->validate([
            'fecha_realizado' => ['nullable', 'date'],
        ]);

        if ($mantenimiento->Estatus !== 'Realizado') {
            $fechaRealizado = $request->filled('fecha_realizado')
                ? Carbon::parse($request->input('fecha_realizado'))
                : now();

            $mantenimiento->update([
                'Estatus' => 'Realizado',
                'RealizadoPor' => Auth::id(),
                'FechaRealizado' => $fechaRealizado,
            ]);
        }

        return redirect()->route('mantenimientos.index')
            ->with('sweetalert_success', 'Mantenimiento marcado como realizado.');
    }

    private function resolverTipoMantenimiento($fechaDeCompra): string
    {
        if (empty($fechaDeCompra)) {
            return 'Profundo';
        }

        $compra = Carbon::parse($fechaDeCompra);
        return $compra->greaterThanOrEqualTo(Carbon::today()->subYear()) ? 'Sencillo' : 'Profundo';
    }

    private function ajustarDiaHabil(Carbon $fecha): Carbon
    {
        return $fecha->isWeekend() ? $this->siguienteDiaHabil($fecha) : $fecha;
    }

    private function siguienteDiaHabil(Carbon $fecha): Carbon
    {
        $cursor = $fecha->copy()->addDay();
        while ($cursor->isWeekend()) {
            $cursor->addDay();
        }

        return $cursor;
    }
}
