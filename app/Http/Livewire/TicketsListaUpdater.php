<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;

class TicketsListaUpdater extends Component
{
    protected $listeners = ['ticket-estatus-actualizado' => 'actualizarDatos'];

    public function actualizarDatos()
    {
        $this->emit('tickets-actualizados-lista', $this->obtenerPayloadActualizacion());
    }

    private function fetchTickets()
    {
        return Tickets::with([
            'empleado',
            'responsableTI',
            'tipoticket',
            'chat' => function ($query) {
                $query->latest()->limit(1);
            },
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function formatearTickets($tickets, array $tiemposProgreso, array $notificacionesMap)
    {
        return $tickets->map(function ($ticket) use ($tiemposProgreso, $notificacionesMap) {
            $tiempoInfo = $tiemposProgreso[$ticket->TicketID] ?? null;
            $notificaciones = (int) ($notificacionesMap[$ticket->TicketID] ?? 0);

            return Tickets::formatearTicketParaVista($ticket, $tiempoInfo, $notificaciones);
        })->toArray();
    }

    private function obtenerPayloadActualizacion()
    {
        $tickets = $this->fetchTickets();
        $tiempos = Tickets::procesarTiemposProgreso($tickets);
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
            'ticketsStatus'    => $ticketsStatus,
            'ticketsExcedidos' => $tiempos['ticketsExcedidos'],
            'tiemposProgreso'  => $tiempos['tiemposProgreso'],
            'hash'             => md5(json_encode($tickets->map(fn ($ticket) => [
                $ticket->TicketID,
                $ticket->Estatus,
                optional($ticket->updated_at)->timestamp,
            ])->values()->all())),
        ];
    }

    public function render()
    {
        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.tickets-lista-updater', [
            'ticketsStatus'    => $payload['ticketsStatus'],
            'ticketsExcedidos' => $payload['ticketsExcedidos'],
            'tiemposProgreso'  => $payload['tiemposProgreso'],
        ]);
    }
}
