<?php

namespace App\DataTables;

use App\Models\Direccion;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class DireccionDataTable extends DataTable
{
    use HasIndexPageHtml;

    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('action', function ($row) {
                return view('direcciones.datatables_actions', ['id' => $row->DireccionID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('DireccionID');
    }

    public function query(Direccion $model)
    {
        return $model->newQuery()
            ->join('unidadesdenegocio', 'direcciones.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID')
            ->select([
                'direcciones.DireccionID',
                'direcciones.NombreDireccion',
                'unidadesdenegocio.NombreEmpresa as nombre_empresa',
            ]);
    }

    public function html()
    {
        return $this->indexPageHtml('direcciones-table');
    }

    protected function getColumns()
    {
        return [
            'DireccionID' => [
                'title' => 'ID',
                'data' => 'DireccionID',
                'name' => 'direcciones.DireccionID',
                'class' => 'dark:bg-[#101010] dark:text-white',
            ],
            'NombreDireccion' => [
                'title' => 'Nombre dirección',
                'data' => 'NombreDireccion',
                'name' => 'direcciones.NombreDireccion',
                'class' => 'dark:bg-[#101010] dark:text-white',
            ],
            'UnidadNegocioID' => [
                'title' => 'Unidad de negocio',
                'data' => 'nombre_empresa',
                'name' => 'unidadesdenegocio.NombreEmpresa',
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
        return 'direcciones_datatable_' . time();
    }
}
