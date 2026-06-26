<?php

namespace App\Http\Livewire;

use App\Models\TicketMantenimiento;
use Livewire\Component;

class MantenimientoTicketsTablaUpdater extends Component
{
    protected $listeners = ['mantenimiento-estatus-actualizado' => 'actualizarDatos'];

    public function actualizarDatos()
    {
        $this->emit('mantenimiento-actualizados-tabla', $this->obtenerPayloadActualizacion());
    }

    private function fetchTickets()
    {
        return TicketMantenimiento::orderBy('created_at', 'desc')->get();
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

        return [
            'tickets' => $this->formatearTickets($tickets),
            'hash' => md5(json_encode($tickets->map(fn ($ticket) => [
                $ticket->MantenimientoID,
                $ticket->Estatus,
                $ticket->Prioridad,
                $ticket->Responsable,
                $ticket->Categoria,
                $ticket->updated_at,
            ]))),
        ];
    }

    public function render()
    {
        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.mantenimiento-tickets-tabla-updater', [
            'tickets' => $payload['tickets'],
        ]);
    }
}
