<?php

namespace App\Http\Controllers;

use App\Exports\MantenimientosExport;
use App\Models\Mantenimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class MantenimientosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-mantenimientos')->only('index');
        $this->middleware('permission:editar-mantenimientos')->only(['generar', 'marcarRealizado']);
    }

    public function index()
    {
        $aniosDisponibles = Mantenimiento::whereNotNull('AnioProgramacion')
            ->distinct()
            ->orderByDesc('AnioProgramacion')
            ->pluck('AnioProgramacion');

        $gerencias = DB::table('empleados as e')
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
                'g.GerenciaID',
                'g.NombreGerencia',
                DB::raw('COUNT(DISTINCT e.EmpleadoID) as TotalPersonal'),
                DB::raw('COUNT(ie.InventarioID) as TotalEquipos'),
            ])
            ->groupBy('g.GerenciaID', 'g.NombreGerencia')
            ->orderBy('g.NombreGerencia')
            ->get();

        return view('mantenimientos.index', compact('gerencias', 'aniosDisponibles'));
    }

    public function exportarExcel(Request $request)
    {
        $anio = $request->input('anio', 'todos');

        if ($anio !== 'todos' && !is_numeric($anio)) {
            $anio = 'todos';
        }

        $sufijo   = $anio !== 'todos' ? "_{$anio}" : '_todos';
        $archivo  = "mantenimientos{$sufijo}_" . date('d-m-Y') . '.xlsx';

        return Excel::download(new MantenimientosExport($anio), $archivo);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['nullable', 'date'],
            'gerencias_orden' => ['nullable', 'array'],
            'gerencias_orden.*' => ['integer'],
        ]);

        $fechaCursor = $request->filled('fecha_inicio')
            ? Carbon::parse($request->input('fecha_inicio'))
            : Carbon::today();

        if ($fechaCursor->isWeekend()) {
            $fechaCursor = $this->siguienteDiaHabil($fechaCursor);
        }

        $anioProgramacion = $fechaCursor->year;

        if (!Schema::hasColumn('mantenimientos', 'AnioProgramacion')) {
            return redirect()->route('mantenimientos.index')
                ->with('sweetalert_warning', 'Falta actualizar la base de datos. Ejecuta php artisan migrate antes de generar la programación.');
        }

        $existeProgramacion = Mantenimiento::where('AnioProgramacion', $anioProgramacion)->exists();

        if ($existeProgramacion) {
            return redirect()->route('mantenimientos.index')
                ->with('sweetalert_warning', "Ya existe una lista de mantenimientos para el año {$anioProgramacion}. No se generó otra lista.");
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
                'g.GerenciaID',
                'g.NombreGerencia',
                'ie.InventarioID',
                'ie.Folio',
                'ie.FechaDeCompra',
            ])
            ->orderBy('g.NombreGerencia')
            ->orderBy('e.NombreEmpleado')
            ->orderBy('ie.InventarioID')
            ->get();

        $ordenGerencias = collect($request->input('gerencias_orden', []))
            ->map(fn ($gerenciaId) => (int) $gerenciaId)
            ->filter()
            ->unique()
            ->values()
            ->flip();

        if ($ordenGerencias->isNotEmpty()) {
            $registros = $registros
                ->sortBy(function ($registro) use ($ordenGerencias) {
                    return sprintf(
                        '%010d-%s-%010d',
                        $ordenGerencias->get((int) $registro->GerenciaID, PHP_INT_MAX),
                        $registro->NombreEmpleado,
                        $registro->InventarioID
                    );
                })
                ->values();
        }

        if ($registros->isEmpty()) {
            return redirect()->route('mantenimientos.index')
                ->with('sweetalert_warning', 'No se encontraron empleados físicos activos con laptop o PC.');
        }

        DB::transaction(function () use ($registros, $fechaCursor, $anioProgramacion) {
            $fechaProgramada = $fechaCursor->copy();

            foreach ($registros as $fila) {
                $tipo = $this->resolverTipoMantenimiento($fila->FechaDeCompra, $fechaCursor, $fila->InventarioID);
                $fechaProgramada = $this->ajustarDiaHabil($fechaProgramada);

                Mantenimiento::updateOrCreate(
                    [
                        'AnioProgramacion' => $anioProgramacion,
                        'EmpleadoID' => $fila->EmpleadoID,
                        'InventarioID' => $fila->InventarioID,
                        'FechaMantenimiento' => $fechaProgramada->toDateString(),
                    ],
                    [
                        'TipoMantenimiento' => $tipo,
                        'Folio' => $fila->Folio,
                        'FechaDeCompra' => $fila->FechaDeCompra,
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

    private function resolverTipoMantenimiento($fechaDeCompra, Carbon $fechaCorte, int $inventarioId): string
    {
        $ultimoTipo = Mantenimiento::where('InventarioID', $inventarioId)
            ->where('AnioProgramacion', '<', $fechaCorte->year)
            ->orderByDesc('AnioProgramacion')
            ->orderByDesc('FechaMantenimiento')
            ->value('TipoMantenimiento');

        if ($ultimoTipo === 'Profundo') {
            return 'Sencillo';
        }

        if (empty($fechaDeCompra)) {
            return 'Profundo';
        }

        $compra = Carbon::parse($fechaDeCompra);
        return $compra->greaterThan($fechaCorte->copy()->subYear()) ? 'Sencillo' : 'Profundo';
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
