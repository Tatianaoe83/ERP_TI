<?php

namespace App\DataTables;

use App\Models\Obras;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class ObrasDataTable extends DataTable
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
            return view('obras.datatables_actions', ['id' => $row->ObraID])->render();
        })
        ->rawColumns(['action'])
        ->setRowId('ObraID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Obras $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Obras $model)
    {
       
        return $model->newQuery()->select([
            'ObraID',
            'NombreObra',
            'Direccion',
            'EncargadoDeObra',
            'UnidadNegocioID'
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
            ->setTableId('obras-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(1, 'asc')
            ->buttons([
                [
                    'extend' => 'collection',
                    'className' => 'btn btn-primary',
                    'text' => '<i class="fa fa-plus"></i> Crear',
                    'action' => "function() {
                        window.location = '" . route('obras.create') . "';
                    }"
                ],
             
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
                        window.LaravelDataTables["obras-table"].ajax.reload();
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
            
            'ObraID' => [
                'title' => 'ID',
                'data' => 'ObraID',
                'name' => 'ObraID',
            ],
            'NombreObra' => [
                'title' => 'Nombre Obra',
                'data' => 'NombreObra',
                'name' => 'NombreObra',
            ],
            'Direccion' =>[
                'title' => 'Dirección',
                'data' => 'Direccion',
                'name' => 'Direccion',

            ],
            'EncargadoDeObra' => [
                'title' => 'Encargado Obra',
                'data' => 'EncargadoDeObra',
                'name' => 'EncargadoDeObra',
            ],
            'UnidadNegocioID' => [
                'title' => 'Unidad Negocio',
                'data' => 'UnidadNegocioID',
                'name' => 'UnidadNegocioID',
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
        return 'obras_datatable_' . time();
    }
}
