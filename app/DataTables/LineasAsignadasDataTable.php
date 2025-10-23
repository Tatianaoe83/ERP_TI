<?php

namespace App\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Html\Column;

class LineasAsignadasDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new QueryDataTable($query);

        return $dataTable
            ->setRowId('InventarioID');
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $filtros = request()->only(['empleado_id', 'linea_id', 'fecha_desde', 'fecha_hasta', 'cuenta_padre']);
        
        $query = DB::table('inventariolineas')
            ->leftJoin('empleados', 'inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->leftJoin('obras', 'inventariolineas.ObraID', '=', 'obras.ObraID')
            ->select([
                'inventariolineas.InventarioID',
                'empleados.NombreEmpleado as empleado_nombre',
                'empleados.Correo as empleado_correo',
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

        return $query->orderBy('inventariolineas.FechaAsignacion', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('lineas-asignadas-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(7, 'desc') // Ordenar por fecha de asignación descendente
            ->buttons([
                [
                    'className' => 'btn btn-default',
                    'text' => '<i class="fa fa-sync-alt"></i> Recargar',
                    'action' => 'function() { 
                        window.LaravelDataTables["lineas-asignadas-table"].ajax.reload();
                    }'
                ],
            ])
            ->parameters([
                'processing' => true,
                'serverSide' => true,
                'responsive' => true,
                'pageLength' => 10,
                'searching' => true,
                'language' => [
                    'processing' => 'Procesando...',
                    'lengthMenu' => 'Mostrar _MENU_ registros',
                    'zeroRecords' => 'No se encontraron resultados',
                    'emptyTable' => 'Ningún dato disponible en esta tabla',
                    'info' => 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
                    'infoEmpty' => 'Mostrando registros del 0 al 0 de un total de 0 registros',
                    'infoFiltered' => '(filtrado de un total de _MAX_ registros)',
                    'infoPostFix' => '',
                    'search' => 'Buscar:',
                    'url' => '',
                    'infoThousands' => ',',
                    'loadingRecords' => 'Cargando...',
                    'paginate' => [
                        'first' => 'Primero',
                        'last' => 'Último',
                        'next' => 'Siguiente',
                        'previous' => 'Anterior'
                    ],
                    'aria' => [
                        'sortAscending' => ': Activar para ordenar la columna de manera ascendente',
                        'sortDescending' => ': Activar para ordenar la columna de manera descendente'
                    ]
                ],
                'drawCallback' => 'function() {
                    if (typeof $ !== "undefined" && $.fn.tooltip) {
                        $("[data-toggle=tooltip]").tooltip();
                    }
                }',
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'empleado_nombre' => [
                'title' => 'Empleado',
                'data' => 'empleado_nombre',
                'name' => 'empleados.NombreEmpleado',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'empleado_correo' => [
                'title' => 'Correo del Empleado',
                'data' => 'empleado_correo',
                'name' => 'empleados.Correo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'linea_numero' => [
                'title' => 'Número de Línea',
                'data' => 'linea_numero',
                'name' => 'inventariolineas.NumTelefonico',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'linea_tipo' => [
                'title' => 'Tipo',
                'data' => 'linea_tipo',
                'name' => 'inventariolineas.TipoLinea',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'obra_nombre' => [
                'title' => 'Obra',
                'data' => 'obra_nombre',
                'name' => 'obras.NombreObra',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'fecha_asignacion' => [
                'title' => 'Fecha Asignación',
                'data' => 'fecha_asignacion',
                'name' => 'inventariolineas.FechaAsignacion',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
        
            'costo_renta_mensual' => [
                'title' => 'Costo Renta Mensual',
                'data' => 'costo_renta_mensual',
                'name' => 'inventariolineas.CostoRentaMensual',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'cuenta_padre' => [
                'title' => 'Cuenta Padre',
                'data' => 'cuenta_padre',
                'name' => 'inventariolineas.CuentaPadre',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'cuenta_hija' => [
                'title' => 'Cuenta Hija',
                'data' => 'cuenta_hija',
                'name' => 'inventariolineas.CuentaHija',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'monto_renovacion_fianza' => [
                'title' => 'Monto Renovación Fianza',
                'data' => 'monto_renovacion_fianza',
                'name' => 'inventariolineas.MontoRenovacionFianza',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],

        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'lineas_asignadas_datatable_' . time();
    }
}
