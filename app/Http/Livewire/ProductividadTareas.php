<?php

namespace App\Http\Livewire;

use App\Models\Empleados;
use App\Models\TicketTarea;
use App\Models\TicketTareaMetrica;
use App\Services\TicketTareaService;
use Livewire\Component;

class ProductividadTareas extends Component
{
    public bool $modalMetricaAbierto = false;
    public ?int $metricaEditId = null;
    public string $metrica_nombre = '';
    public string $metrica_descripcion = '';
    public int $metrica_dia_compromiso = 1;
    public bool $metrica_activo = true;

    /** Quién creó la métrica abierta en el modal. Solo se muestra con tickets.ver-creador-tarea. */
    public ?string $metricaCreador = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('tickets.ver-productividad'), 403);
    }

    public function abrirModalMetrica(?int $id = null): void
    {
        $this->authorizeGestion();
        $this->metricaEditId = $id;
        if ($id) {
            $m = TicketTareaMetrica::with('creador')->findOrFail($id);
            $this->metrica_nombre = $m->nombre;
            $this->metrica_descripcion = (string) ($m->descripcion ?? '');
            $this->metrica_dia_compromiso = (int) $m->dia_compromiso;
            $this->metrica_activo = (bool) $m->activo;
            $this->metricaCreador = $m->creador
                ? ($m->creador->name ?: $m->creador->username)
                : 'Sin registro';
        } else {
            $this->metrica_nombre = '';
            $this->metrica_descripcion = '';
            $this->metrica_dia_compromiso = min(28, (int) now()->day);
            $this->metrica_activo = true;
            $this->metricaCreador = null;
        }
        $this->modalMetricaAbierto = true;
    }

    public function guardarMetrica(): void
    {
        $this->authorizeGestion();
        $this->validate([
            'metrica_nombre' => 'required|string|max:150',
            'metrica_descripcion' => 'nullable|string|max:2000',
            'metrica_dia_compromiso' => 'required|integer|min:1|max:28',
        ], [], [
            'metrica_nombre' => 'nombre',
            'metrica_dia_compromiso' => 'día de creación',
        ]);

        $payload = [
            'nombre' => $this->metrica_nombre,
            'descripcion' => $this->metrica_descripcion,
            'dia_compromiso' => $this->metrica_dia_compromiso,
            'activo' => $this->metrica_activo,
        ];

        if ($this->metricaEditId) {
            TicketTareaMetrica::where('id', $this->metricaEditId)->update($payload);
        } else {
            TicketTareaMetrica::create($payload + ['creado_por_user_id' => auth()->id()]);
        }

        $this->modalMetricaAbierto = false;
        $this->alerta('Métrica mensual guardada. Se creará sola el día indicado de cada mes.');
    }

    public function generarMetricasAhora(TicketTareaService $service): void
    {
        $this->authorizeGestion();
        $creadas = $service->generarTareasMetricas();
        $this->alerta("Se generaron {$creadas} tarea(s) de métricas pendientes del mes.");
    }

    public function render()
    {
        $empleados = Empleados::tiActivos()
            ->orderBy('NombreEmpleado')
            ->get(['EmpleadoID', 'NombreEmpleado']);

        $tareas = TicketTarea::query()
            ->get(['id', 'asignado_id', 'asignados_ids', 'estatus', 'prioridad', 'fecha_compromiso', 'completada_at', 'tipo']);

        $completadaEsteMes = fn ($t) => $t->estatus === TicketTarea::ESTATUS_COMPLETADA
            && $t->completada_at
            && $t->completada_at->isSameMonth(now());

        $rendimiento = $empleados->map(function ($empleado) use ($tareas, $completadaEsteMes) {
            $delEmpleado = $tareas->filter(
                fn ($t) => in_array((int) $empleado->EmpleadoID, $t->idsAsignados(), true)
            );
            $pendientes = $delEmpleado->where('estatus', TicketTarea::ESTATUS_PENDIENTE);
            $completadasMes = $delEmpleado->filter($completadaEsteMes);

            return [
                'empleado' => $empleado,
                'pendientes' => $pendientes->count(),
                'hoy' => $pendientes->filter(fn ($t) => $t->fecha_compromiso?->isToday())->count(),
                'criticas' => $pendientes->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count(),
                'sin_fecha' => $pendientes->filter(fn ($t) => ! $t->fecha_compromiso)->count(),
                'completadas_mes' => $completadasMes->count(),
                'total' => $delEmpleado->count(),
            ];
        })->filter(fn ($row) => $row['total'] > 0 || $row['pendientes'] > 0)->values();

        $sinAsignar = $tareas->whereNull('asignado_id');
        $pendientesSinAsignar = $sinAsignar->where('estatus', TicketTarea::ESTATUS_PENDIENTE);
        if ($pendientesSinAsignar->isNotEmpty() || $sinAsignar->isNotEmpty()) {
            $rendimiento->push([
                'empleado' => null,
                'pendientes' => $pendientesSinAsignar->count(),
                'hoy' => $pendientesSinAsignar->filter(fn ($t) => $t->fecha_compromiso?->isToday())->count(),
                'criticas' => $pendientesSinAsignar->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count(),
                'sin_fecha' => $pendientesSinAsignar->filter(fn ($t) => ! $t->fecha_compromiso)->count(),
                'completadas_mes' => $sinAsignar->filter($completadaEsteMes)->count(),
                'total' => $sinAsignar->count(),
            ]);
        }

        $metricas = TicketTareaMetrica::query()->with('creador')->orderBy('nombre')->get();

        // KPIs desde las tareas ya cargadas: mismas reglas que los scopes pendientes()/deHoy().
        $pendientes = $tareas->where('estatus', TicketTarea::ESTATUS_PENDIENTE);
        $kpis = [
            'pendientes' => $pendientes->count(),
            'hoy' => $pendientes->filter(fn ($t) => $t->fecha_compromiso?->isToday())->count(),
            'criticas' => $pendientes->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count(),
            'completadas_mes' => $tareas->filter($completadaEsteMes)->count(),
            'sin_fecha' => $pendientes->filter(fn ($t) => ! $t->fecha_compromiso)->count(),
            'metricas_activas' => $metricas->where('activo', true)->count(),
        ];

        return view('livewire.productividad-tareas', compact('rendimiento', 'kpis', 'metricas'));
    }

    private function authorizeGestion(): void
    {
        abort_unless(auth()->user()?->can('tickets.gestionar-tareas'), 403);
    }

    /** Alerta SweetAlert2; el listener vive en partials/tareas-alerta. */
    private function alerta(string $mensaje, string $icono = 'success'): void
    {
        $this->dispatchBrowserEvent('tareas-alerta', ['icono' => $icono, 'mensaje' => $mensaje]);
    }
}
