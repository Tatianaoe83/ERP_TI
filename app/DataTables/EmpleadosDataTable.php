<?php

namespace App\DataTables;

use App\Models\Empleados;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;
use Yajra\DataTables\Html\Column;

class EmpleadosDataTable extends DataTable
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
                return view('empleados.datatables_actions', [
                    'id' => $row->EmpleadoID,
                    'activo' => $row->Estado == 1 || $row->Estado === true,
                    'tipo_persona' => $row->tipo_persona,
                ])->render();
            })
            ->editColumn('nombre_presupuesto', function ($row) {
                return $row->nombre_presupuesto ?: '—';
            })
            ->editColumn('Estado', function ($row) {
                if ($row->Estado == 1 || $row->Estado === true) {
                    return '<span class="erp-chip erp-chip--ok">Activo</span>';
                } else {
                    return '<span class="erp-chip erp-chip--neutro">Inactivo</span>';
                }
            })
            ->rawColumns(['action', 'Estado'])
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
            ->leftJoin('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->leftJoin('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->leftJoin('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
            ->leftJoin('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
            ->leftJoin('centrosdecostos', 'empleados.CentroCostoID', '=', 'centrosdecostos.CentroCostoID')
            ->leftJoin('presupuestos_internos', function ($join) {
                $join->on('empleados.PresupuestoInternoID', '=', 'presupuestos_internos.PresupuestoInternoID')
                    ->whereNull('presupuestos_internos.deleted_at');
            })
            ->leftJoin('empleados as jefe', 'empleados.JefeDirectoID', '=', 'jefe.EmpleadoID')
            ->select([
                'empleados.EmpleadoID',
                'empleados.NombreEmpleado',
                'puestos.NombrePuesto as nombre_puesto',
                'obras.NombreObra as nombre_obra',
                'centrosdecostos.NombreCentro as nombre_centro',
                'presupuestos_internos.Nombre as nombre_presupuesto',
                'departamentos.NombreDepartamento as nombre_departamento',
                'gerencia.NombreGerencia as nombre_gerencia',
                'jefe.NombreEmpleado as nombre_jefe',
                'empleados.NumTelefono',
                'empleados.Correo',
                'empleados.Estado',
                'empleados.tipo_persona'
            ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('tabla-empleados', [
            'parameters' => [
                'initComplete' => "function() {
                    if (window.IndexPage) {
                        window.IndexPage.init(this.api());
                    }
                    if (typeof cargarOpcionesFiltros === 'function') {
                        cargarOpcionesFiltros();
                    }
                    if (typeof configurarFiltros === 'function') {
                        configurarFiltros();
                    }
                }",
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
            'EmpleadoID' => [
                'title' => 'ID',
                'data' => 'EmpleadoID',
                'name' => 'empleados.EmpleadoID',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NombreEmpleado' => [
                'title' => 'Nombre Empleado',
                'data' => 'NombreEmpleado',
                'name' => 'empleados.NombreEmpleado',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'PresupuestoInternoID' => [
                'title' => 'Presupuesto interno',
                'data' => 'nombre_presupuesto',
                'name' => 'presupuestos_internos.Nombre',
                'class' => 'dark:bg-[#101010] dark:text-white',
                'responsivePriority' => 2,
            ],
            'PuestoID' => [
                'title' => 'Puesto',
                'data' => 'nombre_puesto',
                'name' => 'puestos.NombrePuesto',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'ObraID' => [
                'title' => 'Ubicación',
                'data' => 'nombre_obra',
                'name' => 'obras.NombreObra',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CentroCostoID' => [
                'title' => 'Centro de costos',
                'data' => 'nombre_centro',
                'name' => 'centrosdecostos.NombreCentro',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'DepartamentoID' => [
                'title' => 'Área',
                'data' => 'nombre_departamento',
                'name' => 'departamentos.NombreDepartamento',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'GerenciaID' => [
                'title' => 'Gerencia',
                'data' => 'nombre_gerencia',
                'name' => 'gerencia.NombreGerencia',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'JefeDirectoID' => [
                'title' => 'Jefe inmediato',
                'data' => 'nombre_jefe',
                'name' => 'jefe.NombreEmpleado',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NumTelefono' => [
                'title' => 'Num Telefono',
                'data' => 'NumTelefono',
                'name' => 'empleados.NumTelefono',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Correo' => [
                'title' => 'Correo',
                'data' => 'Correo',
                'name' => 'empleados.Correo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'tipo_persona' => [
                'title' => 'Tipo Persona',
                'data' => 'tipo_persona',
                'name' => 'empleados.tipo_persona',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Estado' => [
                'title' => 'Estado',
                'data' => 'Estado',
                'name' => 'empleados.Estado',
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
        return 'empleados_datatable_' . time();
    }
}
