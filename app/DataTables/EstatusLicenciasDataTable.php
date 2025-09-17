<?php

namespace App\DataTables;

use App\Models\InventarioInsumo;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\QueryDataTable;

class EstatusLicenciasDataTable extends DataTable
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
     * @param \App\Models\InventarioInsumo $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(InventarioInsumo $model)
    {
        $filtros = request()->only(['empleado_id', 'fecha_desde', 'fecha_hasta', 'frecuencia_pago', 'inventarioinsumo_mes_pago']);
        
        try {
           
            
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
            ])
            ;

            // Aplicar filtros
            if (!empty($filtros['empleado_id'])) {
                $query->where('inventarioinsumo.EmpleadoID', $filtros['empleado_id']);
            }

            if (!empty($filtros['fecha_desde'])) {
                $query->whereDate('inventarioinsumo.FechaAsignacion', '>=', $filtros['fecha_desde']);
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query->whereDate('inventarioinsumo.FechaAsignacion', '<=', $filtros['fecha_hasta']);
            }

            if (!empty($filtros['frecuencia_pago'])) {
                $query->where('inventarioinsumo.FrecuenciaDePago', $filtros['frecuencia_pago']);
            }

            if (!empty($filtros['inventarioinsumo_mes_pago'])) {
                $query->where('inventarioinsumo.MesDePago', $filtros['inventarioinsumo_mes_pago']);
            }

            return $query->orderBy('inventarioinsumo.InventarioID', 'desc');
            
        } catch (\Exception $e) {
            // En caso de error, devolver una consulta vacía
           
            return DB::table('inventarioinsumo')->whereRaw('1 = 0');
        }
    }

    /**
     * Optional method if you want to use the html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('estatus-licencias-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('Bfrtip')
                    ->orderBy(0, 'desc') // Ordenar por fecha de asignación descendente
                    ->buttons([
                        [
                            'className' => 'btn btn-default',
                            'text' => '<i class="fa fa-sync-alt"></i> Recargar',
                            'action' => 'function() { 
                                window.LaravelDataTables["estatus-licencias-table"].ajax.reload();
                            }'
                        ]
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
                'insumo_nombre' => [
                    'title' => 'Nombre del Insumo',
                    'data' => 'insumo_nombre',
                    'name' => 'insumos.NombreInsumo',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'insumo_tipo' => [
                    'title' => 'Tipo',
                    'data' => 'insumo_tipo',
                    'name' => 'insumos.CategoriaInsumo',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'num_serie' => [
                    'title' => 'Número de Serie',
                    'data' => 'num_serie',
                    'name' => 'inventarioinsumo.NumSerie',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'frecuencia_pago' => [
                    'title' => 'Frecuencia de Pago',
                    'data' => 'frecuencia_pago',
                    'name' => 'inventarioinsumo.FrecuenciaDePago',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'costo_mensual' => [
                    'title' => 'Costo Mensual',
                    'data' => 'costo_mensual',
                    'name' => 'inventarioinsumo.CostoMensual',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'costo_anual' => [
                    'title' => 'Costo Anual',
                    'data' => 'costo_anual',
                    'name' => 'inventarioinsumo.CostoAnual',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'mes_pago' => [
                    'title' => 'Mes de Pago',
                    'data' => 'mes_pago',
                    'name' => 'inventarioinsumo.MesDePago',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'observaciones' => [
                    'title' => 'Observaciones',
                    'data' => 'observaciones',
                    'name' => 'inventarioinsumo.Observaciones',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ],
                'comentarios' => [
                    'title' => 'Comentarios',
                    'data' => 'comentarios',
                    'name' => 'inventarioinsumo.Comentarios',
                    'class' => 'dark:bg-[#101010] dark:text-white'
                ]
            ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'EstatusLicencias_' . date('YmdHis');
    }
}