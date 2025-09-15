<?php

namespace App\DataTables;

use App\Models\LineasTelefonicas;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class LineasTelefonicasDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);
        
        return $dataTable
        ->addColumn('action', function($row){
            return view('lineas_telefonicas.datatables_actions', ['id' => $row->LineaID])->render();
        })
        ->addColumn('estado_disponibilidad', function ($row) {
            if ($row->Disponible == 1) {
                return '<span class="badge badge-success" style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Disponible</span>';
            } else {
                return '<span class="badge badge-danger" style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Asignada</span>';
            }
        })
        ->rawColumns(['action', 'estado_disponibilidad'])
        ->setRowId('LineaID');

    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\LineasTelefonicas $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(LineasTelefonicas $model)
    {

        return $model->newQuery()
            ->join('obras', 'obras.ObraID', '=', 'lineastelefonicas.ObraID')
            ->join('planes', 'planes.ID', '=', 'lineastelefonicas.PlanID')
            ->select([
                'lineastelefonicas.LineaID',
                'lineastelefonicas.NumTelefonico',
                'planes.NombrePlan as nombre_plan',
                'lineastelefonicas.CuentaPadre',
                'lineastelefonicas.CuentaHija',
                'lineastelefonicas.TipoLinea',
                'obras.NombreObra as nombre_obra',
                'lineastelefonicas.FechaFianza',
                'lineastelefonicas.CostoFianza',
                'lineastelefonicas.Disponible',
                'lineastelefonicas.MontoRenovacionFianza'

            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('lineas-telefonicas-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(1, 'asc')
            ->buttons(array_filter([
                auth()->user()->can('crear-Lineastelefonicas') ? [
                    'extend' => 'collection',
                    'className' => 'btn btn-primary',
                    'text' => '<i class="fa fa-plus"></i> Crear',
                    'action' => "function() {
                    window.location = '" . route('lineasTelefonicas.create') . "';
                }"
                ] : null,

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
                    window.LaravelDataTables["lineas-telefonicas-table"].ajax.reload();
                }'
                ],
            ]))
            ->parameters([
                'processing' => true,
                'serverSide' => true,
                'responsive' => true,
                'pageLength' => 4,
                'searching' => true,
                'language' => [
                    'url' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                ],
                'drawCallback' => 'function() {
                $("[data-toggle=tooltip]").tooltip();
            }',
                'initComplete' => "function() {
                this.api().columns().every(function () {
                    var column = this;
                    var input = document.createElement(\"input\");
                    $(input).appendTo($(column.footer()).empty())
                    .on('change', function () {
                        column.search($(this).val(), false, false, true).draw();
                    });
                });
            }",
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
            'LineaID' => [
                'title' => 'ID',
                'data' => 'LineaID',
                'name' => 'LineaID',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NumTelefonico' => [
                'title' => 'Num Telefonico',
                'data' => 'NumTelefonico',
                'name' => 'NumTelefonico',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'PlanID' => [
                'title' => 'Plan',
                'data' => 'nombre_plan',
                'name' => 'planes.NombrePlan',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CuentaPadre' => [
                'title' => 'Cuenta Padre',
                'data' => 'CuentaPadre',
                'name' => 'CuentaPadre',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CuentaHija' => [
                'title' => 'Cuenta Hija',
                'data' => 'CuentaHija',
                'name' => 'CuentaHija',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'TipoLinea' => [
                'title' => 'Tipo Linea',
                'data' => 'TipoLinea',
                'name' => 'TipoLinea',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'ObraID' => [
                'title' => 'Obra',
                'data' => 'nombre_obra',
                'name' => 'obras.NombreObra',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'FechaFianza' => [
                'title' => 'Fecha Fianza',
                'data' => 'FechaFianza',
                'name' => 'FechaFianza',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CostoFianza' => [
                'title' => 'Costo Fianza',
                'data' => 'CostoFianza',
                'name' => 'CostoFianza',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'MontoRenovacionFianza' => [
                'title' => 'Monto Renovacion Fianza',
                'data' => 'MontoRenovacionFianza',
                'name' => 'MontoRenovacionFianza',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            Column::computed('estado_disponibilidad')
                ->title('Estado Disponibilidad')
                ->exportable(false)
                ->printable(false)
                ->width(120)
                ->addClass('text-center dark:bg-[#101010] dark:text-white'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center dark:bg-[#101010] dark:text-white')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'lineas_telefonicas_datatable_' . time();
    }
}
