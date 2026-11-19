<?php

namespace App\DataTables;

use App\Models\Division;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class DivisionDataTable extends DataTable
{
    use HasIndexPageHtml;

    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('action', function ($row) {
                return view('divisiones.datatables_actions', ['id' => $row->DivisionID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('DivisionID');
    }

    public function query(Division $model)
    {
        return $model->newQuery()->select([
            'divisiones.DivisionID',
            'divisiones.NombreDivision',
        ]);
    }

    public function html()
    {
        return $this->indexPageHtml('divisiones-table');
    }

    protected function getColumns()
    {
        return [
            'DivisionID' => [
                'title' => 'ID',
                'data' => 'DivisionID',
                'name' => 'divisiones.DivisionID',
                'class' => 'dark:bg-[#101010] dark:text-white',
            ],
            'NombreDivision' => [
                'title' => 'Nombre división',
                'data' => 'NombreDivision',
                'name' => 'divisiones.NombreDivision',
                'class' => 'dark:bg-[#101010] dark:text-white',
            ],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center dark:bg-[#101010] dark:text-white'),
        ];
    }

    protected function filename()
    {
        return 'divisiones_datatable_' . time();
    }
}
