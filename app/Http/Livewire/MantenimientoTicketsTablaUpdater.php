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

    // FILTROS
    public $search = '';
    public $filtroPrioridad = '';
    public $filtroEstado = '';
    public $filtroCategoria = '';

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

    // Cambiar un filtro deja la página actual sin sentido: se vuelve a la primera.
    public function updatingSearch() { $this->pagina = 1; }
    public function updatingFiltroPrioridad() { $this->pagina = 1; }
    public function updatingFiltroEstado() { $this->pagina = 1; }
    public function updatingFiltroCategoria() { $this->pagina = 1; }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'search',
            'filtroPrioridad',
            'filtroEstado',
            'filtroCategoria',
        ]);

        $this->pagina = 1;
    }

    public function actualizarDatos()
    {
        if (!$this->activo) {
            return;
        }

        $this->emit('mantenimiento-actualizados-tabla', $this->obtenerPayloadActualizacion());
    }

    /** Consulta con los filtros de la barra superior ya aplicados. */
    private function queryFiltrada()
    {
        $query = TicketMantenimiento::queryConRelaciones();

        $search = trim((string) $this->search);
        if ($search !== '') {
            $tokens = collect(preg_split('/\s+/', $search) ?: [])
                ->map(fn ($token) => trim((string) $token))
                ->filter(fn ($token) => $token !== '')
                ->values();

            $query->where(function ($q) use ($tokens, $search) {
                foreach ($tokens as $token) {
                    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $token) . '%';

                    $q->where(function ($subQuery) use ($like) {
                        $subQuery->where('Descripcion', 'like', $like)
                            ->orWhere('MantenimientoID', 'like', $like)
                            ->orWhere('Categoria', 'like', $like)
                            ->orWhere('Estatus', 'like', $like)
                            ->orWhere('Prioridad', 'like', $like)
                            ->orWhereHas('empleado', function ($empleado) use ($like) {
                                $empleado->where('NombreEmpleado', 'like', $like)
                                    ->orWhere('Correo', 'like', $like);
                            })
                            ->orWhereHas('responsable', function ($responsable) use ($like) {
                                $responsable->where('NombreEmpleado', 'like', $like);
                            });
                    });
                }

                if (ctype_digit($search)) {
                    $q->orWhere('MantenimientoID', (int) $search);
                }
            });
        }

        if ($this->filtroPrioridad) {
            if ($this->filtroPrioridad === 'sin') {
                $query->where(function ($q) {
                    $q->whereNull('Prioridad')->orWhere('Prioridad', '');
                });
            } else {
                $query->where('Prioridad', $this->filtroPrioridad);
            }
        }

        if ($this->filtroEstado) {
            $query->where('Estatus', $this->filtroEstado);
        }

        if ($this->filtroCategoria) {
            if ($this->filtroCategoria === 'sin') {
                $query->where(function ($q) {
                    $q->whereNull('Categoria')->orWhere('Categoria', '');
                });
            } else {
                $query->where('Categoria', $this->filtroCategoria);
            }
        }

        return $query;
    }

    private function formatearTickets($tickets)
    {
        return $tickets->map(
            fn ($ticket) => TicketMantenimiento::formatearTicketParaVista($ticket)
        )->toArray();
    }

    private function obtenerPayloadActualizacion()
    {
        $query = $this->queryFiltrada();

        $this->total = (clone $query)->count();

        // Un borrado o un filtro puede dejar la página actual fuera de rango; se retrocede antes de consultar.
        $this->pagina = max(1, min($this->pagina, $this->ultimaPagina()));

        $tickets = $query
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
