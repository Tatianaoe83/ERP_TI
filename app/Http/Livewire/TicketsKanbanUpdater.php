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

    private function formatearTickets($tickets, array $tiemposProgreso = [], array $notificacionesMap = [])
    {
        return Tickets::formatearColeccionParaVista($tickets, $tiemposProgreso, $notificacionesMap);
    }

    private function procesarTiempos($tickets)
    {
        return Tickets::procesarTiemposProgreso($tickets);
    }

    private function obtenerPayloadActualizacion()
    {
        $tickets = $this->fetchTickets();
        $tiempos = $this->procesarTiempos($tickets);
        $notificacionesMap = Tickets::mapaNotificacionesPendientes($tickets->pluck('TicketID'));

        $ticketsStatus = [
            'nuevos' => $this->formatearTickets(
                $tickets->where('Estatus', 'Pendiente')->values(),
                $tiempos['tiemposProgreso'],
                $notificacionesMap
            ),
            'proceso' => $this->formatearTickets(
                $tickets->where('Estatus', 'En progreso')->values(),
                $tiempos['tiemposProgreso'],
                $notificacionesMap
            ),
            'resueltos' => $this->formatearTickets(
                $tickets->where('Estatus', 'Cerrado')->values(),
                $tiempos['tiemposProgreso'],
                $notificacionesMap
            ),
        ];

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
