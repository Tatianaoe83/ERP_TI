<?php

namespace App\Http\Livewire;

use App\Models\TicketMantenimiento;
use Livewire\Component;

class MantenimientoTicketsListaUpdater extends Component
{
    protected $listeners = ['mantenimiento-estatus-actualizado' => 'actualizarDatos'];

    public function actualizarDatos()
    {
        $this->emit('mantenimiento-actualizados-lista', $this->obtenerPayloadActualizacion());
    }

    private function fetchTickets()
    {
        return TicketMantenimiento::orderBy('created_at', 'desc')->get();
    }

    private function formatearTickets($tickets)
    {
        return $tickets->map(function ($ticket) {
            return [
                'id'          => $ticket->MantenimientoID,
                'asunto'      => $ticket->Asunto,
                'descripcion' => $ticket->Descripcion,
                'prioridad'   => $ticket->Prioridad,
                'estatus'     => $ticket->Estatus,
                'categoria'   => $ticket->Categoria,
                'responsable' => $ticket->Responsable,
                'solicitante' => $ticket->NombreSolicitante,
                'correo'      => $ticket->Correo,
                'area'        => $ticket->AreaDepartamento,
                'imagen'      => $ticket->imagen,
                'created_at'  => optional($ticket->created_at)->toIso8601String(),
            ];
        })->toArray();
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
                $ticket->Responsable,
                $ticket->Categoria,
                $ticket->updated_at,
            ]))),
        ];
    }

    public function render()
    {
        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.mantenimiento-tickets-lista-updater', [
            'ticketsStatus' => $payload['ticketsStatus'],
        ]);
    }
}
