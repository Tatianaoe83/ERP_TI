<?php

namespace App\DataTables;

use App\Models\Categorias;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class CategoriasDataTable extends DataTable
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
                return view('categorias.datatables_actions', ['id' => $row->ID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('ID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Categorias $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Categorias $model)
    {

        return $model->newQuery()
            ->join('tiposdecategorias', 'categorias.TipoID', '=', 'tiposdecategorias.ID')
            ->select([
                'categorias.ID',
                'tiposdecategorias.Categoria as nombre_categoria',
                'categorias.Categoria'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('categorias-table');
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
            'TipoID' => [
                'title' => 'Tipo',
                'data' => 'nombre_categoria',
                'name' => 'tiposdecategorias.Categoria',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Categoria' => [
                'title' => 'Categoria',
                'data' => 'Categoria',
                'name' => 'Categoria',
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
        return 'categorias_datatable_' . time();
    }
}
