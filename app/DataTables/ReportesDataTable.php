<?php

namespace App\DataTables;

use App\Models\Reportes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;


class ReportesDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($row) {
                return view('reportes.datatables_actions', ['id' => $row->id])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Reportes $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Reportes $model)
    {
        return $model->newQuery()->select(['id', 'title', 'query_details']);
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
            ->addAction(['width' => '120px', 'printable' => false, 'class' => 'dark:bg-[#101010] dark:text-white'])
            ->parameters([
                'dom'       => 'Bfrtip',
                'stateSave' => true,
                'order'     => [[0, 'desc']],
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
            ['data' => 'id', 'title' => 'ID', 'width' => '5%', 'class' => 'dark:bg-[#101010] dark:text-white'],
            ['data' => 'title', 'title' => 'Nombre del Reporte', 'class' => 'dark:bg-[#101010] dark:text-white'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'reportes_datatable_' . time();
    }
}
