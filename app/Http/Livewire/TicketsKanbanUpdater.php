<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Illuminate\Support\Str;

class TicketsKanbanUpdater extends Component
{
    protected $listeners = ['ticket-estatus-actualizado' => 'actualizarDatos'];

    public function actualizarDatos()
    {
        $this->emit('tickets-actualizados-kanban', $this->obtenerPayloadActualizacion());
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
        return Tickets::procesarTiemposProgreso($tickets);
    }

    private function obtenerPayloadActualizacion()
    {
        $tickets = $this->fetchTickets();

        $ticketsStatus = [
            'nuevos' => $this->formatearTickets(
                $tickets->where('Estatus', 'Pendiente')->values()
            ),
            'proceso' => $this->formatearTickets(
                $tickets->where('Estatus', 'En progreso')->values()
            ),
            'resueltos' => $this->formatearTickets(
                $tickets->where('Estatus', 'Cerrado')->values()
            ),
        ];
        $tiempos = $this->procesarTiempos($tickets);

        return [
            'ticketsStatus' => $ticketsStatus,
            'ticketsExcedidos' => $tiempos['ticketsExcedidos'],
            'tiemposProgreso' => $tiempos['tiemposProgreso'],
            'hash' => md5(json_encode($tickets->map(fn ($ticket) => [
                $ticket->TicketID,
                $ticket->Estatus,
                optional($ticket->updated_at)->timestamp,
            ])->values()->all())),
        ];
    }

    public function render()
    {
        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.tickets-kanban-updater', [
            'ticketsStatus'    => $payload['ticketsStatus'],
            'ticketsExcedidos' => $payload['ticketsExcedidos'],
            'tiemposProgreso'  => $payload['tiemposProgreso'],
        ]);
    }
}
