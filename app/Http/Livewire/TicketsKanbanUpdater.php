<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Illuminate\Support\Str;

class TicketsKanbanUpdater extends Component
{
    public $ticketsStatus = [
        'nuevos' => [],
        'proceso' => [],
        'resueltos' => [],
    ];

    public $ticketsExcedidos = [];
    public $tiemposProgreso = [];

    protected $listeners = ['ticket-estatus-actualizado' => 'actualizarDatos'];

    public function mount()
    {
        $this->actualizarDatos();
    }

    public function actualizarDatos()
    {
        // QUERY
        $tickets = Tickets::with([
            'empleado',
            'responsableTI',
            'tipoticket',
            'chat' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // Clasificación por estatus
        $this->ticketsStatus = [
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

        // Procesar tiempos y excedidos usando la misma colección
        $this->procesarTiempos($tickets);

        // Hash optimizado
        $hashDatos = hash('xxh3', json_encode([
            'tickets' => $this->ticketsStatus,
            'tiempos' => $this->tiemposProgreso
        ]));

        // Emitir evento para Alpine
        $this->emit('tickets-actualizados-kanban', [
            'ticketsStatus' => $this->ticketsStatus,
            'ticketsExcedidos' => $this->ticketsExcedidos,
            'tiemposProgreso' => $this->tiemposProgreso,
            'hash' => $hashDatos,
            'timestamp' => now()->toIso8601String()
        ]);
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

    private function procesarTiempos($tickets)
    {
        $this->ticketsExcedidos = [];
        $this->tiemposProgreso = [];

        $ticketsEnProgreso = $tickets
            ->where('Estatus', 'En progreso')
            ->whereNotNull('FechaInicioProgreso');

        foreach ($ticketsEnProgreso as $ticket) {

            if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) {
                $this->tiemposProgreso[$ticket->TicketID] = null;
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;

            // Cálculo directo sin depender de accessor pesado
            $tiempoTranscurrido = $ticket->FechaInicioProgreso
                ? $ticket->FechaInicioProgreso->diffInMinutes(now()) / 60
                : 0;

            $porcentajeUsado = $tiempoEstimadoHoras > 0
                ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100
                : 0;

            // Guardar progreso
            $this->tiemposProgreso[$ticket->TicketID] = [
                'transcurrido' => round($tiempoTranscurrido, 1),
                'estimado' => round($tiempoEstimadoHoras, 1),
                'porcentaje' => round($porcentajeUsado, 1),
                'estado' => $porcentajeUsado >= 100
                    ? 'agotado'
                    : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal')
            ];

            // Detectar excedidos
            if ($tiempoTranscurrido > $tiempoEstimadoHoras) {

                $tiempoExcedido = round($tiempoTranscurrido - $tiempoEstimadoHoras, 2);
                $porcentajeExcedido = round(($tiempoTranscurrido / $tiempoEstimadoHoras) * 100, 1);

                $this->ticketsExcedidos[] = [
                    'id' => $ticket->TicketID,
                    'descripcion' => Str::limit($ticket->Descripcion, 80),
                    'responsable' => $ticket->responsableTI
                        ? $ticket->responsableTI->NombreEmpleado
                        : 'Sin asignar',
                    'empleado' => $ticket->empleado
                        ? $ticket->empleado->NombreEmpleado
                        : 'Sin empleado',
                    'prioridad' => $ticket->Prioridad,
                    'tiempo_estimado' => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta' => round($tiempoTranscurrido, 2),
                    'tiempo_excedido' => $tiempoExcedido,
                    'porcentaje_excedido' => $porcentajeExcedido,
                    'categoria' => $ticket->tipoticket
                        ? $ticket->tipoticket->NombreTipo
                        : 'Sin categoría'
                ];
            }
        }

        // Ordenar excedidos por mayor tiempo excedido
        usort($this->ticketsExcedidos, function ($a, $b) {
            return $b['tiempo_excedido'] <=> $a['tiempo_excedido'];
        });
    }

    public function render()
    {
        return view('livewire.tickets-kanban-updater', [
            'ticketsStatus' => $this->ticketsStatus,
            'ticketsExcedidos' => $this->ticketsExcedidos,
            'tiemposProgreso' => $this->tiemposProgreso,
        ]);
    }
}
