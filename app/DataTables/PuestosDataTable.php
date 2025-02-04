<?php

namespace App\DataTables;

use App\Models\Puestos;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class PuestosDataTable extends DataTable
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
            return view('puestos.datatables_actions', ['id' => $row->PuestoID])->render();
        })
        ->rawColumns(['action'])
        ->setRowId('PuestoID');

    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Puestos $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Puestos $model)
    {

        return $model->newQuery()
        ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
        ->select([
            'puestos.PuestoID',
            'puestos.NombrePuesto',
            'departamentos.NombreDepartamento as nombre_departamento'
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
        ->setTableId('puestos-table')
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
                    window.location = '" . route('puestos.create') . "';
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
                    window.LaravelDataTables["puestos-table"].ajax.reload();
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
            'PuestoID' => [
                'title' => 'ID',
                'data' => 'PuestoID',
                'name' => 'PuestoID',
            ],  
            'NombrePuesto' => [
                'title' => 'Nombre Puesto',
                'data' => 'NombrePuesto',
                'name' => 'NombrePuesto',
            ],
            'DepartamentoID' => [
                'title' => 'Departamento',
                'data' => 'nombre_departamento',
                'name' => 'departamentos.NombreDepartamento',
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
        return 'puestos_datatable_' . time();
    }
}
