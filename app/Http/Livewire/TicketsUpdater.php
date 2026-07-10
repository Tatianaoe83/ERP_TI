<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;

class TicketsUpdater extends Component
{
    public $ticketsExcedidos = [];
    public $tiemposProgreso = [];
    
    public function mount()
    {
        $this->actualizarDatos();
    }
    
    public function actualizarDatos()
    {
        // Actualizar tickets excedidos
        $this->actualizarTicketsExcedidos();
        
        // Actualizar tiempos de progreso
        $this->actualizarTiemposProgreso();
        
        // Emitir evento para que Alpine.js actualice los indicadores
        $this->emit('tickets-actualizados', [
            'ticketsExcedidos' => $this->ticketsExcedidos,
            'tiemposProgreso' => $this->tiemposProgreso
        ]);
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

            $tiempoProgreso = $ticket->tiempo_progreso;
            if ($tiempoProgreso === null) {
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;

            if ($tiempoProgreso > $tiempoEstimadoHoras) {
                $tiempoExcedido = round($tiempoProgreso - $tiempoEstimadoHoras, 2);
                $porcentajeExcedido = round(($tiempoProgreso / $tiempoEstimadoHoras) * 100, 1);
                
                $this->ticketsExcedidos[] = [
                    'id' => $ticket->TicketID,
                    'descripcion' => \Illuminate\Support\Str::limit($ticket->Descripcion, 80),
                    'responsable' => $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin asignar',
                    'empleado' => $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin empleado',
                    'prioridad' => $ticket->Prioridad,
                    'tiempo_estimado' => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta' => round($tiempoProgreso, 2),
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
                $tiempoTranscurrido = $ticket->tiempo_progreso ?? 0;
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
        return view('livewire.tickets-updater');
    }
}
