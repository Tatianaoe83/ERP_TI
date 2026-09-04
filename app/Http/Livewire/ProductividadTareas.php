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
        session()->flash('prod_tareas_mensaje', 'Métrica mensual guardada. Se creará sola el día indicado de cada mes.');
    }

    public function generarMetricasAhora(TicketTareaService $service): void
    {
        $this->authorizeGestion();
        $creadas = $service->generarTareasMetricas();
        session()->flash('prod_tareas_mensaje', "Se generaron {$creadas} tarea(s) de métricas pendientes del mes.");
    }

    public function render()
    {
        $empleados = Empleados::tiActivos()
            ->orderBy('NombreEmpleado')
            ->get(['EmpleadoID', 'NombreEmpleado']);

        $tareas = TicketTarea::query()
            ->with('asignado')
            ->get(['id', 'asignado_id', 'estatus', 'prioridad', 'fecha_compromiso', 'completada_at', 'tipo']);

        $rendimiento = $empleados->map(function ($empleado) use ($tareas) {
            $delEmpleado = $tareas->where('asignado_id', $empleado->EmpleadoID);
            $pendientes = $delEmpleado->where('estatus', TicketTarea::ESTATUS_PENDIENTE);
            $completadasMes = $delEmpleado
                ->where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                ->filter(function ($t) {
                    return $t->completada_at
                        && (int) $t->completada_at->month === (int) now()->month
                        && (int) $t->completada_at->year === (int) now()->year;
                });

            return [
                'empleado' => $empleado,
                'pendientes' => $pendientes->count(),
                'hoy' => $pendientes->filter(function ($t) {
                    if (! $t->fecha_compromiso) {
                        return true;
                    }
                    return $t->fecha_compromiso->isToday();
                })->count(),
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
                'hoy' => $pendientesSinAsignar->filter(function ($t) {
                    if (! $t->fecha_compromiso) {
                        return true;
                    }
                    return $t->fecha_compromiso->isToday();
                })->count(),
                'criticas' => $pendientesSinAsignar->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count(),
                'sin_fecha' => $pendientesSinAsignar->filter(fn ($t) => ! $t->fecha_compromiso)->count(),
                'completadas_mes' => $sinAsignar
                    ->where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                    ->filter(function ($t) {
                        return $t->completada_at
                            && (int) $t->completada_at->month === (int) now()->month
                            && (int) $t->completada_at->year === (int) now()->year;
                    })
                    ->count(),
                'total' => $sinAsignar->count(),
            ]);
        }

        $kpis = [
            'pendientes' => TicketTarea::pendientes()->count(),
            'hoy' => TicketTarea::pendientes()
                ->where(function ($q) {
                    $q->whereDate('fecha_compromiso', now()->toDateString())
                        ->orWhereNull('fecha_compromiso');
                })
                ->count(),
            'criticas' => TicketTarea::pendientes()->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count(),
            'completadas_mes' => TicketTarea::where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                ->whereMonth('completada_at', now()->month)
                ->whereYear('completada_at', now()->year)
                ->count(),
            'sin_fecha' => TicketTarea::pendientes()->whereNull('fecha_compromiso')->count(),
            'metricas_activas' => TicketTareaMetrica::where('activo', true)->count(),
        ];

        $metricas = TicketTareaMetrica::query()->with('creador')->orderBy('nombre')->get();

        return view('livewire.productividad-tareas', compact('rendimiento', 'kpis', 'metricas'));
    }

    private function authorizeGestion(): void
    {
        abort_unless(auth()->user()?->can('tickets.gestionar-tareas'), 403);
    }
}
