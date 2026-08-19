<?php

namespace App\DataTables;

use App\Models\Insumos;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class InsumosDataTable extends DataTable
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
                return view('insumos.datatables_actions', ['id' => $row->ID])->render();
            })
            ->editColumn('Importe', function ($row) {
                if ($row->Importe === null || $row->Importe === '') {
                    return '0.00%';
                }
                return number_format((float)$row->Importe, 2) . '%';
            })
            ->editColumn('FechaRenovacion', function ($row) {
                if (empty($row->FechaRenovacion)) {
                    return 'Sin asignar';
                }
                return \Carbon\Carbon::parse($row->FechaRenovacion)->format('d/m/Y');
            })

            

            
            ->rawColumns(['action'])
            ->setRowId('ID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Insumos $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Insumos $model)
    {

        return $model->newQuery()
            ->join('categorias', 'insumos.CategoriaID', '=', 'categorias.ID')
            ->select([
                'insumos.ID',
                'insumos.NombreInsumo',
                'categorias.Categoria as nombre_categoria',
                'insumos.CostoMensual',
                'insumos.CostoAnual',
                'insumos.Importe',
                'insumos.FrecuenciaDePago',
                'insumos.Observaciones',
                'insumos.FechaRenovacion'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('insumos-table');
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
            'NombreInsumo' => [
                'title' => 'Nombre Insumo',
                'data' => 'NombreInsumo',
                'name' => 'NombreInsumo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CategoriaID' => [
                'title' => 'Categoria',
                'data' => 'nombre_categoria',
                'name' => 'categorias.Categoria',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CostoMensual' => [
                'title' => 'Costo Mensual',
                'data' => 'CostoMensual',
                'name' => 'CostoMensual',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CostoAnual' => [
                'title' => 'Costo Anual',
                'data' => 'CostoAnual',
                'name' => 'CostoAnual',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Importe' => [
                'title' => 'Inflación (%)',
                'data' => 'Importe',
                'name' => 'Importe',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'FrecuenciaDePago' => [
                'title' => 'Frecuencia de Pago',
                'data' => 'FrecuenciaDePago',
                'name' => 'FrecuenciaDePago',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],

            'Observaciones' => [
                'title' => 'Observaciones',
                'data' => 'Observaciones',
                'name' => 'Observaciones',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'FechaRenovacion' => [
                'title' => 'Fecha Renovación',
                'data' => 'FechaRenovacion',
                'name' => 'FechaRenovacion',
                'class' => 'dark:bg-[#101010] dark:text-white',
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
        return 'insumos_datatable_' . time();
    }
}
