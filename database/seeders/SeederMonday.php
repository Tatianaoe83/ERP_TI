<?php

namespace Database\Seeders;

use App\Helpers\PagoMeses;
use App\Models\InventarioInsumo;
use Illuminate\Database\Seeder;

class SeederMonday extends Seeder
{
    public function run()
    {
        // Uno por uno para que quede en la auditoría
        $asignaciones = InventarioInsumo::where('NombreInsumo', 'MONDAY')
            ->whereNull('deleted_at')
            ->whereNotNull('EmpleadoID')
            ->get();

        // Frecuencia derivada de los meses, igual que al guardar desde InventarioController
        foreach ($asignaciones as $asignacion) {
            $asignacion->update([
                'MesDePago' => 'Febrero',
                'FrecuenciaDePago' => PagoMeses::frecuenciaDerivada('Febrero'),
            ]);
        }

        $this->command->info("MONDAY actualizados a Febrero: {$asignaciones->count()}");
    }
}
