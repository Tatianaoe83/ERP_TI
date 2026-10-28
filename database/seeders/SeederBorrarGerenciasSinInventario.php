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
                $q->from('departamentos')
                    ->join('puestos', function ($join) {
                        $join->on('puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
                            ->whereNull('puestos.deleted_at');
                    })
                    ->join('empleados', function ($join) {
                        $join->on('empleados.PuestoID', '=', 'puestos.PuestoID')
                            ->whereNull('empleados.deleted_at');
                    })
                    ->whereColumn('departamentos.GerenciaID', 'gerencia.GerenciaID')
                    ->whereNull('departamentos.deleted_at');
            })
            ->orderBy('NombreGerencia')
            ->get();

        foreach ($gerencias as $gerencia) {
            $gerencia->delete();
            $this->command->line("Borrada gerencia {$gerencia->GerenciaID} {$gerencia->NombreGerencia}");
        }

        $this->command->info("Gerencias sin gente borradas: {$gerencias->count()}");
    }
}
