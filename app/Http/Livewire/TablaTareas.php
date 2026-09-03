<?php

namespace App\Http\Livewire;

use App\Models\Empleados;
use App\Models\TicketTarea;
use App\Services\TicketTareaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class TablaTareas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $vista = 'tareas';
    public string $filtroEstatus = 'hoy';
    public string $filtroTipo = '';
    public string $search = '';
    public int $perPage = 12;
    public int $calMes = 1;
    public int $calAnio = 2026;
    public string $modoLista = 'tarjetas';
    public string $fechaSeleccionada = '';

    public bool $modalTareaAbierto = false;
    public bool $modalReagendarAbierto = false;
    public bool $modalHistorialAbierto = false;

    public ?int $tareaEditId = null;
    public ?int $tareaReagendarId = null;
    public ?int $tareaHistorialId = null;

    public string $titulo = '';
    public string $razon = '';
    public $asignado_id = '';
    public string $fecha_compromiso = '';

    public string $reagendar_fecha = '';
    public string $reagendar_motivo = '';

    // modoLista NO va en el queryString: al entrar a la vista siempre debe arrancar
    // en 'tarjetas'. Si se guarda en la URL, la última elección pisa el valor por defecto.
    protected $queryString = [
        'vista' => ['except' => 'tareas'],
        'filtroEstatus' => ['except' => 'hoy'],
        'filtroTipo' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->authorizeTab();
        $this->calMes = max(1, min(12, (int) (request('calMes') ?: now()->month)));
        $this->calAnio = max(2000, (int) (request('calAnio') ?: now()->year));
        $this->fechaSeleccionada = now()->format('Y-m-d');
        app(TicketTareaService::class)->actualizarPrioridades();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEstatus(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo(): void
    {
        $this->resetPage();
    }

    public function filtrarKpi(string $estatus): void
    {
        $this->filtroEstatus = in_array($estatus, ['hoy', 'criticas', 'completadas'], true) ? $estatus : 'hoy';
        $this->resetPage();

        if ($this->filtroEstatus === 'hoy') {
            $this->irHoy();
            $this->modoLista = 'calendario';
        } else {
            $this->modoLista = 'tarjetas';
        }
    }

    public function mesAnterior(): void
    {
        $fecha = Carbon::create($this->calAnio, $this->calMes, 1)->subMonth();
        $this->calMes = (int) $fecha->month;
        $this->calAnio = (int) $fecha->year;
    }

    public function mesSiguiente(): void
    {
        $fecha = Carbon::create($this->calAnio, $this->calMes, 1)->addMonth();
        $this->calMes = (int) $fecha->month;
        $this->calAnio = (int) $fecha->year;
    }

    public function irHoy(): void
    {
        $this->calMes = (int) now()->month;
        $this->calAnio = (int) now()->year;
        $this->fechaSeleccionada = now()->format('Y-m-d');
    }

    public function seleccionarDia(string $fecha): void
    {
        $carbon = Carbon::parse($fecha);
        $this->fechaSeleccionada = $carbon->format('Y-m-d');
        $this->calMes = (int) $carbon->month;
        $this->calAnio = (int) $carbon->year;
    }

    public function abrirModalNuevaTarea(): void
    {
        $this->authorizeGestion();
        $this->resetFormTarea();
        $this->tareaEditId = null;
        $this->fecha_compromiso = '';
        $this->modalTareaAbierto = true;
    }

    public function abrirModalEditarTarea(int $id): void
    {
        $this->authorizeGestion();
        $tarea = TicketTarea::findOrFail($id);
        $this->tareaEditId = $tarea->id;
        $this->titulo = $tarea->titulo;
        $this->razon = (string) ($tarea->razon ?? '');
        $this->asignado_id = $tarea->asignado_id ? (string) $tarea->asignado_id : '';
        $this->fecha_compromiso = optional($tarea->fecha_compromiso)->format('Y-m-d') ?? '';
        $this->modalTareaAbierto = true;
    }

    public function guardarTarea(TicketTareaService $service): void
    {
        $this->authorizeGestion();

        $idsPermitidos = Empleados::tiActivos()->pluck('EmpleadoID')->map(fn ($id) => (int) $id)->all();
        if ($this->tareaEditId) {
            $actual = TicketTarea::find($this->tareaEditId);
            if ($actual?->asignado_id) {
                $idsPermitidos[] = (int) $actual->asignado_id;
            }
        }

        $this->validate([
            'titulo' => 'required|string|max:200',
            'razon' => 'nullable|string|max:2000',
            'asignado_id' => ['required', 'integer', Rule::in($idsPermitidos)],
            'fecha_compromiso' => 'nullable|date',
        ], [
            'asignado_id.in' => 'Solo se puede asignar a personal de TI activo.',
        ], [
            'titulo' => 'título',
            'razon' => 'razón',
            'asignado_id' => 'asignado',
            'fecha_compromiso' => 'fecha compromiso',
        ]);

        $fecha = trim((string) $this->fecha_compromiso) !== '' ? $this->fecha_compromiso : null;

        if ($this->tareaEditId) {
            $tarea = TicketTarea::findOrFail($this->tareaEditId);
            if ($tarea->tipo === TicketTarea::TIPO_METRICA) {
                throw ValidationException::withMessages([
                    'titulo' => 'Las tareas de métrica mensual no se editan aquí; reagéndela o complétela.',
                ]);
            }

            $anteriorAsignado = $tarea->asignado_id;
            $anteriorFecha = optional($tarea->fecha_compromiso)->format('Y-m-d');

            $tarea->update([
                'titulo' => $this->titulo,
                'razon' => $this->razon,
                'asignado_id' => (int) $this->asignado_id,
                'fecha_compromiso' => $fecha,
            ]);

            if ($anteriorFecha && $fecha && $anteriorFecha !== $fecha) {
                $service->reagendar($tarea, $fecha, 'Actualización desde edición de tarea.');
            } elseif ($anteriorFecha !== $fecha) {
                $service->registrarHistorial($tarea, 'reagendada', $fecha ? null : 'Se quitó la fecha de compromiso.', [
                    'fecha_compromiso_anterior' => $anteriorFecha,
                    'fecha_compromiso_nueva' => $fecha,
                ]);
            } elseif ((int) $anteriorAsignado !== (int) $this->asignado_id) {
                $service->registrarHistorial($tarea, 'asignada', null, [
                    'asignado_anterior_id' => $anteriorAsignado,
                    'asignado_nuevo_id' => (int) $this->asignado_id,
                    'notas' => 'Cambio de responsable.',
                ]);
            }
        } else {
            $service->crearEvento([
                'titulo' => $this->titulo,
                'razon' => $this->razon,
                'asignado_id' => (int) $this->asignado_id,
                'fecha_compromiso' => $fecha,
            ]);
        }

        $this->modalTareaAbierto = false;
        $this->resetFormTarea();
        session()->flash('tareas_mensaje', 'Tarea guardada correctamente.');
    }

    public function abrirReagendar(int $id): void
    {
        $this->authorizeGestion();
        $tarea = TicketTarea::findOrFail($id);
        $this->tareaReagendarId = $tarea->id;
        $this->reagendar_fecha = optional($tarea->fecha_compromiso)->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->reagendar_motivo = '';
        $this->modalReagendarAbierto = true;
    }

    public function guardarReagendar(TicketTareaService $service): void
    {
        $this->authorizeGestion();
        $this->validate([
            'reagendar_fecha' => 'required|date',
            'reagendar_motivo' => 'required|string|min:5|max:1000',
        ], [], [
            'reagendar_fecha' => 'nueva fecha',
            'reagendar_motivo' => 'motivo',
        ]);

        $tarea = TicketTarea::findOrFail($this->tareaReagendarId);
        $service->reagendar($tarea, $this->reagendar_fecha, $this->reagendar_motivo);

        $this->modalReagendarAbierto = false;
        session()->flash('tareas_mensaje', 'Tarea reagendada.');
    }

    public function completarTarea(int $id, TicketTareaService $service): void
    {
        $this->authorizeGestion();
        $tarea = TicketTarea::findOrFail($id);
        $service->completar($tarea);
        session()->flash('tareas_mensaje', 'Tarea completada.');
    }

    public function abrirHistorial(int $id): void
    {
        $this->tareaHistorialId = $id;
        $this->modalHistorialAbierto = true;
    }

    public function render()
    {
        $this->authorizeTab();

        $responsables = Empleados::tiActivos()
            ->orderBy('NombreEmpleado')
            ->get(['EmpleadoID', 'NombreEmpleado']);

        $hoy = now()->format('Y-m-d');

        $kpis = [
            'hoy' => TicketTarea::pendientes()
                ->where(function ($q) use ($hoy) {
                    $q->whereDate('fecha_compromiso', $hoy)
                        ->orWhereNull('fecha_compromiso');
                })
                ->count(),
            'criticas' => TicketTarea::pendientes()->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)->count(),
            'completadas_mes' => TicketTarea::where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                ->whereMonth('completada_at', now()->month)
                ->whereYear('completada_at', now()->year)
                ->count(),
        ];

        $inicioMes = Carbon::create($this->calAnio, $this->calMes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth();

        $tareasMes = TicketTarea::query()
            ->with(['asignado', 'metrica'])
            ->whereNotNull('fecha_compromiso')
            ->whereBetween('fecha_compromiso', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->orderBy('fecha_compromiso')
            ->get();

        $tareasPorDia = $tareasMes->groupBy(fn ($t) => $t->fecha_compromiso->format('Y-m-d'));

        $calendario = $this->construirCalendario($inicioMes, $tareasPorDia, $this->fechaSeleccionada);

        $tareas = TicketTarea::query()
            ->with(['asignado', 'metrica'])
            ->when($this->filtroEstatus === 'hoy', function ($q) use ($hoy) {
                $q->pendientes()->where(function ($inner) use ($hoy) {
                    $inner->whereDate('fecha_compromiso', $hoy)
                        ->orWhereNull('fecha_compromiso');
                });
            })
            ->when($this->filtroEstatus === 'pendientes', fn ($q) => $q->where('estatus', TicketTarea::ESTATUS_PENDIENTE))
            ->when($this->filtroEstatus === 'completadas', fn ($q) => $q->where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                ->whereMonth('completada_at', now()->month)
                ->whereYear('completada_at', now()->year))
            ->when($this->filtroEstatus === 'criticas', fn ($q) => $q->pendientes()->where('prioridad', TicketTarea::PRIORIDAD_CRITICA))
            ->when($this->filtroTipo !== '', fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('titulo', 'like', $term)
                        ->orWhere('razon', 'like', $term)
                        ->orWhereHas('asignado', fn ($a) => $a->where('NombreEmpleado', 'like', $term));
                });
            })
            ->orderByRaw("FIELD(prioridad, 'critica', 'normal')")
            ->orderByRaw('fecha_compromiso IS NULL DESC')
            ->orderBy('fecha_compromiso')
            ->paginate($this->perPage);

        $historialTarea = $this->tareaHistorialId
            ? TicketTarea::with(['historial.usuario', 'historial.asignadoAnterior', 'historial.asignadoNuevo', 'asignado'])->find($this->tareaHistorialId)
            : null;

        $tituloMes = $inicioMes->translatedFormat('F Y');
        $fechaSel = $this->fechaSeleccionada ?: $hoy;
        $fechaCarbonSel = Carbon::parse($fechaSel);

        $tareasDiaSeleccionado = TicketTarea::query()
            ->with(['asignado', 'metrica'])
            ->whereDate('fecha_compromiso', $fechaSel)
            ->orderByRaw("FIELD(prioridad, 'critica', 'normal')")
            ->orderBy('titulo')
            ->get();

        $tareasSinFecha = TicketTarea::query()
            ->with(['asignado', 'metrica'])
            ->pendientes()
            ->whereNull('fecha_compromiso')
            ->orderByRaw("FIELD(prioridad, 'critica', 'normal')")
            ->orderBy('titulo')
            ->get();

        $etiquetaDiaSeleccionado = $fechaCarbonSel->translatedFormat('l d \\d\\e F Y');

        return view('livewire.tabla-tareas', compact(
            'tareas',
            'responsables',
            'kpis',
            'historialTarea',
            'calendario',
            'tareasPorDia',
            'hoy',
            'tituloMes',
            'fechaSel',
            'tareasDiaSeleccionado',
            'tareasSinFecha',
            'etiquetaDiaSeleccionado'
        ));
    }

    private function construirCalendario(Carbon $inicioMes, Collection $tareasPorDia, string $fechaSeleccionada = ''): array
    {
        $inicioGrid = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finGrid = $inicioMes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $semanas = [];
        $cursor = $inicioGrid->copy();
        $sel = $fechaSeleccionada ?: now()->format('Y-m-d');

        while ($cursor->lte($finGrid)) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $fechaStr = $cursor->format('Y-m-d');
                $semana[] = [
                    'fecha' => $cursor->copy(),
                    'fecha_str' => $fechaStr,
                    'dia' => (int) $cursor->day,
                    'mes_actual' => (int) $cursor->month === (int) $inicioMes->month,
                    'es_hoy' => $cursor->isToday(),
                    'es_seleccionado' => $fechaStr === $sel,
                    'tareas' => $tareasPorDia->get($fechaStr, collect()),
                ];
                $cursor->addDay();
            }
            $semanas[] = $semana;
        }

        return $semanas;
    }

    private function resetFormTarea(): void
    {
        $this->titulo = '';
        $this->razon = '';
        $this->asignado_id = '';
        $this->fecha_compromiso = '';
    }

    private function authorizeTab(): void
    {
        abort_unless(auth()->user()?->can('tickets.ver-tareas'), 403);
    }

    private function authorizeGestion(): void
    {
        abort_unless(auth()->user()?->can('tickets.gestionar-tareas'), 403);
    }
}
