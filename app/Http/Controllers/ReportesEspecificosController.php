<?php

namespace App\Http\Controllers;

use App\Models\Equipos;
use App\Models\Empleados;
use App\Models\LineasTelefonicas;
use App\Models\InventarioEquipo;
use App\Models\InventarioLineas;
use App\Models\InventarioInsumo;
use App\Models\Insumos;
use App\DataTables\EstatusLicenciasDataTable;
use App\DataTables\EquiposAsignadosDataTable;
use App\DataTables\LineasAsignadasDataTable;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LineasAsignadasExport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportesEspecificosController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('permission:ver-reportes')->only(['index', 'estatusLicencias', 'equiposAsignados', 'lineasAsignadas']);
        $this->middleware('permission:exportar-reportes')->only(['exportEstatusLicencias', 'exportEquiposAsignados', 'exportLineasAsignadas']);
    }

    /**
     * Mostrar la página principal de reportes específicos
     */
    public function index()
    {
        return view('reportes_especificos.index');
    }

    /**
     * Reporte de estatus de licencias asignadas
     */
    public function estatusLicencias(Request $request, EstatusLicenciasDataTable $dataTable)
    {

        print_r($request->all());
        $filtros = $request->only(['empleado_id', 'estatus', 'fecha_desde', 'fecha_hasta', 'frecuencia_pago']) + [
            'empleado_id' => '',
            'estatus' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'frecuencia_pago' => ''
        ];

       

        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('reportes_especificos.estatus_licencias', ['filtros' => $filtros]);
    }

    /**
     * Reporte de equipos asignados
     */
    public function equiposAsignados(Request $request, EquiposAsignadosDataTable $dataTable)
    {
        $filtros = $request->only(['empleado_id', 'equipo_id', 'estatus', 'fecha_desde', 'fecha_hasta', 'gerencia_id']) + [
            'empleado_id' => '',
            'equipo_id' => '',
            'estatus' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'gerencia_id' => ''
        ];

        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('reportes_especificos.equipos_asignados', ['filtros' => $filtros]);
    }

    /**
     * Reporte de líneas asignadas
     */
    public function lineasAsignadas(Request $request, LineasAsignadasDataTable $dataTable)
    {
        $filtros = $request->only(['empleado_id', 'linea_id', 'fecha_desde', 'fecha_hasta', 'cuenta_padre']) + [
            'empleado_id' => '',
            'linea_id' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'cuenta_padre' => ''
        ];


        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('reportes_especificos.lineas_asignadas', ['filtros' => $filtros]);
    }

    /**
     * Exportar estatus de licencias a PDF
     */
    public function exportEstatusLicencias(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'estatus', 'fecha_desde', 'fecha_hasta', 'frecuencia_pago']);
        
        $query = DB::table('inventarioinsumo')
            ->join('insumos', 'inventarioinsumo.InsumoID', '=', 'insumos.InsumoID')
            ->join('empleados', 'inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'empleados.NombreEmpleado as empleado_nombre',
                'insumos.Nombre as insumo_nombre',
                'insumos.Tipo as insumo_tipo',
                'inventarioinsumo.FechaAsignacion',
                'inventarioinsumo.Estatus',
                'inventarioinsumo.Observaciones'
            ])
            ->whereNull('inventarioinsumo.deleted_at')
            ->whereNull('insumos.deleted_at')
            ->whereNull('empleados.deleted_at');

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventarioinsumo.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['estatus'])) {
            $query->where('inventarioinsumo.Estatus', $filtros['estatus']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventarioinsumo.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventarioinsumo.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        $resultado = $query->orderBy('inventarioinsumo.FechaAsignacion', 'desc')->get();

        $pdf = Pdf::loadView('reportes_especificos.export_estatus_licencias_pdf', compact('resultado', 'filtros'));
        return $pdf->download('estatus_licencias_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exportar equipos asignados a PDF
     */
    public function exportEquiposAsignados(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'equipo_id', 'estatus', 'fecha_desde', 'fecha_hasta']);
        
        $query = DB::table('inventarioequipo')
            ->join('equipos', 'inventarioequipo.EquipoID', '=', 'equipos.EquipoID')
            ->join('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'empleados.NombreEmpleado as empleado_nombre',
                'equipos.Nombre as equipo_nombre',
                'equipos.Marca as equipo_marca',
                'equipos.Modelo as equipo_modelo',
                'equipos.NumeroSerie as equipo_serie',
                'inventarioequipo.FechaAsignacion',
                'inventarioequipo.Estatus',
                'inventarioequipo.Observaciones'
            ])
            ->whereNull('inventarioequipo.deleted_at')
            ->whereNull('equipos.deleted_at')
            ->whereNull('empleados.deleted_at');

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventarioequipo.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['equipo_id'])) {
            $query->where('inventarioequipo.EquipoID', $filtros['equipo_id']);
        }

        if (!empty($filtros['estatus'])) {
            $query->where('inventarioequipo.Estatus', $filtros['estatus']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventarioequipo.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventarioequipo.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        $resultado = $query->orderBy('inventarioequipo.FechaAsignacion', 'desc')->get();

        $pdf = Pdf::loadView('reportes_especificos.export_equipos_asignados_pdf', compact('resultado', 'filtros'));
        return $pdf->download('equipos_asignados_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exportar líneas asignadas a PDF
     */
    public function exportLineasAsignadas(Request $request)
    {
      
        $filtros =[];
        
        $query = DB::table('inventariolineas')
            ->leftJoin('empleados', 'inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->leftJoin('obras', 'inventariolineas.ObraID', '=', 'obras.ObraID')
            ->select([
                'inventariolineas.InventarioID',
                'empleados.NombreEmpleado as empleado_nombre',
                'inventariolineas.NumTelefonico as linea_numero',
                'inventariolineas.TipoLinea as linea_tipo',
                'obras.NombreObra as obra_nombre',
                'inventariolineas.FechaAsignacion as fecha_asignacion',
                'inventariolineas.CostoRentaMensual as costo_renta_mensual',
                'inventariolineas.CuentaPadre as cuenta_padre',
                'inventariolineas.CuentaHija as cuenta_hija',
                'inventariolineas.MontoRenovacionFianza as monto_renovacion_fianza'
            ]);

    

        $resultado = $query->orderBy('inventariolineas.FechaAsignacion', 'desc')->get();

        $nombreArchivo = 'lineas_asignadas_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new LineasAsignadasExport($resultado, $filtros), $nombreArchivo);
    }

    /**
     * Exportar líneas asignadas a Excel
     */
    public function exportLineasAsignadasExcel(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'linea_id', 'fecha_desde', 'fecha_hasta', 'cuenta_padre']);
        
        $query = DB::table('inventariolineas')
            ->leftJoin('empleados', 'inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->leftJoin('obras', 'inventariolineas.ObraID', '=', 'obras.ObraID')
            ->select([
                'inventariolineas.InventarioID',
                'empleados.NombreEmpleado as empleado_nombre',
                'inventariolineas.NumTelefonico as linea_numero',
                'inventariolineas.TipoLinea as linea_tipo',
                'obras.NombreObra as obra_nombre',
                'inventariolineas.FechaAsignacion as fecha_asignacion',
                'inventariolineas.CostoRentaMensual as costo_renta_mensual',
                'inventariolineas.CuentaPadre as cuenta_padre',
                'inventariolineas.CuentaHija as cuenta_hija',
                'inventariolineas.MontoRenovacionFianza as monto_renovacion_fianza'
            ]);

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventariolineas.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['linea_id'])) {
            // Buscar por número telefónico en lugar de LineaID
            $linea = DB::table('lineastelefonicas')->where('LineaID', $filtros['linea_id'])->first();
            if ($linea) {
                $query->where('inventariolineas.NumTelefonico', $linea->NumTelefonico);
            }
        }

        if (!empty($filtros['cuenta_padre'])) {
            $query->where('inventariolineas.CuentaPadre', $filtros['cuenta_padre']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventariolineas.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventariolineas.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        $resultado = $query->orderBy('inventariolineas.FechaAsignacion', 'desc')->get();

        $nombreArchivo = 'lineas_asignadas_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new LineasAsignadasExport($resultado, $filtros), $nombreArchivo);
    }
}
