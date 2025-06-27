<?php

namespace App\DataTables;

use App\Models\Empleados;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class InventarioDataTable extends DataTable
{
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
                return view('inventarios.datatables_actions', ['id' => $row->EmpleadoID])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('EmpleadoID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Inventario $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Inventario $model)
    {
        return $model->newQuery()
            ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->select([
                'empleados.EmpleadoID',
                'empleados.NombreEmpleado',
                'puestos.NombrePuesto as nombre_puesto',
                'obras.NombreObra as nombre_obra',
                'empleados.NumTelefono',
                'empleados.Correo',
                'empleados.Estado'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('inventarios-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(1, 'asc')
            ->buttons([


                [
                    'className' => 'btn btn-default',
                    'text' => '<i class="fa fa-sync-alt"></i> Recargar',
                    'action' => 'function() { 
                    window.LaravelDataTables["inventarios-table"].ajax.reload();
                }'
                ],
            ])
            ->parameters([
                'processing' => true,
                'serverSide' => true,
                'responsive' => true,
                'pageLength' => 10,
                'searching' => true,
                'language' => [
                    'url' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                ],
                'drawCallback' => 'function() {
                $("[data-toggle=tooltip]").tooltip();
            }',
                'initComplete' => "function() {
                this.api().columns().every(function () {
                    var column = this;
                    var input = document.createElement(\"input\");
                    $(input).appendTo($(column.footer()).empty())
                    .on('change', function () {
                        column.search($(this).val(), false, false, true).draw();
                    });
                });
            }",
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
            'EmpleadoID' => [
                'title' => 'ID',
                'data' => 'EmpleadoID',
                'name' => 'EmpleadoID',
            ],
            'NombreEmpleado' => [
                'title' => 'Nombre Empleado',
                'data' => 'NombreEmpleado',
                'name' => 'NombreEmpleado',
            ],
            'PuestoID' => [
                'title' => 'Puesto',
                'data' => 'nombre_puesto',
                'name' => 'puestos.NombrePuesto',
            ],
            'ObraID' => [
                'title' => 'Obra',
                'data' => 'nombre_obra',
                'name' => 'obras.NombreObra',
            ],
            'NumTelefono' => [
                'title' => 'Num Telefono',
                'data' => 'NumTelefono',
                'name' => 'NumTelefono',
            ],
            'Correo' => [
                'title' => 'Correo',
                'data' => 'Correo',
                'name' => 'Correo',
            ],
            'Estado' => [
                'title' => 'Estado',
                'data' => 'Estado',
                'name' => 'Estado',
            ],

            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'inventarios_datatable_' . time();
    }
}
