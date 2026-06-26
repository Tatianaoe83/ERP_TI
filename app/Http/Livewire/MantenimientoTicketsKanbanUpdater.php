<?php

namespace App\Http\Livewire;

use App\Models\TicketMantenimiento;
use Livewire\Component;

class MantenimientoTicketsKanbanUpdater extends Component
{
    protected $listeners = ['mantenimiento-estatus-actualizado' => 'actualizarDatos'];

    public function actualizarDatos()
    {
        $this->emit('mantenimiento-actualizados-kanban', $this->obtenerPayloadActualizacion());
    }

    private function fetchTickets()
    {
        return TicketMantenimiento::queryConRelaciones()->orderBy('created_at', 'desc')->get();
    }

    private function formatearTickets($tickets)
    {
        return $tickets->map(
            fn ($ticket) => TicketMantenimiento::formatearTicketParaVista($ticket)
        )->toArray();
    }

    private function obtenerPayloadActualizacion()
    {
        $tickets = $this->fetchTickets();

        $grupos = TicketMantenimiento::agruparPorColumnas($tickets);
        $ticketsStatus = collect($grupos)->map(
            fn ($grupo) => $this->formatearTickets($grupo)
        )->toArray();

        return [
            'ticketsStatus' => $ticketsStatus,
            'hash' => md5(json_encode($tickets->map(fn ($ticket) => [
                $ticket->MantenimientoID,
                $ticket->Estatus,
                $ticket->Prioridad,
                $ticket->ResponsableID,
                $ticket->Categoria,
                $ticket->updated_at,
            ]))),
        ];
    }

    public function render()
    {
        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.mantenimiento-tickets-kanban-updater', [
            'ticketsStatus' => $payload['ticketsStatus'],
        ]);
    }
}
