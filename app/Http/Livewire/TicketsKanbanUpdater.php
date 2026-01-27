<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;

class TicketsKanbanUpdater extends Component
{
    public $ticketsExcedidos = [];
    public $tiemposProgreso = [];
    
    public function mount()
    {
        $this->actualizarDatos();
    }
    
    public function actualizarDatos()
    {
        // Recargar todos los tickets
        $ticketsQuery = \App\Models\Tickets::with(['empleado', 'responsableTI', 'tipoticket', 'chat' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }]);

        $tickets = $ticketsQuery->orderBy('created_at', 'desc')->get();

        $ticketsStatus = [
            'nuevos' => $tickets->where('Estatus', 'Pendiente')->values(),
            'proceso' => $tickets->where('Estatus', 'En progreso')->values(),
            'resueltos' => $tickets->where('Estatus', 'Cerrado')->values(),
        ];
        
        // Actualizar tickets excedidos
        $this->actualizarTicketsExcedidos();
        
        // Actualizar tiempos de progreso
        $this->actualizarTiemposProgreso();
        
        // Generar un hash de los datos para detectar cambios
        $ticketsFormateados = [
            'nuevos' => $this->formatearTickets($ticketsStatus['nuevos']),
            'proceso' => $this->formatearTickets($ticketsStatus['proceso']),
            'resueltos' => $this->formatearTickets($ticketsStatus['resueltos']),
        ];
        
        // Generar hash incluyendo todos los datos relevantes para detectar cambios
        // NO incluir timestamp en el hash para que solo cambie cuando hay cambios reales
        $datosParaHash = [
            'tickets' => $ticketsFormateados,
            'tiempos' => $this->tiemposProgreso
        ];
        $hashDatos = md5(json_encode($datosParaHash));
        
        // Emitir evento para que Alpine.js actualice los datos completos
        $this->emit('tickets-actualizados-kanban', [
            'ticketsStatus' => $ticketsFormateados,
            'ticketsExcedidos' => $this->ticketsExcedidos,
            'tiemposProgreso' => $this->tiemposProgreso,
            'hash' => $hashDatos,
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    private function formatearTickets($tickets)
    {
        return $tickets->map(function($ticket) {
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
                'created_at' => $ticket->created_at->toIso8601String(),
                'fecha_inicio_progreso' => $ticket->FechaInicioProgreso ? $ticket->FechaInicioProgreso->toIso8601String() : null,
                'updated_at' => $ticket->updated_at->toIso8601String(),
            ];
        })->toArray();
    }
    
    private function actualizarTicketsExcedidos()
    {
        $tickets = Tickets::with(['tipoticket', 'responsableTI', 'empleado'])
            ->where('Estatus', 'En progreso')
            ->whereNotNull('FechaInicioProgreso')
            ->whereNotNull('TipoID')
            ->get();

        $this->ticketsExcedidos = [];

        foreach ($tickets as $ticket) {
            if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) {
                continue;
            }

            $tiempoRespuesta = $ticket->tiempo_respuesta;
            if ($tiempoRespuesta === null) {
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;

            if ($tiempoRespuesta > $tiempoEstimadoHoras) {
                $tiempoExcedido = round($tiempoRespuesta - $tiempoEstimadoHoras, 2);
                $porcentajeExcedido = round(($tiempoRespuesta / $tiempoEstimadoHoras) * 100, 1);
                
                $this->ticketsExcedidos[] = [
                    'id' => $ticket->TicketID,
                    'descripcion' => \Illuminate\Support\Str::limit($ticket->Descripcion, 80),
                    'responsable' => $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin asignar',
                    'empleado' => $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin empleado',
                    'prioridad' => $ticket->Prioridad,
                    'tiempo_estimado' => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta' => round($tiempoRespuesta, 2),
                    'tiempo_excedido' => $tiempoExcedido,
                    'porcentaje_excedido' => $porcentajeExcedido,
                    'categoria' => $ticket->tipoticket ? $ticket->tipoticket->NombreTipo : 'Sin categoría'
                ];
            }
        }

        usort($this->ticketsExcedidos, function($a, $b) {
            return $b['tiempo_excedido'] <=> $a['tiempo_excedido'];
        });
    }
    
    private function actualizarTiemposProgreso()
    {
        $ticketsEnProgreso = Tickets::with(['tipoticket', 'responsableTI'])
            ->where('Estatus', 'En progreso')
            ->whereNotNull('FechaInicioProgreso')
            ->get();

        $this->tiemposProgreso = [];
        
        foreach ($ticketsEnProgreso as $ticket) {
            $tiempoInfo = null;
            
            if ($ticket->tipoticket && $ticket->tipoticket->TiempoEstimadoMinutos) {
                $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;
                $tiempoTranscurrido = $ticket->tiempo_respuesta ?? 0;
                $porcentajeUsado = $tiempoEstimadoHoras > 0 ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100 : 0;
                
                $tiempoInfo = [
                    'transcurrido' => round($tiempoTranscurrido, 1),
                    'estimado' => round($tiempoEstimadoHoras, 1),
                    'porcentaje' => round($porcentajeUsado, 1),
                    'estado' => $porcentajeUsado >= 100 ? 'agotado' : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal')
                ];
            }
            
            $this->tiemposProgreso[$ticket->TicketID] = $tiempoInfo;
        }
    }
    
    public function render()
    {
        return view('livewire.tickets-kanban-updater');
    }
}
