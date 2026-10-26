<?php

namespace Database\Seeders;

use App\Models\Gerencia;
use Illuminate\Database\Seeder;

class SeederBorrarGerenciasSinInventario extends Seeder
{
    public function run()
    {
        $gerencias = Gerencia::query()
            ->whereNotExists(function ($q) {
                $q->from('inventarioequipo')
                    ->whereColumn('inventarioequipo.GerenciaEquipoID', 'gerencia.GerenciaID');
            })
            ->whereNotExists(function ($q) {
                $this->inventarioPorEmpleado($q, 'inventarioequipo');
            })
            ->whereNotExists(function ($q) {
                $this->inventarioPorEmpleado($q, 'inventarioinsumo', true);
            })
            ->whereNotExists(function ($q) {
                $this->inventarioPorEmpleado($q, 'inventariolineas', true);
            })
            ->orderBy('NombreGerencia')
            ->get();

        foreach ($gerencias as $gerencia) {
            $gerencia->delete();
            $this->command->line("Borrada gerencia {$gerencia->GerenciaID} {$gerencia->NombreGerencia}");
        }

        $this->command->info("Gerencias sin inventario borradas: {$gerencias->count()}");
    }

    private function inventarioPorEmpleado($q, string $tabla, bool $respetaBorrado = false): void
    {
        $q->from($tabla)
            ->join('empleados', 'empleados.EmpleadoID', '=', "{$tabla}.EmpleadoID")
            ->join('puestos', 'puestos.PuestoID', '=', 'empleados.PuestoID')
            ->join('departamentos', 'departamentos.DepartamentoID', '=', 'puestos.DepartamentoID')
            ->whereColumn('departamentos.GerenciaID', 'gerencia.GerenciaID');

        if ($respetaBorrado) {
            $q->whereNull("{$tabla}.deleted_at");
        }
    }
}
