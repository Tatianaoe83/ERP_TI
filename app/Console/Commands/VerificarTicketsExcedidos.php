<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tickets;
use App\Services\TicketNotificationService;
use Illuminate\Support\Facades\Log;

class VerificarTicketsExcedidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:verificar-excedidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar tickets en progreso que excedan el tiempo de respuesta según métricas de categoría';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Verificando tickets en progreso que excedan el tiempo de respuesta...');

        try {
            // Obtener todos los tickets en progreso con sus relaciones
            $tickets = Tickets::with(['tipoticket', 'responsableTI'])
                ->where('Estatus', 'En progreso')
                ->whereNotNull('FechaInicioProgreso')
                ->whereNotNull('TipoID')
                ->get();

            $notificationService = new TicketNotificationService();
            $ticketsExcedidos = 0;
            $notificacionesEnviadas = 0;

            foreach ($tickets as $ticket) {
                // Verificar si el ticket tiene métrica configurada
                if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) {
                    continue;
                }

                // Calcular tiempo de respuesta
                $tiempoRespuesta = $ticket->tiempo_respuesta;
                if ($tiempoRespuesta === null) {
                    continue;
                }

                // Convertir tiempo estimado de minutos a horas
                $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;

                // Verificar si excede
                if ($tiempoRespuesta > $tiempoEstimadoHoras) {
                    $ticketsExcedidos++;
                    $this->line("Ticket #{$ticket->TicketID} excede el tiempo: {$tiempoRespuesta}h > {$tiempoEstimadoHoras}h");
                    
                    // Enviar notificación
                    if ($notificationService->verificarYNotificarExceso($ticket)) {
                        $notificacionesEnviadas++;
                        $this->info("  ✓ Notificación enviada para ticket #{$ticket->TicketID}");
                    } else {
                        $this->warn("  ✗ Error enviando notificación para ticket #{$ticket->TicketID}");
                    }
                }
            }

            $this->info("Verificación completada:");
            $this->info("  - Tickets verificados: {$tickets->count()}");
            $this->info("  - Tickets excedidos: {$ticketsExcedidos}");
            $this->info("  - Notificaciones enviadas: {$notificacionesEnviadas}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error verificando tickets: " . $e->getMessage());
            Log::error("Error en comando verificar tickets excedidos: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
