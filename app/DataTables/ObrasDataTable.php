<?php

namespace App\DataTables;

use App\Models\Obras;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class ObrasDataTable extends DataTable
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
            ->addColumn('estado_formatted', function ($row) {
                // Debug temporal - mostrar el valor real
                $estadoValue = $row->estado;
                $tipo = gettype($estadoValue);
                
                if ($row->estado == 1 || $row->estado === true || $row->estado === '1') {
                    return '<span class="badge badge-success">Si</span> <small>(' . $estadoValue . ':' . $tipo . ')</small>';
                } else {
                    return '<span class="badge badge-danger">No</span> <small>(' . $estadoValue . ':' . $tipo . ')</small>';
                }
            })
            ->addColumn('action', function ($row) {
                return view('obras.datatables_actions', ['id' => $row->ObraID])->render();
            })
            ->rawColumns(['estado_formatted', 'action'])
            ->setRowId('ObraID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Obras $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Obras $model)
    {


        return $model->newQuery()
            ->join('unidadesdenegocio', 'obras.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->select([
                'obras.ObraID',
                'obras.NombreObra',
                'obras.Direccion',
                'obras.EncargadoDeObra',
                'obras.estado',
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
        return $this->indexPageHtml('obras-table');
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [

            'ObraID' => [
                'title' => 'ID',
                'data' => 'ObraID',
                'name' => 'ObraID',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombreObra' => [
                'title' => 'Nombre Obra',
                'data' => 'NombreObra',
                'name' => 'NombreObra',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Direccion' => [
                'title' => 'Dirección',
                'data' => 'Direccion',
                'name' => 'Direccion',
                'class' => 'dark:bg-[#101010] dark:text-white'

            ],
            'EncargadoDeObra' => [
                'title' => 'Encargado Obra',
                'data' => 'EncargadoDeObra',
                'name' => 'EncargadoDeObra',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'UnidadNegocioID' => [
                'title' => 'Unidad Negocio',
                'data' => 'nombre_empresa',
                'name' => 'unidadesdenegocio.NombreEmpresa',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'estado' => [
                'title' => 'Es obra',
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
        return 'obras_datatable_' . time();
    }
}
