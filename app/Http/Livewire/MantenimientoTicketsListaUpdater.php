<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\ColumnasFinalizadasPaginadas;
use App\Http\Livewire\Concerns\VistaLazy;
use App\Models\TicketMantenimiento;
use Livewire\Component;

class MantenimientoTicketsListaUpdater extends Component
{
    use VistaLazy;
    use ColumnasFinalizadasPaginadas;

    protected $listeners = [
        'mantenimiento-estatus-actualizado' => 'actualizarDatos',
        'mantenimiento-vista-activada' => 'activarVista',
    ];

    protected function nombreVista(): string
    {
        return 'lista';
    }

    public function actualizarDatos()
    {
        if (!$this->activo) {
            return;
        }

        $this->emit('mantenimiento-actualizados-lista', $this->obtenerPayloadActualizacion());
    }

    private function formatearTickets($tickets)
    {
        return $tickets->map(
            fn ($ticket) => TicketMantenimiento::formatearTicketParaVista($ticket)
        )->toArray();
    }

    private function obtenerPayloadActualizacion()
    {
        $grupos = $this->obtenerColumnas();

        return [
            'ticketsStatus' => collect($grupos)->map(
                fn ($grupo) => $this->formatearTickets($grupo)
            )->toArray(),
            'totales' => $this->totales,
            'hash' => TicketMantenimiento::hashDeTickets(collect($grupos)->flatten(1)),
        ];
    }

    public function render()
    {
        if (!$this->activo) {
            return view('livewire.mantenimiento-tickets-lista-updater', [
                'ticketsStatus' => null,
            ]);
        }

        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.mantenimiento-tickets-lista-updater', [
            'ticketsStatus' => $payload['ticketsStatus'],
        ]);
    }
}
