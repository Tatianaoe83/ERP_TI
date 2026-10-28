<?php

namespace Database\Seeders;

use App\Models\Planes;
use Illuminate\Database\Seeder;

class SeederBorrarPlanesSinLineas extends Seeder
{
    public function run()
    {
        // Sin ninguna línea viva ni asignación viva que lo use
        $planes = Planes::whereNotExists(function ($q) {
            $q->from('lineastelefonicas')->whereColumn('lineastelefonicas.PlanID', 'planes.ID')->whereNull('lineastelefonicas.deleted_at');
        })->whereNotExists(function ($q) {
            $q->from('inventariolineas')->whereColumn('inventariolineas.PlanID', 'planes.ID')->whereNull('inventariolineas.deleted_at');
        })->get();

        // Uno por uno (soft delete) para que quede en la auditoría
        foreach ($planes as $plan) {
            $plan->delete();
            $this->command->line("Borrado plan {$plan->ID} " . trim($plan->NombrePlan));
        }

        $this->command->info("Planes sin líneas borrados: {$planes->count()}");
    }
}
