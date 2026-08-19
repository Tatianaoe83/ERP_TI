<?php

namespace App\DataTables;

use App\Models\Planes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class PlanesDataTable extends DataTable
{
    use HasIndexPageHtml;
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
            ->addColumn('action', function ($row) {
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
        return $this->indexPageHtml('planes-table');
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
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CompaniaID' => [
                'title' => 'Compania',
                'data' => 'nombre_compania',
                'name' => 'companiaslineastelefonicas.Compania',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombrePlan' => [
                'title' => 'Nombre Plan',
                'data' => 'NombrePlan',
                'name' => 'NombrePlan',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'PrecioPlan' => [
                'title' => 'Precio Plan',
                'data' => 'PrecioPlan',
                'name' => 'PrecioPlan',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center dark:bg-[#101010] dark:text-white')

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
