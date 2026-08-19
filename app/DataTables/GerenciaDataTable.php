<?php

namespace App\DataTables;

use App\Models\Gerencia;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class GerenciaDataTable extends DataTable
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
                return view('gerencias.datatables_actions', ['id' => $row->GerenciaID])->render();
            })
            ->addColumn('estado_formatted', function ($row) {
                if ($row->estado == 1 || $row->estado === true || $row->estado === '1') {
                    return '<span class="badge badge-success">Si</span>';
                } else {
                    return '<span class="badge badge-danger">No</span>';
                }
            })
            ->rawColumns(['action', 'estado_formatted'])
            ->setRowId('GerenciaID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Gerencia $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Gerencia $model)
    {
        return $model->newQuery()
            ->join('unidadesdenegocio', 'gerencia.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->select([
                'gerencia.GerenciaID',
                'gerencia.NombreGerencia',
                'gerencia.NombreGerente',
                'gerencia.estado',
                'unidadesdenegocio.NombreEmpresa as nombre_empresa'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('gerencias-table');
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [

            'GerenciaID' => [
                'title' => 'ID',
                'data' => 'GerenciaID',
                'name' => 'GerenciaID',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombreGerencia' => [
                'title' => 'Nombre Gerencia',
                'data' => 'NombreGerencia',
                'name' => 'NombreGerencia',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'UnidadNegocioID' => [
                'title' => 'Unidad Negocio',
                'data' => 'nombre_empresa',
                'name' => 'unidadesdenegocio.NombreEmpresa',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombreGerente' => [
                'title' => 'Nombre Gerente',
                'data' => 'NombreGerente',
                'name' => 'NombreGerente',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'estado' => [
                'title' => 'Es gerencia',
                'data' => 'estado_formatted',
                'name' => 'estado',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'orderable' => true,
                'searchable' => false
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
        return 'gerencias_datatable_' . time();
    }
}
