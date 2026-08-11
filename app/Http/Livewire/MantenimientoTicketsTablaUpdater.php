<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\VistaLazy;
use App\Models\TicketMantenimiento;
use Livewire\Component;

class MantenimientoTicketsTablaUpdater extends Component
{
    use VistaLazy;

    public const POR_PAGINA = 25;

    public int $pagina = 1;
    public int $total = 0;

    protected $listeners = [
        'mantenimiento-estatus-actualizado' => 'actualizarDatos',
        'mantenimiento-vista-activada' => 'activarVista',
    ];

    protected function nombreVista(): string
    {
        return 'tabla';
    }

    public function irAPagina($pagina): void
    {
        $this->pagina = max(1, min((int) $pagina, $this->ultimaPagina()));
    }

    public function ultimaPagina(): int
    {
        return max(1, (int) ceil($this->total / self::POR_PAGINA));
    }

    public function actualizarDatos()
    {
        if (!$this->activo) {
            return;
        }

        $this->emit('mantenimiento-actualizados-tabla', $this->obtenerPayloadActualizacion());
    }

    private function formatearTickets($tickets)
    {
        return $tickets->map(
            fn ($ticket) => TicketMantenimiento::formatearTicketParaVista($ticket)
        )->toArray();
    }

    private function obtenerPayloadActualizacion()
    {
        $this->total = TicketMantenimiento::count();

        // Un borrado puede dejar la página actual fuera de rango; se retrocede antes de consultar.
        $this->pagina = max(1, min($this->pagina, $this->ultimaPagina()));

        $tickets = TicketMantenimiento::queryConRelaciones()
            ->orderBy('created_at', 'desc')
            ->forPage($this->pagina, self::POR_PAGINA)
            ->get();

        return [
            'tickets' => $this->formatearTickets($tickets),
            'hash' => TicketMantenimiento::hashDeTickets($tickets),
        ];
    }

    public function render()
    {
        if (!$this->activo) {
            return view('livewire.mantenimiento-tickets-tabla-updater', [
                'tickets' => null,
            ]);
        }

        $payload = $this->obtenerPayloadActualizacion();

        return view('livewire.mantenimiento-tickets-tabla-updater', [
            'tickets' => $payload['tickets'],
        ]);
    }
}
