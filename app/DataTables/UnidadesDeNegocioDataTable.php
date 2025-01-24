<?php

namespace App\DataTables;

use App\Models\UnidadesDeNegocio;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class UnidadesDeNegocioDataTable extends DataTable
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
                return view('unidades_de_negocios.datatables_actions', ['id' => $row->UnidadNegocioID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('UnidadNegocioID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\UnidadesDeNegocio $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(UnidadesDeNegocio $model)
    {
        return $model->newQuery()->select([
            'UnidadNegocioID',
            'NombreEmpresa',
            'RFC',
            'Direccion',
            'NumTelefono'
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
            ->setTableId('unidadesDeNegocios-table')
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
                        window.location = '" . route('unidadesDeNegocios.create') . "';
                    }"
                ],
                /*[
                    'extend' => 'csv',
                    'className' => 'btn btn-info',
                    'text' => '<i class="fa fa-file-csv"></i> CSV'
                ],*/
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
                /*[
                    'extend' => 'print',
                    'className' => 'btn btn-light',
                    'text' => '<i class="fa fa-print"></i> Imprimir'
                ],*/
                [
                    'className' => 'btn btn-default',
                    'text' => '<i class="fa fa-sync-alt"></i> Recargar',
                    'action' => 'function() { 
                        window.LaravelDataTables["unidadesDeNegocios-table"].ajax.reload();
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
            'UnidadNegocioID' => [
                'title' => 'ID',
                'data' => 'UnidadNegocioID',
                'name' => 'UnidadNegocioID',
            ],
            'NombreEmpresa' => [
                'title' => 'Nombre Empresa',
                'data' => 'NombreEmpresa',
                'name' => 'NombreEmpresa',
            ],
            'RFC' => [
                'title' => 'RFC',
                'data' => 'RFC',
                'name' => 'RFC',
            ],
            'Direccion' => [
                'title' => 'Dirección',
                'data' => 'Direccion',
                'name' => 'Direccion',
            ],
            'NumTelefono' => [
                'title' => 'Teléfono',
                'data' => 'NumTelefono',
                'name' => 'NumTelefono',
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
        return 'unidades_de_negocios_datatable_' . time();
    }
}
