<?php

namespace App\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Html\Column;

class EquiposAsignadosDataTable extends DataTable
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
            ->addColumn('fecha_formateada', function ($row) {
                return \Carbon\Carbon::parse($row->FechaAsignacion)->format('d/m/Y');
            })
            ->rawColumns(['fecha_formateada'])
            ->setRowId('Folio');
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $filtros = request()->only(['empleado_id', 'equipo_id', 'estatus', 'fecha_desde', 'fecha_hasta', 'gerencia_id']);
        
        $query = DB::table('inventarioequipo')
            ->join('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'empleados.NombreEmpleado as empleado_nombre',
                'inventarioequipo.GerenciaEquipo',
                'inventarioequipo.Marca',
                'inventarioequipo.Modelo',
                'inventarioequipo.Folio',
                'inventarioequipo.Caracteristicas',
                'inventarioequipo.NumSerie',
                'inventarioequipo.FechaAsignacion',
            ]);

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

        return $query->orderBy('inventarioequipo.FechaAsignacion', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('equipos-asignados-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(5, 'desc') // Ordenar por fecha de asignación descendente
            ->buttons([
                [
                    'extend' => 'excel',
                    'className' => 'btn btn-success',
                    'text' => '<i class="fa fa-file-excel"></i> Excel'
                ],
                [
                    'extend' => 'pdf',
                    'className' => 'btn btn-danger',
                    'text' => '<i class="fa fa-file-pdf"></i> PDF'
                ],
                [
                    'className' => 'btn btn-default',
                    'text' => '<i class="fa fa-sync-alt"></i> Recargar',
                    'action' => 'function() { 
                        window.LaravelDataTables["equipos-asignados-table"].ajax.reload();
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
            'GerenciaEquipo' => [
                'title' => 'Gerencia',
                'data' => 'GerenciaEquipo',
                'name' => 'inventarioequipo.GerenciaEquipo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Marca' => [
                'title' => 'Marca/Modelo',
                'data' => 'Marca',
                'name' => 'inventarioequipo.Marca',
                'render' => 'function(data, type, row) {
                    return data + " " + row.Modelo;
                }',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NumSerie' => [
                'title' => 'Número de Serie',
                'data' => 'NumSerie',
                'name' => 'inventarioequipo.NumSerie',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            Column::computed('fecha_formateada')
                ->title('Fecha Asignación')
                ->exportable(true)
                ->printable(true)
                ->addClass('dark:bg-[#101010] dark:text-white'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'equipos_asignados_datatable_' . time();
    }
}
