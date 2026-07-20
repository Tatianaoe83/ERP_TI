<?php

namespace App\Http\Livewire;

use App\Models\Tickets;
use Livewire\Component;
use Livewire\WithPagination;

class TicketsKanbanUpdater extends Component
{
    use WithPagination;

    // Nombre de esta vista para el lazy-load por pestaña.
    private const VISTA = 'kanban';

    protected string $pageNameCerrados = 'pageCerradosKanban';

    // Lazy: kanban es la vista por defecto, arranca cargada. Lista/Tabla no.
    public bool $cargar = true;

    protected $listeners = [
        'ticket-estatus-actualizado' => 'actualizarDatos',
        'soporte-vista-activa'       => 'activarSiCorresponde',
    ];

    // El selector de vista (Alpine) emite la vista activa; solo la que coincide se carga.
    public function activarSiCorresponde($vista)
    {
        if ($vista === self::VISTA) {
            $this->cargar = true;
        }
    }

    public function actualizarDatos()
    {
        $this->emit('tickets-actualizados-kanban', $this->obtenerPayloadActualizacion());
    }

    // Tickets activos (Nuevos + En progreso): pocos, se cargan completos.
    private function fetchActivos()
    {
        return Tickets::with(['empleado', 'responsableTI', 'tipoticket'])
            ->whereIn('Estatus', ['Pendiente', 'En progreso'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Cerrados: paginados. Solo se consulta y formatea la página visible.
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

    // Construye lo que consumen render() y el payload de sync, en una sola pasada.
    private function construirDatos(): array
    {
        if (! $this->cargar) {
            return $this->estructuraVacia();
        }

        $activos  = $this->fetchActivos();
        $cerrados = $this->fetchCerrados();
        $cerradosItems = collect($cerrados->items());

        // Tiempos y excedidos solo dependen de los "En progreso" → basta con los activos.
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

        return view('livewire.tickets-kanban-updater', [
            'ticketsStatus'    => $datos['ticketsStatus'],
            'cerrados'         => $datos['cerrados'],
            'ticketsExcedidos' => $datos['ticketsExcedidos'],
            'tiemposProgreso'  => $datos['tiemposProgreso'],
        ]);
    }
}
