<?php

namespace App\DataTables;

use App\Models\Categorias;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class CategoriasDataTable extends DataTable
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
            return view('categorias.datatables_actions', ['id' => $row->ID])->render();
        })
        ->rawColumns(['action'])
        ->setRowId('ID');

       
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Categorias $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Categorias $model)
    {

        return $model->newQuery()
        ->join('TiposDeCategorias', 'Categorias.TipoID', '=', 'TiposDeCategorias.ID')
        ->select([
            'Categorias.ID',
            'TiposDeCategorias.Categoria as nombre_categoria',
            'Categorias.Categoria'
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
        ->setTableId('categorias-table')
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
                    window.location = '" . route('categorias.create') . "';
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
                    window.LaravelDataTables["categorias-table"].ajax.reload();
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
            'ID' => [
                'title' => 'ID',
                'data' => 'ID',
                'name' => 'ID',
            ],
            'TipoID' => [
                'title' => 'Tipo',
                'data' => 'nombre_categoria',
                'name' => 'TiposDeCategorias.Categoria',
            ],
            'Categoria' => [
                'title' => 'Categoria',
                'data' => 'Categoria',
                'name' => 'Categoria',
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
        return 'categorias_datatable_' . time();
    }
}
