<?php

namespace App\DataTables;

use App\Models\UnidadesDeNegocio;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class UnidadesDeNegocioDataTable extends DataTable
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
                return view('unidades_de_negocios.datatables_actions', ['id' => $row->UnidadNegocioID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('UnidadNegocioID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\UnidadesDeNegocio $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(UnidadesDeNegocio $model)
    {
        return $model->newQuery()
            ->join('divisiones', 'unidadesdenegocio.DivisionID', '=', 'divisiones.DivisionID')
            ->select([
                'unidadesdenegocio.UnidadNegocioID',
                'unidadesdenegocio.NombreEmpresa',
                'unidadesdenegocio.RFC',
                'unidadesdenegocio.Direccion',
                'unidadesdenegocio.NumTelefono',
                'divisiones.NombreDivision as nombre_division',
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('unidadesDeNegocios-table', [
            'parameters' => [
                'responsive' => [
                    'details' => [
                        'type' => 'column',
                        'target' => 'tr',
                    ],
                ],
            ],
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
            'UnidadNegocioID' => [
                'title' => 'ID',
                'data' => 'UnidadNegocioID',
                'name' => 'unidadesdenegocio.UnidadNegocioID',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 1
            ],
            'DivisionID' => [
                'title' => 'División',
                'data' => 'nombre_division',
                'name' => 'divisiones.NombreDivision',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 2
            ],
            'NombreEmpresa' => [
                'title' => 'Nombre Empresa',
                'data' => 'NombreEmpresa',
                'name' => 'unidadesdenegocio.NombreEmpresa',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 1
            ],
            'RFC' => [
                'title' => 'RFC',
                'data' => 'RFC',
                'name' => 'unidadesdenegocio.RFC',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 2
            ],
            'Direccion' => [
                'title' => 'Dirección',
                'data' => 'Direccion',
                'name' => 'unidadesdenegocio.Direccion',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 3
            ],
            'NumTelefono' => [
                'title' => 'Teléfono',
                'data' => 'NumTelefono',
                'name' => 'unidadesdenegocio.NumTelefono',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 3
            ],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->responsivePriority(1)
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
        return 'unidades_de_negocios_datatable_' . time();
    }
}
