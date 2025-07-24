<?php

namespace App\DataTables;

use App\Models\Cortes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class CortesDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'cortes.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Cortes $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Cortes $model)
    {
        return $model->newQuery()
            ->join('gerencia', 'cortes.GerenciaID', '=', 'gerencia.GerenciaID')
            ->join('insumos', 'cortes.InsumoID', '=', 'insumos.ID')
            ->select([
                'gerencia.NombreGerencia',
                'insumos.NombreInsumo'
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
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '120px', 'printable' => false])
            ->parameters([
                'dom'       => 'Bfrtip',
                'stateSave' => true,
                'order'     => [[0, 'desc']],
                'buttons' => []
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
            'InsumoID' => [
                'title' => 'Insumo',
                'data' => 'NombreInsumo',
                'name' => 'NombreInsumo'
            ],
            'Mes',
            'GerenciaID' => [
                'title' => 'Nombre Gerencia',
                'data' => 'NombreGerencia',
                'name' => 'NombreGerencia',
            ],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'cortes_datatable_' . time();
    }
}
