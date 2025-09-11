<?php

namespace App\DataTables;

use App\Models\InventarioInsumo;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

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
        return datatables($query)
            ->addColumn('fecha_formateada', function ($row) {
                return $row->FechaAsignacion ? \Carbon\Carbon::parse($row->FechaAsignacion)->format('d/m/Y') : 'N/A';
            })
            ->addColumn('costo_formateado', function ($row) {
                return '$' . number_format($row->CostoMensual ?? 0, 2);
            })
            ->addColumn('observaciones_formateadas', function ($row) {
                return $row->Observaciones ?? 'Sin observaciones';
            })
            ->rawColumns(['fecha_formateada', 'costo_formateado', 'observaciones_formateadas']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\InventarioInsumo $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(InventarioInsumo $model)
    {
        $filtros = request()->only(['empleado_id', 'estatus', 'fecha_desde', 'fecha_hasta', 'frecuencia_pago']);
        
        $query = DB::table('inventarioinsumo')
            ->join('empleados', 'inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'inventarioinsumo.*',
                'empleados.NombreEmpleado as empleado_nombre'
            ])
            ->whereNull('inventarioinsumo.deleted_at')
            ->whereNull('empleados.deleted_at');

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

        return $query;
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
                    ->orderBy(0)
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ])
                    ->parameters([
                        'responsive' => true,
                        'autoWidth' => false,
                        'language' => [
                            'url' => '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
                        ],
                        'pageLength' => 25,
                        'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
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
            Column::make('empleado_nombre')
                ->title('Empleado')
                ->addClass('text-left'),
            Column::make('NombreInsumo')
                ->title('Insumo')
                ->addClass('text-left'),
            Column::make('CateogoriaInsumo')
                ->title('Categoría')
                ->addClass('text-left'),
            Column::make('fecha_formateada')
                ->title('Fecha Asignación')
                ->addClass('text-left'),
            Column::make('costo_formateado')
                ->title('Costo Mensual')
                ->addClass('text-right'),
            Column::make('FrecuenciaDePago')
                ->title('Frecuencia de Pago')
                ->addClass('text-left'),
            Column::make('observaciones_formateadas')
                ->title('Observaciones')
                ->addClass('text-left'),
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