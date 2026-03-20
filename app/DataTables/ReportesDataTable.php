<?php

namespace App\DataTables;

use App\Models\Reportes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class ReportesDataTable extends DataTable
{
    public function dataTable($query)
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($row) {
                return view('reportes.datatables_actions', ['id' => $row->id])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(Reportes $model)
    {
        return $model->newQuery()->select(['id', 'title']);
    }

    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '120px', 'printable' => false, 'class' => 'dark:bg-[#101010] dark:text-white'])
            ->parameters([
                'dom'          => 'Bfrtip',
                'responsive'   => true,
                'stateSave'    => true,
                'processing'   => true, // spinner mientras carga
                'serverSide'   => true, 
                'order'        => [[0, 'desc']],
                'pageLength'   => 25,
                'lengthMenu'   => [[25, 50, 100, 250], [25, 50, 100, 250]],
                'language'     => [
                    'processing'  => '<span class="spinner-border spinner-border-sm"></span> Cargando...',
                    'zeroRecords' => 'No se encontraron registros.',
                    'info'        => 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    'infoEmpty'   => 'Sin registros disponibles',
                    'search'      => 'Buscar:',
                    'paginate'    => ['first' => 'Primero', 'last' => 'Último', 'next' => 'Siguiente', 'previous' => 'Anterior'],
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'id',    'title' => 'ID',                  'width' => '5%',  'class' => 'dark:bg-[#101010] dark:text-white'],
            ['data' => 'title', 'title' => 'Nombre del Reporte',                    'class' => 'dark:bg-[#101010] dark:text-white'],
        ];
    }

    protected function filename()
    {
        return 'reportes_datatable_' . time();
    }
}