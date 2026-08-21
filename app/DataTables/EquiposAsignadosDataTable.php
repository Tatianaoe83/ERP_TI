<?php

namespace App\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\QueryDataTable;
use App\DataTables\Concerns\HasIndexPageHtml;

class EquiposAsignadosDataTable extends DataTable
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
        $dataTable = new QueryDataTable($query);

        return $dataTable
            ->setRowId('InventarioID');
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {

        $filtros = request()->only(['empleado_id', 'equipo_id', 'fecha_desde', 'fecha_hasta', 'gerencia_id','categoria_nombre','marca']);
        
        $query = DB::table('inventarioequipo')
            ->join('empleados', 'inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID')
            ->select([
                'inventarioequipo.InventarioID',
                'empleados.NombreEmpleado as empleado_nombre',
                'inventarioequipo.GerenciaEquipo',
                'inventarioequipo.Marca',
                'inventarioequipo.Modelo',
                'inventarioequipo.Folio',
                'inventarioequipo.Caracteristicas',
                'inventarioequipo.NumSerie',
                'inventarioequipo.FechaAsignacion',
                'inventarioequipo.CategoriaEquipo',
            ]);

        // Aplicar filtros
        if (!empty($filtros['empleado_id'])) {
            $query->where('inventarioequipo.EmpleadoID', $filtros['empleado_id']);
        }

        if (!empty($filtros['equipo_id'])) {
            $query->where('inventarioequipo.EquipoID', $filtros['equipo_id']);
        }

        if (!empty($filtros['marca'])) {
            $query->where('inventarioequipo.Marca', $filtros['marca']);
        }

        if (!empty($filtros['categoria_nombre'])) {
            $query->where('inventarioequipo.CategoriaEquipo', $filtros['categoria_nombre']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('inventarioequipo.FechaAsignacion', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('inventarioequipo.FechaAsignacion', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['gerencia_id'])) {
            $query->where('inventarioequipo.GerenciaEquipoID', $filtros['gerencia_id']);
        }

        return $query->orderBy('inventarioequipo.InventarioID', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->indexPageHtml('equipos-asignados-table', [
            'orderColumn' => 8,
            'orderDir' => 'desc',
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
            'empleado_nombre' => [
                'title' => 'Empleado',
                'data' => 'empleado_nombre',
                'name' => 'empleados.NombreEmpleado',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'GerenciaEquipo' => [
                'title' => 'Gerencia',
                'data' => 'GerenciaEquipo',
                'name' => 'inventarioequipo.GerenciaEquipo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Marca' => [
                'title' => 'Marca',
                'data' => 'Marca',
                'name' => 'inventarioequipo.Marca',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Modelo' => [
                'title' => 'Modelo',
                'data' => 'Modelo',
                'name' => 'inventarioequipo.Modelo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Folio' => [
                'title' => 'Folio',
                'data' => 'Folio',
                'name' => 'inventarioequipo.Folio',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'Caracteristicas' => [
                'title' => 'Caracteristicas',
                'data' => 'Caracteristicas',
                'name' => 'inventarioequipo.Caracteristicas',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'NumSerie' => [
                'title' => 'Número de Serie',
                'data' => 'NumSerie',
                'name' => 'inventarioequipo.NumSerie',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'CategoriaEquipo' => [
                'title' => 'Categoria',
                'data' => 'CategoriaEquipo',
                'name' => 'inventarioequipo.CategoriaEquipo',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
            'FechaAsignacion' => [
                'title' => 'Fecha Asignación',
                'data' => 'FechaAsignacion',
                'name' => 'inventarioequipo.FechaAsignacion',
                'class' => 'dark:bg-[#101010] dark:text-white'
            ],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'equipos_asignados_datatable_' . time();
    }
}
