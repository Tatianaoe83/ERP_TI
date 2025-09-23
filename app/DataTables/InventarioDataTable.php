<?php

namespace App\DataTables;

use App\Models\Empleados;
use App\Models\Inventario;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

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
            ->addColumn('estado_disponibilidad', function ($row) {
            if ($row->Disponible == 1) {
                return '<span class="badge badge-success" style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Disponible</span>';
            } else {
                return '<span class="badge badge-danger" style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">Asignada</span>';
            }
        })
        ->rawColumns(['action', 'estado_disponibilidad'])
            
            ->setRowId('EmpleadoID');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Empleados $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Empleados $model)
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
                'empleados.Estado',
                DB::raw('CASE 
                    WHEN EXISTS(SELECT 1 FROM inventario_equipo WHERE EmpleadoID = empleados.EmpleadoID) 
                         OR EXISTS(SELECT 1 FROM inventario_insumo WHERE EmpleadoID = empleados.EmpleadoID)
                         OR EXISTS(SELECT 1 FROM inventario_lineas WHERE EmpleadoID = empleados.EmpleadoID)
                    THEN 0 ELSE 1 END as Disponible')
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
                'pageLength' => 7,
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
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombreEmpleado' => [
                'title' => 'Nombre Empleado',
                'data' => 'NombreEmpleado',
                'name' => 'NombreEmpleado',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'PuestoID' => [
                'title' => 'Puesto',
                'data' => 'nombre_puesto',
                'name' => 'puestos.NombrePuesto',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'ObraID' => [
                'title' => 'Obra',
                'data' => 'nombre_obra',
                'name' => 'obras.NombreObra',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NumTelefono' => [
                'title' => 'Num Telefono',
                'data' => 'NumTelefono',
                'name' => 'NumTelefono',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Correo' => [
                'title' => 'Correo',
                'data' => 'Correo',
                'name' => 'Correo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
          
            Column::computed('estado_disponibilidad')
                ->title('Estado')
                ->exportable(false)
                ->printable(false)
                ->width(100)
                ->addClass('text-center dark:bg-[#101010] dark:text-white'),    

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
        return 'inventarios_datatable_' . time();
    }
}
