<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Illuminate\Support\Str;

class TicketsListaUpdater extends Component
{
    // Solo necesitamos escuchar el evento para forzar recargas si es necesario
    protected function getListeners()
    {
        return [
            'ticket-estatus-actualizado' => '$refresh',

            // Escucha en tiempo real vía WebSockets usando Laravel Echo
            "echo:tickets-channel,TicketUpdatedEvent" => '$refresh',
        ];
    }

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
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    private function formatearTickets($tickets, array $tiemposProgreso = [], array $notificacionesMap = [])
    {
        return Tickets::formatearColeccionParaVista($tickets, $tiemposProgreso, $notificacionesMap);
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
        $tickets = $this->fetchTickets();

        // 2. Filtramos los datos
        $ticketsNuevos = $tickets->where('Estatus', 'Pendiente')->values();
        $ticketsProceso = $tickets->where('Estatus', 'En progreso')->values();
        $ticketsResueltos = $tickets->where('Estatus', 'Cerrado')->values();

        // 3. Procesamos los tiempos
        $tiemposProcesados = $this->procesarTiempos($tickets);
        $notificacionesMap = Tickets::mapaNotificacionesPendientes($tickets->pluck('TicketID'));

        $ticketsStatus = [
            'nuevos' => $this->formatearTickets($ticketsNuevos, $tiemposProcesados['tiemposProgreso'], $notificacionesMap),
            'proceso' => $this->formatearTickets($ticketsProceso, $tiemposProcesados['tiemposProgreso'], $notificacionesMap),
            'resueltos' => $this->formatearTickets($ticketsResueltos, $tiemposProcesados['tiemposProgreso'], $notificacionesMap),
        ];

        $this->emit('tickets-actualizados-lista', [
            'ticketsStatus' => $ticketsStatus,
            'ticketsExcedidos' => $tiemposProcesados['ticketsExcedidos'],
            'tiemposProgreso' => $tiemposProcesados['tiemposProgreso'],
            'hash' => md5(json_encode($ticketsStatus)),
        ]);

        return view('livewire.tickets-lista-updater', [
            'ticketsStatus' => $ticketsStatus,
            'tiemposProgreso' => $tiemposProcesados['tiemposProgreso'],
            'ticketsExcedidos' => $tiemposProcesados['ticketsExcedidos'],
        ]);
    }

    
    private function procesarTiempos($tickets)
    {
        return Tickets::procesarTiemposProgreso($tickets);
    }
}
