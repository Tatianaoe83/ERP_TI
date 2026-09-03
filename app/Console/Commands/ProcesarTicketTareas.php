<?php

namespace App\Console\Commands;

use App\Services\TicketTareaService;
use Illuminate\Console\Command;

class ProcesarTicketTareas extends Command
{
    protected $signature = 'tickets:procesar-tareas
                            {--solo-metricas : Solo generar tareas de métricas mensuales}
                            {--solo-prioridad : Solo actualizar prioridades vencidas}';

    protected $description = 'Genera tareas mensuales de métricas y marca prioridad crítica (+2 días vencida)';

    public function handle(TicketTareaService $service): int
    {
        $soloMetricas = (bool) $this->option('solo-metricas');
        $soloPrioridad = (bool) $this->option('solo-prioridad');

        if (! $soloPrioridad) {
            $creadas = $service->generarTareasMetricas();
            $this->info("Tareas de métricas generadas: {$creadas}");
        }

        if (! $soloMetricas) {
            $actualizadas = $service->actualizarPrioridades();
            $this->info("Prioridades actualizadas: {$actualizadas}");
        }

        return self::SUCCESS;
    }
}
