<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class TicketsTablaUpdater extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap'; // cambia a 'tailwind' si usas tailwind pagination

    // 🔎 FILTROS
    public $search = '';
    public $filtroPrioridad = '';
    public $filtroEstado = '';
    public $filtroResponsable = '';

    // ⏱ TIEMPOS
    public $tiemposProgreso = [];
    public $ticketsExcedidos = [];

    protected $listeners = [
        'ticket-estatus-actualizado' => '$refresh'
    ];

    // 🔄 Se ejecuta por el wire:poll
    public function actualizarDatos()
    {
    }

    // 🔁 Reset paginación cuando cambian filtros
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFiltroPrioridad() { $this->resetPage(); }
    public function updatingFiltroEstado() { $this->resetPage(); }
    public function updatingFiltroResponsable() { $this->resetPage(); }

    public function render()
    {
        $query = Tickets::with([
            'empleado',
            'responsableTI',
            'tipoticket'
        ]);

        // 🔎 BUSCADOR
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('Descripcion', 'like', '%' . $this->search . '%')
                  ->orWhere('TicketID', 'like', '%' . $this->search . '%')
                  ->orWhereHas('empleado', function ($sub) {
                      $sub->where('NombreEmpleado', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // 🎯 PRIORIDAD
        if ($this->filtroPrioridad) {
            $query->where('Prioridad', $this->filtroPrioridad);
        }

        // 📌 ESTADO
        if ($this->filtroEstado) {
            $query->where('Estatus', $this->filtroEstado);
        }

        // 👨‍💻 RESPONSABLE
        if ($this->filtroResponsable) {
            $query->whereHas('responsableTI', function ($q) {
                $q->where('NombreEmpleado', $this->filtroResponsable);
            });
        }

        $tickets = $query
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $this->procesarTiempos($tickets->getCollection());

        return view('livewire.tickets-tabla-updater', [
            'ticketsTabla' => $tickets,
            'tiemposProgreso' => $this->tiemposProgreso
        ]);
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'search',
            'filtroPrioridad',
            'filtroEstado',
            'filtroResponsable',
        ]);

        $this->resetPage();
    }


    private function procesarTiempos($tickets)
    {
        $this->tiemposProgreso = [];
        $this->ticketsExcedidos = [];

        foreach ($tickets as $ticket) {

            if (
                !$ticket->tipoticket ||
                !$ticket->tipoticket->TiempoEstimadoMinutos ||
                !$ticket->FechaInicioProgreso
            ) {
                $this->tiemposProgreso[$ticket->TicketID] = null;
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;

            $tiempoTranscurrido = $ticket->FechaInicioProgreso
                ->diffInMinutes(now()) / 60;

            $porcentajeUsado = $tiempoEstimadoHoras > 0
                ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100
                : 0;

            $this->tiemposProgreso[$ticket->TicketID] = [
                'transcurrido' => round($tiempoTranscurrido, 1),
                'estimado' => round($tiempoEstimadoHoras, 1),
                'porcentaje' => round($porcentajeUsado, 1),
                'estado' => $porcentajeUsado >= 100
                    ? 'agotado'
                    : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal')
            ];

            if ($tiempoTranscurrido > $tiempoEstimadoHoras) {
                $this->ticketsExcedidos[] = [
                    'id' => $ticket->TicketID,
                    'descripcion' => Str::limit($ticket->Descripcion, 80),
                    'tiempo_excedido' => round($tiempoTranscurrido - $tiempoEstimadoHoras, 2),
                ];
            }
        }

        usort($this->ticketsExcedidos, function ($a, $b) {
            return $b['tiempo_excedido'] <=> $a['tiempo_excedido'];
        });
    }
}
