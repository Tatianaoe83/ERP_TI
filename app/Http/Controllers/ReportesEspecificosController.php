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
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
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
    public function equiposAsignados(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'equipo_id', 'estatus', 'fecha_desde', 'fecha_hasta', 'gerencia_id']) + [
            'empleado_id' => '',
            'equipo_id' => '',
            'estatus' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'gerencia_id' => ''
        ];
        
        $query = DB::table('inventarioequipo')
            //->join('equipos', 'inventarioequipo.EquipoID', '=', 'equipos.EquipoID')
            ->join('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'empleados.NombreEmpleado as empleado_nombre',
                'inventarioequipo.GerenciaEquipo',
                //'equipos.Marca as equipo_marca',
                //'equipos.Modelo as equipo_modelo',
                //'equipos.NumeroSerie as equipo_serie',
                'inventarioequipo.Marca',
                'inventarioequipo.Modelo',
                'inventarioequipo.Folio',
                'inventarioequipo.Caracteristicas',
                'inventarioequipo.NumSerie',
                'inventarioequipo.FechaAsignacion',
               
              
            ]);
          
            //->whereNull('inventarioequipo.deleted_at')
            //->whereNull('equipos.deleted_at')
            //->whereNull('empleados.deleted_at');

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

        if ($request->ajax()) {
            return response()->json($resultado);
        }

        return view('reportes_especificos.equipos_asignados', compact('resultado', 'filtros'));
    }

    /**
     * Reporte de líneas asignadas
     */
    public function lineasAsignadas(Request $request)
    {
        $filtros = $request->only(['empleado_id', 'linea_id', 'estatus', 'fecha_desde', 'fecha_hasta']) + [
            'empleado_id' => '',
            'linea_id' => '',
            'estatus' => '',
            'fecha_desde' => '',
            'fecha_hasta' => ''
        ];
        
        $query = DB::table('inventariolineas')
            ->join('lineastelefonicas', 'inventariolineas.LineaID', '=', 'lineastelefonicas.LineaID')
            ->join('empleados', 'inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->join('obras', 'inventariolineas.ObraID', '=', 'obras.ObraID')
            ->select([
                'empleados.NombreEmpleado as empleado_nombre',
                'lineastelefonicas.Numero as linea_numero',
                'lineastelefonicas.Tipo as linea_tipo',
                'obras.Nombre as obra_nombre',
                'inventariolineas.FechaAsignacion',
                'inventariolineas.Estatus',
                'inventariolineas.Observaciones'
            ])
            ->whereNull('inventariolineas.deleted_at')
            ->whereNull('lineastelefonicas.deleted_at')
            ->whereNull('empleados.deleted_at')
            ->whereNull('obras.deleted_at');

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventariolineas.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['linea_id'])) {
            $query->where('inventariolineas.LineaID', $filtros['linea_id']);
        }

        if (!empty($filtros['estatus'])) {
            $query->where('inventariolineas.Estatus', $filtros['estatus']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventariolineas.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventariolineas.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        $resultado = $query->orderBy('inventariolineas.FechaAsignacion', 'desc')->get();

        if ($request->ajax()) {
            return response()->json($resultado);
        }

        return view('reportes_especificos.lineas_asignadas', compact('resultado', 'filtros'));
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
        $filtros = $request->only(['empleado_id', 'linea_id', 'estatus', 'fecha_desde', 'fecha_hasta']);
        
        $query = DB::table('inventariolineas')
            ->join('lineastelefonicas', 'inventariolineas.LineaID', '=', 'lineastelefonicas.LineaID')
            ->join('empleados', 'inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->join('obras', 'inventariolineas.ObraID', '=', 'obras.ObraID')
            ->select([
                'empleados.NombreEmpleado as empleado_nombre',
                'lineastelefonicas.Numero as linea_numero',
                'lineastelefonicas.Tipo as linea_tipo',
                'obras.Nombre as obra_nombre',
                'inventariolineas.FechaAsignacion',
                'inventariolineas.Estatus',
                'inventariolineas.Observaciones'
            ])
            ->whereNull('inventariolineas.deleted_at')
            ->whereNull('lineastelefonicas.deleted_at')
            ->whereNull('empleados.deleted_at')
            ->whereNull('obras.deleted_at');

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventariolineas.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['linea_id'])) {
            $query->where('inventariolineas.LineaID', $filtros['linea_id']);
        }

        if (!empty($filtros['estatus'])) {
            $query->where('inventariolineas.Estatus', $filtros['estatus']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventariolineas.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventariolineas.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        $resultado = $query->orderBy('inventariolineas.FechaAsignacion', 'desc')->get();

        $pdf = Pdf::loadView('reportes_especificos.export_lineas_asignadas_pdf', compact('resultado', 'filtros'));
        return $pdf->download('lineas_asignadas_' . date('Y-m-d') . '.pdf');
    }
}
