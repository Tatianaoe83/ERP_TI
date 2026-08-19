<?php

namespace App\DataTables;

use App\Models\Departamentos;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class DepartamentosDataTable extends DataTable
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
                return view('departamentos.datatables_actions', ['id' => $row->DepartamentoID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('DepartamentoID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Departamentos $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Departamentos $model)
    {

        return $model->newQuery()
            ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
            ->select([
                'departamentos.DepartamentoID',
                'departamentos.NombreDepartamento',
                'gerencia.NombreGerencia as nombre_gerencia'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('departamentos-table');
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'DepartamentoID' => [
                'title' => 'ID',
                'data' => 'DepartamentoID',
                'name' => 'DepartamentoID',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],

            'NombreDepartamento' => [
                'title' => 'Nombre Departamento',
                'data' => 'NombreDepartamento',
                'name' => 'NombreDepartamento',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'GerenciaID' => [
                'title' => 'Nombre Gerencia',
                'data' => 'nombre_gerencia',
                'name' => 'gerencia.NombreGerencia',
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
        return 'departamentos_datatable_' . time();
    }
}
