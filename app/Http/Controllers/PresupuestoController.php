<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gerencia;
use App\Models\Gerencias_usuarios;
use App\Models\PresupuestoInterno;
use App\Helpers\PresupuestoHelper;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;



class PresupuestoController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:ver-presupuesto')->only('index');

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $id = auth()->id();

        $usuariosgeren = DB::table('gerencias_usuarios')
            ->where('users_id', $id)
            ->pluck('GerenciaID')
            ->toArray();

        if (count($usuariosgeren) > 0) {
            $genusuarios = Gerencia::join("gerencias_usuarios", "gerencias_usuarios.GerenciaID", "=", "gerencia.GerenciaID")
                ->select('gerencia.*')
                ->where('gerencias_usuarios.users_id', $id)
                ->get();
        } else {
            $genusuarios = Gerencia::all();
        }

        $presupuestosInternos = PresupuestoInterno::visiblesPara(auth()->id());

        return view('presupuesto.index', compact('genusuarios', 'presupuestosInternos'));


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Blog $blog)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Blog $blog)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Blog $blog)
    {

    }


    public function descargar(request $request)
    {
        $data = PresupuestoHelper::datosReporte((int) $request->GerenciaID, (string) $request->tipo, $request->input('modo', 'presupuesto'));

        $nombreGerencia = $data['GerenciaTb']->NombreGerencia ?? 'Sin_Gerencia';
        $prefijoArchivo = $data['modo'] === 'inventario' ? 'Reporte_Inventario' : 'Reporte_Presupuesto';
        $nombreArchivo = $prefijoArchivo . '_' . $nombreGerencia . '_' . ($request->tipo == 'mens' ? 'Mensual' : 'Anual');

        if ($request->submitbutton == 'pdf') {
            $pdf = PDF::loadView('presupuesto.reporte', $data);
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();
            return $pdf->stream($nombreArchivo . '.pdf');
        }

        return Excel::download(new ReportExport($request->GerenciaID, $request->tipo, $data['modo']), $nombreArchivo . '.xlsx');
    }

    public function verificarFechas(Request $request)
    {
        try {
            $gerenciaId = $request->GerenciaID;

            // Año siguiente
            $anioSiguiente = now()->year + 1;

            // 1. CONTEO DE INSUMOS MENSUALES FALTANTES
            $insumosSinMesPagoMensual = DB::table('inventarioinsumo as ii')
                ->whereNull('ii.deleted_at')
                ->join('empleados as e', 'ii.EmpleadoID', '=', 'e.EmpleadoID')
                ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
                ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
                ->where('d.GerenciaID', $gerenciaId)
                ->where('e.Estado', 1)
                ->whereNull('e.deleted_at')
                ->whereNotNull('ii.FechaRenovacion')
                ->whereNotIn('ii.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

                // NUEVO FILTRO
                ->whereYear('ii.FechaRenovacion', $anioSiguiente)

                ->where('ii.FrecuenciaDePago', 'LIKE', '%ensual%')
                ->where(function ($qq) {
                    $qq->whereNull('ii.MesDePago')
                        ->orWhere('ii.MesDePago', '')
                        ->orWhere('ii.MesDePago', 'N/A');
                })
                ->count();

            // 2. CONTEO DE INSUMOS ANUALES FALTANTES
            $insumosSinMesPagoAnual = DB::table('inventarioinsumo as ii')
                ->whereNull('ii.deleted_at')
                ->join('empleados as e', 'ii.EmpleadoID', '=', 'e.EmpleadoID')
                ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
                ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
                ->where('d.GerenciaID', $gerenciaId)
                ->where('e.Estado', 1)
                ->whereNull('e.deleted_at')
                ->whereNotNull('ii.FechaRenovacion')
                ->whereNotIn('ii.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

                // NUEVO FILTRO
                ->whereYear('ii.FechaRenovacion', $anioSiguiente)
                ->where('ii.FrecuenciaDePago', 'LIKE', '%nual%')
                ->where(function ($qq) {
                    $qq->whereNull('ii.MesDePago')
                        ->orWhere('ii.MesDePago', '')
                        ->orWhere('ii.MesDePago', 'N/A');
                })
                ->count();

            // 3. TOTAL DE EMPLEADOS
            $totalEmpleados = DB::table('empleados as e')
                ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
                ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
                ->where('d.GerenciaID', $gerenciaId)
                ->where('e.Estado', 1)
                ->whereNull('e.deleted_at')
                ->distinct()
                ->count('e.EmpleadoID');

            // 4. LÍNEAS HUÉRFANAS
            $lineasOrfanas = DB::table('lineastelefonicas as l')
                ->whereNull('l.deleted_at')
                ->whereNotNull('l.FechaRenovacion')
                ->whereNotIn('l.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

                // FILTRO DE ESTADOS
                ->where('l.Disponible', '1')
                ->where('l.Activo', '1')

                // NUEVO FILTRO
                ->whereYear('l.FechaRenovacion', $anioSiguiente)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('inventariolineas as il')
                        ->whereColumn('il.LineaID', 'l.LineaID')
                        ->whereNull('il.deleted_at');
                })
                ->count();

            // 5. INSUMOS HUÉRFANOS
            $insumosOrfanos = DB::table('insumos as i')
                ->whereNull('i.deleted_at')
                ->whereNotNull('i.FechaRenovacion')
                ->whereNotIn('i.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

                // NUEVO FILTRO
                ->whereYear('i.FechaRenovacion', $anioSiguiente)

                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('inventarioinsumo as ii')
                        ->whereColumn('ii.InsumoID', 'i.ID')
                        ->whereNull('ii.deleted_at')
                        ->where('ii.EmpleadoID', '>', 0);
                })
                ->count();

            return response()->json([
                'success' => true,
                'totalEmpleados' => $totalEmpleados,
                'empleadosSinMesPagoMensual' => $insumosSinMesPagoMensual,
                'empleadosSinMesPagoAnual' => $insumosSinMesPagoAnual,
                'lineasSinAsignarConFecha' => $lineasOrfanas,
                'insumosSinAsignarConFecha' => $insumosOrfanos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

