<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Illuminate\Support\Str;

class TicketsListaUpdater extends Component
{
    // Solo necesitamos escuchar el evento para forzar recargas si es necesario
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
        $tickets = $this->fetchTickets();

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
        return Tickets::procesarTiemposProgreso($tickets);
    }
}