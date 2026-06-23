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
        $ticketsExcedidos = [];
        $tiemposProgreso = [];

        $ticketsEnProgreso = $tickets
            ->where('Estatus', 'En progreso')
            ->whereNotNull('FechaInicioProgreso');

        foreach ($ticketsEnProgreso as $ticket) {
            if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) {
                $tiemposProgreso[$ticket->TicketID] = null;
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;
            $tiempoTranscurrido = $ticket->FechaInicioProgreso
                ? $ticket->FechaInicioProgreso->diffInMinutes(now()) / 60
                : 0;
            $porcentajeUsado = $tiempoEstimadoHoras > 0
                ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100
                : 0;

            $tiemposProgreso[$ticket->TicketID] = [
                'transcurrido' => round($tiempoTranscurrido, 1),
                'estimado' => round($tiempoEstimadoHoras, 1),
                'porcentaje' => round($porcentajeUsado, 1),
                'estado' => $porcentajeUsado >= 100 ? 'agotado' : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal'),
            ];

            if ($tiempoTranscurrido > $tiempoEstimadoHoras) {
                $ticketsExcedidos[] = [
                    'id' => $ticket->TicketID,
                    'descripcion' => Str::limit($ticket->Descripcion, 80),
                    'responsable' => $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin asignar',
                    'empleado' => $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin empleado',
                    'prioridad' => $ticket->Prioridad,
                    'tiempo_estimado' => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta' => round($tiempoTranscurrido, 2),
                    'tiempo_excedido' => round($tiempoTranscurrido - $tiempoEstimadoHoras, 2),
                    'porcentaje_excedido' => round(($tiempoTranscurrido / $tiempoEstimadoHoras) * 100, 1),
                    'categoria' => $ticket->tipoticket ? $ticket->tipoticket->NombreTipo : 'Sin categoría',
                ];
            }
        }

        usort($ticketsExcedidos, function ($a, $b) {
            return $b['tiempo_excedido'] <=> $a['tiempo_excedido'];
        });

        return [
            'tiemposProgreso' => $tiemposProgreso,
            'ticketsExcedidos' => $ticketsExcedidos,
        ];
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
