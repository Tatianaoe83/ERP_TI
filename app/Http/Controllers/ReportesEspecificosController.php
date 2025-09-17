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
use App\Exports\EquiposAsignadosExport;
use App\Exports\EstatusLicenciasExport;
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

        $filtros = $request->only(['empleado_id', 'fecha_desde', 'fecha_hasta', 'frecuencia_pago', 'inventarioinsumo_mes_pago']) + [
            'empleado_id' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'frecuencia_pago' => '',
            'inventarioinsumo_mes_pago' => ''
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


        $filtros = $request->only(['empleado_id', 'equipo_id', 'fecha_desde', 'fecha_hasta', 'gerencia_id','categoria_nombre','marca']) + [
            'empleado_id' => '',
            'equipo_id' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'gerencia_id' => '',
            'categoria_nombre' => '',
            'marca' => ''
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
    public function exportEstatusLicenciasExcel(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'fecha_desde', 'fecha_hasta', 'frecuencia_pago', 'inventarioinsumo_mes_pago']);
        
        $query = DB::table('inventarioinsumo')
        ->join('empleados', 'inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID')
        ->where('inventarioinsumo.CateogoriaInsumo', 'Licencia')
   
        ->select([
            'inventarioinsumo.InventarioID',
            'empleados.NombreEmpleado as empleado_nombre',
            'inventarioinsumo.NombreInsumo as insumo_nombre',
            'inventarioinsumo.CateogoriaInsumo as insumo_tipo',
            'inventarioinsumo.FechaAsignacion',
            'inventarioinsumo.NumSerie as num_serie',
            'inventarioinsumo.FrecuenciaDePago as frecuencia_pago',
            'inventarioinsumo.CostoMensual as costo_mensual',
            'inventarioinsumo.CostoAnual as costo_anual',
            'inventarioinsumo.MesDePago as mes_pago',
            'inventarioinsumo.Observaciones as observaciones',
            'inventarioinsumo.Comentarios as comentarios'
        ]);

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventarioinsumo.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['frecuencia_pago'])) {
            $query->where('inventarioinsumo.FrecuenciaDePago', $filtros['frecuencia_pago']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventarioinsumo.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventarioinsumo.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['inventarioinsumo_mes_pago'])) {
            $query->where('inventarioinsumo.MesDePago', $filtros['inventarioinsumo_mes_pago']);
        }

        $resultado = $query->orderBy('inventarioinsumo.FechaAsignacion', 'desc')->get();

        $nombreArchivo = 'estatus_licencias_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new EstatusLicenciasExport($resultado, $filtros), $nombreArchivo);
    }

    public function exportEstatusLicencias(Request $request)
    {
        $filtros = [];
        
        $query = DB::table('inventarioinsumo')
        ->join('empleados', 'inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID')
        ->where('inventarioinsumo.CateogoriaInsumo', 'Licencia')
   
        ->select([
            'inventarioinsumo.InventarioID',
            'empleados.NombreEmpleado as empleado_nombre',
            'inventarioinsumo.NombreInsumo as insumo_nombre',
            'inventarioinsumo.CateogoriaInsumo as insumo_tipo',
            'inventarioinsumo.FechaAsignacion',
            'inventarioinsumo.NumSerie as num_serie',
            'inventarioinsumo.FrecuenciaDePago as frecuencia_pago',
            'inventarioinsumo.CostoMensual as costo_mensual',
            'inventarioinsumo.CostoAnual as costo_anual',
            'inventarioinsumo.MesDePago as mes_pago',
            'inventarioinsumo.Observaciones as observaciones',
            'inventarioinsumo.Comentarios as comentarios'
        ]);


        $resultado = $query->orderBy('inventarioinsumo.InventarioID', 'desc')->get();

        $nombreArchivo = 'estatus_licencias_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new EstatusLicenciasExport($resultado, $filtros), $nombreArchivo);
    }



        /**
     * Exportar equipos asignados a PDF
     */
    public function exportEquiposAsignados(Request $request)
    {
        $filtros = [];
        
        $query = DB::table('inventarioequipo')
        ->join('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
        ->select([
            'inventarioequipo.InventarioID',
            'empleados.NombreEmpleado as empleado_nombre',
            'inventarioequipo.GerenciaEquipo',
            'inventarioequipo.Marca',
            'inventarioequipo.Modelo',
            'inventarioequipo.Folio',
            'inventarioequipo.Caracteristicas',
            'inventarioequipo.NumSerie',
            'inventarioequipo.FechaAsignacion',
            'inventarioequipo.CategoriaEquipo',
        ])
            ->whereNull('empleados.deleted_at');

            
        $resultado = $query->orderBy('inventarioequipo.FechaAsignacion', 'desc')->get();

        $nombreArchivo = 'equipos_asignados_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new EquiposAsignadosExport($resultado, $filtros), $nombreArchivo);
    }

    /**
     * Exportar equipos asignados a PDF
     */
    public function exportEquiposAsignadosExcel(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'equipo_id', 'marca', 'fecha_desde', 'fecha_hasta', 'gerencia_id','categoria_nombre']);
        
        $query = DB::table('inventarioequipo')
        ->join('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
        ->select([
            'inventarioequipo.InventarioID',
            'empleados.NombreEmpleado as empleado_nombre',
            'inventarioequipo.GerenciaEquipo',
            'inventarioequipo.Marca',
            'inventarioequipo.Modelo',
            'inventarioequipo.Folio',
            'inventarioequipo.Caracteristicas',
            'inventarioequipo.NumSerie',
            'inventarioequipo.FechaAsignacion',
            'inventarioequipo.CategoriaEquipo',
        ])
            ->whereNull('empleados.deleted_at');

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventarioequipo.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['equipo_id'])) {
            $query->where('inventarioequipo.EquipoID', $filtros['equipo_id']);
        }

        if (!empty($filtros['marca'])) {
            $query->where('inventarioequipo.Marca', $filtros['marca']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventarioequipo.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventarioequipo.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['gerencia_id'])) {
            $query->where('inventarioequipo.GerenciaEquipoID', $filtros['gerencia_id']);    
        }

        if (!empty($filtros['categoria_nombre'])) {
            $query->where('inventarioequipo.CategoriaEquipo', $filtros['categoria_nombre']);
        }

        $resultado = $query->orderBy('inventarioequipo.FechaAsignacion', 'desc')->get();

        $nombreArchivo = 'equipos_asignados_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new EquiposAsignadosExport($resultado, $filtros), $nombreArchivo);
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
