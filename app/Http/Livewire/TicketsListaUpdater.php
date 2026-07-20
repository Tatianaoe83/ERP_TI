<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Livewire\WithPagination;

class TicketsListaUpdater extends Component
{
    use WithPagination;

    private const VISTA = 'lista';

    protected string $pageNameCerrados = 'pageCerradosLista';

    // Lazy: no es la vista por defecto → no consulta hasta que se active su pestaña.
    public bool $cargar = false;

    protected function getListeners()
    {
        return [
            'ticket-estatus-actualizado'               => '$refresh',
            'soporte-vista-activa'                     => 'activarSiCorresponde',
            "echo:tickets-channel,TicketUpdatedEvent"  => '$refresh',
        ];
    }

    public function activarSiCorresponde($vista)
    {
        if ($vista === self::VISTA) {
            $this->cargar = true;
        }
    }

    public function actualizarDatos()
    {
        $this->emit('tickets-actualizados-lista', $this->obtenerPayloadActualizacion());
    }

    private function fetchActivos()
    {
        return Tickets::with(['empleado', 'responsableTI', 'tipoticket'])
            ->whereIn('Estatus', ['Pendiente', 'En progreso'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function fetchCerrados()
    {
        return Tickets::with(['empleado', 'responsableTI', 'tipoticket'])
            ->where('Estatus', 'Cerrado')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], $this->pageNameCerrados);
    }

    private function formatearTickets($tickets, array $tiemposProgreso = [], array $notificacionesMap = [])
    {
        return Tickets::formatearColeccionParaVista($tickets, $tiemposProgreso, $notificacionesMap);
    }

    private function estructuraVacia(): array
    {
        return [
            'ticketsStatus'    => ['nuevos' => [], 'proceso' => [], 'resueltos' => []],
            'cerrados'         => null,
            'ticketsExcedidos' => [],
            'tiemposProgreso'  => [],
        ];
    }

    private function construirDatos(): array
    {
        if (! $this->cargar) {
            return $this->estructuraVacia();
        }

        $activos  = $this->fetchActivos();
        $cerrados = $this->fetchCerrados();
        $cerradosItems = collect($cerrados->items());

        $tiempos = Tickets::procesarTiemposProgreso($activos);

        $idsVisibles = $activos->pluck('TicketID')->merge($cerradosItems->pluck('TicketID'));
        $notificacionesMap = Tickets::mapaNotificacionesPendientes($idsVisibles);

        $ticketsStatus = [
            'nuevos' => $this->formatearTickets(
                $activos->where('Estatus', 'Pendiente')->values(),
                $tiempos['tiemposProgreso'],
                $notificacionesMap
            ),
            'proceso' => $this->formatearTickets(
                $activos->where('Estatus', 'En progreso')->values(),
                $tiempos['tiemposProgreso'],
                $notificacionesMap
            ),
            'resueltos' => $this->formatearTickets(
                $cerradosItems->values(),
                $tiempos['tiemposProgreso'],
                $notificacionesMap
            ),
        ];

        return [
            'ticketsStatus'    => $ticketsStatus,
            'cerrados'         => $cerrados,
            'ticketsExcedidos' => $tiempos['ticketsExcedidos'],
            'tiemposProgreso'  => $tiempos['tiemposProgreso'],
        ];
    }

    private function obtenerPayloadActualizacion()
    {
        $datos = $this->construirDatos();

        return [
            'ticketsStatus'    => $datos['ticketsStatus'],
            'ticketsExcedidos' => $datos['ticketsExcedidos'],
            'tiemposProgreso'  => $datos['tiemposProgreso'],
            'totalCerrados'    => $datos['cerrados'] ? $datos['cerrados']->total() : 0,
            'hash'             => md5(json_encode($datos['ticketsStatus'])),
        ];
    }

    public function render()
    {
        $datos = $this->construirDatos();

        if ($this->cargar) {
            $this->emit('tickets-actualizados-lista', [
                'ticketsStatus'    => $datos['ticketsStatus'],
                'ticketsExcedidos' => $datos['ticketsExcedidos'],
                'tiemposProgreso'  => $datos['tiemposProgreso'],
                'totalCerrados'    => $datos['cerrados'] ? $datos['cerrados']->total() : 0,
                'hash'             => md5(json_encode($datos['ticketsStatus'])),
            ]);
        }

        return view('livewire.tickets-lista-updater', [
            'ticketsStatus'    => $datos['ticketsStatus'],
            'cerrados'         => $datos['cerrados'],
            'tiemposProgreso'  => $datos['tiemposProgreso'],
            'ticketsExcedidos' => $datos['ticketsExcedidos'],
        ]);
    }
}
