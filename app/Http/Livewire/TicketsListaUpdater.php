<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Illuminate\Support\Str;

class TicketsListaUpdater extends Component
{
    // Solo necesitamos escuchar el evento para forzar recargas si es necesario
    protected $listeners = ['ticket-estatus-actualizado' => '$refresh'];

    public function render()
    {
        // 1. Hacemos la consulta directamente aquí
        $tickets = Tickets::with([
            'empleado',
            'responsableTI',
            'tipoticket',
            'chat' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // 2. Filtramos los datos
        $ticketsNuevos = $tickets->where('Estatus', 'Pendiente')->values();
        $ticketsProceso = $tickets->where('Estatus', 'En progreso')->values();
        $ticketsResueltos = $tickets->where('Estatus', 'Cerrado')->values();

        // 3. Procesamos los tiempos
        $tiemposProcesados = $this->procesarTiempos($tickets);

        // 4. Se los pasamos a la vista
        return view('livewire.tickets-lista-updater', [
            'ticketsNuevos' => $ticketsNuevos,
            'ticketsProceso' => $ticketsProceso,
            'ticketsResueltos' => $ticketsResueltos,
            'tiemposProgreso' => $tiemposProcesados['tiemposProgreso'],
            'ticketsExcedidos' => $tiemposProcesados['ticketsExcedidos'],
        ]);
    }

    private function procesarTiempos($tickets)
    {
        $ticketsExcedidos = [];
        $tiemposProgreso = [];

        $ticketsEnProgreso = $tickets
            ->where('Estatus', 'En progreso')
            ->whereNotNull('FechaInicioProgreso');

        foreach ($ticketsEnProgreso as $ticket) {

            if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) {
                $tiemposProgreso[$ticket->TicketID] = null;
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;
            $tiempoTranscurrido = $ticket->FechaInicioProgreso
                ? $ticket->FechaInicioProgreso->diffInMinutes(now()) / 60
                : 0;

            $porcentajeUsado = $tiempoEstimadoHoras > 0
                ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100
                : 0;

            $tiemposProgreso[$ticket->TicketID] = [
                'transcurrido' => round($tiempoTranscurrido, 1),
                'estimado' => round($tiempoEstimadoHoras, 1),
                'porcentaje' => round($porcentajeUsado, 1),
                'estado' => $porcentajeUsado >= 100
                    ? 'agotado'
                    : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal')
            ];

            if ($tiempoTranscurrido > $tiempoEstimadoHoras) {
                $tiempoExcedido = round($tiempoTranscurrido - $tiempoEstimadoHoras, 2);
                $porcentajeExcedido = round(($tiempoTranscurrido / $tiempoEstimadoHoras) * 100, 1);

                $ticketsExcedidos[] = [
                    'id' => $ticket->TicketID,
                    'descripcion' => Str::limit($ticket->Descripcion, 80),
                    'responsable' => $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin asignar',
                    'empleado' => $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin empleado',
                    'prioridad' => $ticket->Prioridad,
                    'tiempo_estimado' => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta' => round($tiempoTranscurrido, 2),
                    'tiempo_excedido' => $tiempoExcedido,
                    'porcentaje_excedido' => $porcentajeExcedido,
                    'categoria' => $ticket->tipoticket ? $ticket->tipoticket->NombreTipo : 'Sin categoría'
                ];
            }
        }

        usort($ticketsExcedidos, function ($a, $b) {
            return $b['tiempo_excedido'] <=> $a['tiempo_excedido'];
        });

        // Devolvemos el array para usarlo en el render
        return [
            'tiemposProgreso' => $tiemposProgreso,
            'ticketsExcedidos' => $ticketsExcedidos
        ];
    }
}