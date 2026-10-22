<?php

namespace Database\Seeders;

use App\Models\InventarioInsumo;
use Illuminate\Database\Seeder;

class SeederPuNeodata extends Seeder
{
    public function run()
    {
        // Uno por uno para que quede en la auditoría
        $asignaciones = InventarioInsumo::where('NombreInsumo', 'PU NEODATA')
            ->whereNull('deleted_at')
            ->whereNotNull('EmpleadoID')
            ->get();

        foreach ($asignaciones as $asignacion) {
            $asignacion->update(['MesDePago' => 'Febrero']);
        }

        $this->command->info("PU NEODATA actualizados a Febrero: {$asignaciones->count()}");
    }
}
