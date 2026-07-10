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
        'ticket-estatus-actualizado' => 'actualizarDatos'
    ];

    // 🔄 Se ejecuta por el wire:poll
    public function actualizarDatos()
    {
        $this->emit('tickets-actualizados-tabla', $this->obtenerPayloadActualizacion());
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
        $search = trim((string)$this->search);
        if ($search !== '') {
            $tokens = collect(preg_split('/\s+/', $search) ?: [])
                ->map(fn($token) => trim((string)$token))
                ->filter(fn($token) => $token !== '')
                ->values();

            $query->where(function ($q) use ($tokens, $search) {
                foreach ($tokens as $token) {
                    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $token) . '%';

                    $q->where(function ($subQuery) use ($like) {
                        $subQuery->where('Descripcion', 'like', $like)
                            ->orWhere('TicketID', 'like', $like)
                            ->orWhere('Estatus', 'like', $like)
                            ->orWhere('Prioridad', 'like', $like)
                            ->orWhereHas('empleado', function ($empleado) use ($like) {
                                $empleado->where('NombreEmpleado', 'like', $like)
                                    ->orWhere('Correo', 'like', $like);
                            })
                            ->orWhereHas('responsableTI', function ($responsable) use ($like) {
                                $responsable->where('NombreEmpleado', 'like', $like)
                                    ->orWhere('Correo', 'like', $like);
                            })
                            ->orWhereHas('tipoticket', function ($tipo) use ($like) {
                                $tipo->where('NombreTipo', 'like', $like);
                            });
                    });
                }

                if (ctype_digit($search)) {
                    $q->orWhere('TicketID', (int)$search);
                }
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

    private function obtenerPayloadActualizacion()
    {
        $tickets = Tickets::with([
            'empleado',
            'responsableTI',
            'tipoticket',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

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

        $tiempos = $this->procesarTiemposParaPayload($tickets);

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

    private function procesarTiemposParaPayload($tickets)
    {
        return Tickets::procesarTiemposProgreso($tickets);
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
        $result = Tickets::procesarTiemposProgreso($tickets);
        $this->tiemposProgreso = $result['tiemposProgreso'];
        $this->ticketsExcedidos = $result['ticketsExcedidos'];
    }
}
