<?php

namespace App\DataTables;

use App\Models\Reportes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;

class ReportesDataTable extends DataTable
{
    use HasIndexPageHtml;

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
        return $this->indexPageHtml('reportes-table', [
            'orderColumn' => 0,
            'orderDir' => 'desc',
        ]);
    }

    protected function getColumns()
    {
        return [
            'id' => [
                'title' => 'ID',
                'data' => 'id',
                'name' => 'id',
                'width' => '5%',
            ],
            'title' => [
                'title' => 'Nombre del reporte',
                'data' => 'title',
                'name' => 'title',
            ],
        ];
    }

    protected function filename()
    {
        return 'reportes_datatable_' . time();
    }
}
