<?php

namespace App\DataTables;

use App\Models\Equipos;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class EquiposDataTable extends DataTable
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
                return view('equipos.datatables_actions', ['id' => $row->ID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('ID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Equipos $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Equipos $model)
    {
        return $model->newQuery()
            ->join('categorias', 'equipos.CategoriaID', '=', 'categorias.ID')
            ->select([
                'equipos.ID',
                'categorias.Categoria as categoria_name',
                'equipos.Marca',
                'equipos.Caracteristicas',
                'equipos.Modelo',
                'equipos.Precio'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('equipos-table');
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
            'CategoriaID' => [
                'title' => 'Categoria',
                'data' => 'categoria_name',
                'name' => 'categorias.Categoria',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Marca' => [
                'title' => 'Marca',
                'data' => 'Marca',
                'name' => 'Marca',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Caracteristicas' => [
                'title' => 'Caracteristicas',
                'data' => 'Caracteristicas',
                'name' => 'Caracteristicas',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Modelo' => [
                'title' => 'Modelo',
                'data' => 'Modelo',
                'name' => 'Modelo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Precio' => [
                'title' => 'Precio',
                'data' => 'Precio',
                'name' => 'Precio',
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
        return 'equipos_datatable_' . time();
    }
}
