<?php

namespace App\DataTables;

use App\Models\Puestos;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;
use DB;

class PuestosDataTable extends DataTable
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
            ->join('gerencia', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
            ->select([
                'puestos.PuestoID',
                'puestos.NombrePuesto',
                'departamentos.NombreDepartamento',
                'gerencia.NombreGerencia',
                DB::raw('CONCAT(departamentos.NombreDepartamento, " - ", gerencia.NombreGerencia) as nombre_departamento')
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('puestos-table');
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
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombrePuesto' => [
                'title' => 'Nombre Puesto',
                'data' => 'NombrePuesto',
                'name' => 'NombrePuesto',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'DepartamentoID' => [
                'title' => 'Departamento',
                'data' => 'nombre_departamento',
                'name' => DB::raw('CONCAT(departamentos.NombreDepartamento, " - ", gerencia.NombreGerencia)'),
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
        return 'puestos_datatable_' . time();
    }
}
