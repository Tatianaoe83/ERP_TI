<?php

namespace App\DataTables;

use App\Models\Planes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class PlanesDataTable extends DataTable
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
            return view('planes.datatables_actions', ['id' => $row->ID])->render();
        })
        ->rawColumns(['action'])
        ->setRowId('ID');


    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Planes $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Planes $model)
    {

        return $model->newQuery()
        ->join('companiaslineastelefonicas', 'planes.companiaID', '=', 'companiaslineastelefonicas.ID')
        ->select([
            'planes.ID',
            'companiaslineastelefonicas.Compania as nombre_compania',
            'planes.NombrePlan',
            'planes.PrecioPlan'
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
        ->setTableId('planes-table')
        ->columns($this->getColumns())
        ->minifiedAjax()
        ->dom('Bfrtip')
        ->orderBy(1, 'asc')
        ->buttons(array_filter([
            auth()->user()->can('crear-planes') ? [
                'extend' => 'collection',
                'className' => 'btn btn-primary',
                'text' => '<i class="fa fa-plus"></i> Crear',
                'action' => "function() {
                    window.location = '" . route('planes.create') . "';
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
                    window.LaravelDataTables["planes-table"].ajax.reload();
                }'
            ],
        ]))
        ->parameters([
            'processing' => true,
            'serverSide' => true,
            'responsive' => true,
            'pageLength' => 10,
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
            'ID' => [
                'title' => 'ID',
                'data' => 'ID',
                'name' => 'ID',
            ],
            'CompaniaID' => [
                'title' => 'Compania',
                'data' => 'nombre_compania',
                'name' => 'companiaslineastelefonicas.Compania',
            ],
            'NombrePlan' => [
                'title' => 'Nombre Plan',
                'data' => 'NombrePlan',
                'name' => 'NombrePlan',
            ],
            'PrecioPlan' => [
                'title' => 'Precio Plan',
                'data' => 'PrecioPlan',
                'name' => 'PrecioPlan',
            ],
            Column::computed('action')
            ->exportable(false)
            ->printable(false)
            ->width(60)
            ->addClass('text-center')
           
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'planes_datatable_' . time();
    }
}
