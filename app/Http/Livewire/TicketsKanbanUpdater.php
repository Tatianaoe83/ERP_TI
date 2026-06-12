<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Illuminate\Support\Str;

class TicketsKanbanUpdater extends Component
{
   protected function getListeners()
    {
        return [
            // Escucha local (la que ya usabas desde Alpine u otros componentes)
            'ticket-estatus-actualizado' => '$refresh',
            
            // Escucha en tiempo real vía WebSockets usando Laravel Echo
            "echo:tickets-channel,TicketUpdatedEvent" => '$refresh',
        ];
    }

    private function fetchTickets()
    {
        return Tickets::with([
            'empleado',
            'responsableTI',
            'tipoticket',
            'chat' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    private function formatearTickets($tickets)
    {
        return $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->TicketID,
                'descripcion' => $ticket->Descripcion,
                'code_anydesk' => $ticket->CodeAnyDesk ?? '',
                'numero' => $ticket->Numero ?? '',
                'prioridad' => $ticket->Prioridad,
                'estatus' => $ticket->Estatus,
                'empleado' => $ticket->empleado ? [
                    'nombre' => $ticket->empleado->NombreEmpleado,
                    'correo' => $ticket->empleado->Correo ?? '',
                ] : null,
                'responsable' => $ticket->responsableTI ? [
                    'nombre' => $ticket->responsableTI->NombreEmpleado,
                ] : null,
                'created_at' => optional($ticket->created_at)->toIso8601String(),
                'fecha_inicio_progreso' => optional($ticket->FechaInicioProgreso)->toIso8601String(),
                'updated_at' => optional($ticket->updated_at)->toIso8601String(),
            ];
        })->toArray();
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
                    'responsable' => $ticket->responsableTI
                        ? $ticket->responsableTI->NombreEmpleado
                        : 'Sin asignar',
                    'empleado' => $ticket->empleado
                        ? $ticket->empleado->NombreEmpleado
                        : 'Sin empleado',
                    'prioridad' => $ticket->Prioridad,
                    'tiempo_estimado' => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta' => round($tiempoTranscurrido, 2),
                    'tiempo_excedido' => $tiempoExcedido,
                    'porcentaje_excedido' => $porcentajeExcedido,
                    'categoria' => $ticket->tipoticket
                        ? $ticket->tipoticket->NombreTipo
                        : 'Sin categoría'
                ];
            }
        }

        usort($ticketsExcedidos, function ($a, $b) {
            return $b['tiempo_excedido'] <=> $a['tiempo_excedido'];
        });

        return [
            'tiemposProgreso' => $tiemposProgreso,
            'ticketsExcedidos' => $ticketsExcedidos,
        ];
    }

    public function render()
    {
        $tickets = $this->fetchTickets();

        $ticketsStatus = [
            'nuevos' => $this->formatearTickets(
                $tickets->filter(function ($ticket) {
                    return strtolower(trim($ticket->Estatus)) === 'pendiente';
                })->values()
            ),
            'proceso' => $this->formatearTickets(
                $tickets->filter(function ($ticket) {
                    $estatus = strtolower(trim($ticket->Estatus));
                    return $estatus === 'en progreso' || $estatus === 'en proceso';
                })->values()
            ),
            'resueltos' => $this->formatearTickets(
                $tickets->filter(function ($ticket) {
                    $estatus = strtolower(trim($ticket->Estatus));
                    return $estatus === 'cerrado' || $estatus === 'resuelto';
                })->values()
            ),
        ];
        $tiempos = $this->procesarTiempos($tickets);

        $this->emit('tickets-actualizados-kanban', [
            'ticketsStatus'    => $ticketsStatus,
            'ticketsExcedidos' => $tiempos['ticketsExcedidos'],
            'tiemposProgreso'  => $tiempos['tiemposProgreso'],
            'hash'             => md5(json_encode($ticketsStatus)),
        ]);

        return view('livewire.tickets-kanban-updater', [
            'ticketsStatus'    => $ticketsStatus,
            'ticketsExcedidos' => $tiempos['ticketsExcedidos'],
            'tiemposProgreso'  => $tiempos['tiemposProgreso'],
        ]);
    }
}
