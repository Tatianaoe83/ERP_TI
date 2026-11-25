<?php

namespace App\Http\Livewire;

use App\Models\Empleados;
use App\Models\TicketTarea;
// use App\Services\TicketTareaNotificacionService; // Aviso de asignación al responsable, desactivado por ahora
use App\Services\TicketTareaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TablaTareas extends Component
{
    use WithPagination;

    /** '' = sin filtro: tareas con fecha en el día elegido, cualquier estatus. 'sinfecha' = pendientes sin fecha. */
    public string $filtroEstatus = '';
    public string $filtroTipo = '';
    public string $search = '';
    public int $calMes = 1;
    public int $calAnio = 2026;
    public string $fechaSeleccionada = '';
    public int $perPageLista = 8;

    /**
     * En no realizadas / críticas / completadas el listado es global.
     * Al pulsar un día del calendario se acota a esa fecha.
     */
    public bool $soloDia = false;

    /** IDs de empleados que coinciden con la búsqueda; se resuelve una vez por request. */
    private ?array $idsBusqueda = null;

    public bool $modalTareaAbierto = false;
    public bool $modalReagendarAbierto = false;
    public bool $modalDetalleAbierto = false;
    public bool $modalTextoAbierto = false;

    public ?int $tareaEditId = null;
    public ?int $tareaReagendarId = null;
    public ?int $tareaDetalleId = null;
    public ?int $tareaTextoId = null;

    /** La tarea abierta en el modal es de métrica: solo se le cambia el responsable. */
    public bool $editandoMetrica = false;

    /** Quién creó la tarea abierta en el modal. Solo se muestra con tickets.ver-creador-tarea. */
    public ?string $creadorTarea = null;

    public string $titulo = '';
    public string $razon = '';
    /** IDs de personal TI. En alta se puede elegir más de uno; cada quien recibe su propia fila. */
    public array $asignados_ids = [];
    public string $fecha_compromiso = '';

    public string $reagendar_fecha = '';
    public string $reagendar_motivo = '';

    protected $queryString = [
        'filtroEstatus' => ['except' => ''],
        'filtroTipo' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->authorizeTab();
        $this->calMes = max(1, min(12, (int) (request('calMes') ?: now()->month)));
        $this->calAnio = max(2000, (int) (request('calAnio') ?: now()->year));
        $this->fechaSeleccionada = now()->format('Y-m-d');
        $this->filtroEstatus = $this->normalizarFiltro($this->filtroEstatus);
        app(TicketTareaService::class)->actualizarPrioridades();
    }

    public function filtrarKpi(string $estatus): void
    {
        $estatus = $this->normalizarFiltro($estatus);
        // Pulsar el KPI activo otra vez quita el filtro.
        $this->filtroEstatus = $estatus === $this->filtroEstatus ? '' : $estatus;
        $this->soloDia = false;
        $this->resetPage();

        if ($this->filtroEstatus === 'hoy') {
            $this->irHoy();
        }
    }

    private function normalizarFiltro(string $estatus): string
    {
        if ($estatus === 'criticas') {
            $estatus = 'atrasadas';
        }

        return in_array($estatus, ['hoy', 'sinfecha', 'atrasadas', 'completadas'], true) ? $estatus : '';
    }

    public function mesAnterior(): void
    {
        $fecha = Carbon::create($this->calAnio, $this->calMes, 1)->subMonth();
        $this->calMes = (int) $fecha->month;
        $this->calAnio = (int) $fecha->year;
        $this->resetPage();
    }

    public function mesSiguiente(): void
    {
        $fecha = Carbon::create($this->calAnio, $this->calMes, 1)->addMonth();
        $this->calMes = (int) $fecha->month;
        $this->calAnio = (int) $fecha->year;
        $this->resetPage();
    }

    public function irHoy(): void
    {
        $this->calMes = (int) now()->month;
        $this->calAnio = (int) now()->year;
        $this->fechaSeleccionada = now()->format('Y-m-d');
        $this->soloDia = false;
        $this->resetPage();
    }

    public function seleccionarDia(string $fecha): void
    {
        $carbon = Carbon::parse($fecha);
        $this->fechaSeleccionada = $carbon->format('Y-m-d');
        $this->calMes = (int) $carbon->month;
        $this->calAnio = (int) $carbon->year;
        // No realizadas y completadas son listados globales: elegir un día los acota a esa fecha.
        $this->soloDia = in_array($this->filtroEstatus, ['atrasadas', 'completadas'], true);
        $this->resetPage();
    }

    public function verTodas(): void
    {
        $this->soloDia = false;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo(): void
    {
        $this->resetPage();
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
        $tarea = TicketTarea::with('creador')->findOrFail($id);
        $this->tareaEditId = $tarea->id;
        $this->creadorTarea = $this->nombreCreador($tarea);
        $this->titulo = $tarea->titulo;
        $this->razon = (string) ($tarea->razon ?? '');
        $this->asignados_ids = collect($tarea->idsAsignados())->map(fn ($id) => (string) $id)->all();
        $this->fecha_compromiso = optional($tarea->fecha_compromiso)->format('Y-m-d') ?? '';
        $this->editandoMetrica = $tarea->tipo === TicketTarea::TIPO_METRICA;
        $this->resetErrorBag();
        $this->modalTareaAbierto = true;
    }

    // Para activar el aviso de asignación al responsable, agregar el parámetro
    // TicketTareaNotificacionService $notificaciones y descomentar las llamadas a
    // notificarAsignacion() de este método.
    public function guardarTarea(TicketTareaService $service): void
    {
        $this->authorizeGestion();

        $idsPermitidos = Empleados::tiActivos()->pluck('EmpleadoID')->map(fn ($id) => (int) $id)->all();
        if ($this->tareaEditId) {
            $actual = TicketTarea::find($this->tareaEditId);
            foreach ($actual?->idsAsignados() ?? [] as $idActual) {
                $idsPermitidos[] = (int) $idActual;
            }
        }
        $idsPermitidos = array_values(array_unique($idsPermitidos));

        $this->validate([
            'titulo' => 'required|string|max:200',
            'razon' => 'nullable|string|max:2000',
            'asignados_ids' => 'required|array|min:1',
            'asignados_ids.*' => ['integer', Rule::in($idsPermitidos)],
            'fecha_compromiso' => 'nullable|date',
        ], [
            'asignados_ids.required' => 'Seleccione al menos un responsable de TI.',
            'asignados_ids.min' => 'Seleccione al menos un responsable de TI.',
            'asignados_ids.*.in' => 'Solo se puede asignar a personal de TI activo.',
        ], [
            'titulo' => 'título',
            'razon' => 'razón',
            'asignados_ids' => 'asignados',
            'fecha_compromiso' => 'fecha compromiso',
        ]);

        $ids = collect($this->asignados_ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $fecha = trim((string) $this->fecha_compromiso) !== '' ? $this->fecha_compromiso : null;
        $csv = TicketTarea::serializarAsignados($ids->all());
        $principal = (int) $ids->first();

        if ($this->tareaEditId) {
            $tarea = TicketTarea::findOrFail($this->tareaEditId);

            // En una métrica el título y la razón los regenera la plantilla cada mes,
            // así que editarlos no tendría efecto duradero: solo se cambia el responsable.
            if ($tarea->tipo === TicketTarea::TIPO_METRICA) {
                if ($ids->count() > 1) {
                    $this->addError('asignados_ids', 'Las métricas solo admiten un responsable.');

                    return;
                }

                $anterior = $tarea->asignado_id;
                $nuevo = $principal;

                if ((int) $anterior !== $nuevo) {
                    $tarea->update([
                        'asignado_id' => $nuevo,
                        'asignados_ids' => $csv,
                    ]);
                    $service->registrarHistorial($tarea, 'asignada', null, [
                        'asignado_anterior_id' => $anterior,
                        'asignado_nuevo_id' => $nuevo,
                        'notas' => $anterior ? 'Cambio de responsable.' : 'Se asignó responsable.',
                    ]);
                    // $notificaciones->notificarAsignacion($tarea);
                }

                $this->cerrarModal();
                $this->alerta('Responsable actualizado.');

                return;
            }

            $anteriores = $tarea->idsAsignados();
            $anteriorFecha = optional($tarea->fecha_compromiso)->format('Y-m-d');

            $tarea->update([
                'titulo' => $this->titulo,
                'razon' => $this->razon,
                'asignado_id' => $principal,
                'asignados_ids' => $csv,
                'fecha_compromiso' => $fecha,
            ]);

            if ($anteriorFecha && $fecha && $anteriorFecha !== $fecha) {
                $service->reagendar($tarea, $fecha, 'Actualización desde edición de tarea.');
            } elseif ($anteriorFecha !== $fecha) {
                $service->registrarHistorial($tarea, 'reagendada', $fecha ? null : 'Se quitó la fecha de compromiso.', [
                    'fecha_compromiso_anterior' => $anteriorFecha,
                    'fecha_compromiso_nueva' => $fecha,
                ]);
            } elseif (collect($anteriores)->sort()->values()->all() !== $ids->sort()->values()->all()) {
                $service->registrarHistorial($tarea, 'asignada', null, [
                    'asignado_anterior_id' => $anteriores[0] ?? null,
                    'asignado_nuevo_id' => $principal,
                    'notas' => $ids->count() > 1
                        ? 'Responsables actualizados (' . $ids->count() . ' personas).'
                        : 'Cambio de responsable.',
                ]);
            }
        } else {
            $service->crearEvento([
                'titulo' => $this->titulo,
                'razon' => $this->razon,
                'asignados_ids' => $ids->all(),
                'fecha_compromiso' => $fecha,
            ]);
        }

        $this->cerrarModal();
        $this->alerta('Tarea guardada correctamente.');
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

        $this->cerrarModal();
        $this->alerta('Tarea reagendada.');
    }

    public function completarTarea(int $id, TicketTareaService $service): void
    {
        $this->authorizeGestion();
        $tarea = TicketTarea::findOrFail($id);

        // Sin responsable no se completa: si no, no queda registro de quién la hizo.
        if (! $tarea->tieneResponsable()) {
            $this->alerta('Asigna un responsable antes de completar la tarea.', 'error');

            return;
        }

        // Evita repetir la acción (pisaría completada_at y duplicaría el historial).
        if ($tarea->estatus === TicketTarea::ESTATUS_COMPLETADA) {
            $this->alerta('Esa tarea ya estaba completada.', 'error');

            return;
        }

        $service->completar($tarea);
        $this->alerta('Tarea completada.');
    }

    public function abrirDetalle(int $id): void
    {
        $this->authorizeTab();
        TicketTarea::findOrFail($id);
        $this->tareaDetalleId = $id;
        $this->modalDetalleAbierto = true;
    }

    public function abrirEditarTexto(int $id): void
    {
        $this->authorizeGestion();
        $tarea = TicketTarea::findOrFail($id);
        if ($tarea->estatus !== TicketTarea::ESTATUS_PENDIENTE) {
            $this->alerta('Las tareas completadas no se pueden editar.', 'error');

            return;
        }
        $this->tareaTextoId = $tarea->id;
        $this->titulo = $tarea->titulo;
        $this->razon = (string) ($tarea->razon ?? '');
        $this->modalDetalleAbierto = false;
        $this->modalTareaAbierto = false;
        $this->resetErrorBag();
        $this->modalTextoAbierto = true;
    }

    public function guardarTextoTarea(TicketTareaService $service): void
    {
        $this->authorizeGestion();

        $this->titulo = trim($this->titulo);
        $this->razon = trim($this->razon);

        $this->validate([
            'titulo' => 'required|string|max:200',
            'razon' => 'nullable|string|max:2000',
        ], [], [
            'titulo' => 'título',
            'razon' => 'descripción',
        ]);

        $tarea = TicketTarea::findOrFail($this->tareaTextoId);
        if ($tarea->estatus !== TicketTarea::ESTATUS_PENDIENTE) {
            $this->cerrarModal();
            $this->alerta('Las tareas completadas no se pueden editar.', 'error');

            return;
        }
        $razonNueva = $this->razon !== '' ? $this->razon : null;
        $cambioTitulo = $tarea->titulo !== $this->titulo;
        $cambioRazon = (string) ($tarea->razon ?? '') !== (string) ($razonNueva ?? '');

        if ($cambioTitulo || $cambioRazon) {
            $notas = [];
            if ($cambioTitulo) {
                $notas[] = 'Título: «'.$tarea->titulo.'» → «'.$this->titulo.'».';
            }
            if ($cambioRazon) {
                $notas[] = 'Descripción actualizada.';
            }

            $tarea->update([
                'titulo' => $this->titulo,
                'razon' => $razonNueva,
            ]);
            $service->registrarHistorial($tarea, 'editada', null, [
                'notas' => implode(' ', $notas),
            ]);
            $this->alerta('Título y descripción actualizados.');
        }

        $this->cerrarModal();
    }

    public function render()
    {
        $this->authorizeTab();

        $responsables = Empleados::tiActivos()
            ->orderBy('NombreEmpleado')
            ->get(['EmpleadoID', 'NombreEmpleado']);

        $hoy = now()->format('Y-m-d');
        $fechaSel = $this->fechaSeleccionada ?: $hoy;

        $inicioMes = Carbon::create($this->calAnio, $this->calMes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth();

        // Un viaje con subselects que reusan los scopes: la regla de cada KPI vive solo en TicketTarea.
        $contar = fn ($q) => $q->selectRaw('COUNT(*)');
        $kpis = array_map('intval', (array) DB::query()
            ->selectSub($contar(TicketTarea::deHoy()), 'hoy')
            ->selectSub($contar(TicketTarea::sinFecha()), 'sinfecha')
            ->selectSub($contar(TicketTarea::noRealizadas()), 'atrasadas')
            ->selectSub($contar(TicketTarea::where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                ->whereBetween('fecha_compromiso', [$inicioMes->toDateString(), $finMes->toDateString()])), 'completadas_mes')
            ->first());

        $tareasMes = TicketTarea::query()
            ->whereBetween('fecha_compromiso', [$inicioMes->toDateString(), $finMes->toDateString()]);
        $this->aplicarFiltrosComunes($tareasMes);
        $tareasMes = $tareasMes->orderBy('fecha_compromiso')->get();

        $tareasPorDia = $tareasMes->groupBy(fn ($t) => $t->fecha_compromiso->format('Y-m-d'));
        $calendario = $this->construirCalendario($inicioMes, $tareasPorDia, $fechaSel);

        $listaTareas = $this->consultarListado($hoy, $fechaSel);
        if ($listaTareas->currentPage() > 1 && $listaTareas->isEmpty()) {
            $this->resetPage();
            $listaTareas = $this->consultarListado($hoy, $fechaSel);
        }

        $tareaDetalle = ($this->modalDetalleAbierto && $this->tareaDetalleId)
            ? TicketTarea::with(['creador', 'historial.usuario', 'historial.asignadoAnterior', 'historial.asignadoNuevo'])->find($this->tareaDetalleId)
            : null;
        $creadorDetalle = $tareaDetalle ? $this->nombreCreador($tareaDetalle) : null;

        $tituloMes = ucfirst($inicioMes->translatedFormat('F Y'));
        $etiquetaDiaSeleccionado = Carbon::parse($fechaSel)->translatedFormat('l d \\d\\e F Y');

        // Solo el listado y el detalle muestran nombres; el calendario solo cuenta.
        $nombresResponsables = $this->mapaNombresAsignados(
            $listaTareas->getCollection(),
            $tareaDetalle ? collect([$tareaDetalle]) : collect()
        );

        $tituloSeccion = match ($this->filtroEstatus) {
            'atrasadas' => 'No realizadas',
            'completadas' => 'Completadas',
            'hoy' => 'Pendientes de hoy',
            'sinfecha' => 'Sin fecha asignada',
            default => 'Toda la actividad',
        };

        $tituloLista = match ($this->filtroEstatus) {
            'atrasadas' => $this->soloDia ? 'No realizadas del ' . $etiquetaDiaSeleccionado : 'No realizadas',
            'completadas' => $this->soloDia ? 'Completadas del ' . $etiquetaDiaSeleccionado : 'Completadas de ' . $tituloMes,
            'sinfecha' => 'Pendientes sin fecha asignada',
            default => ucfirst($etiquetaDiaSeleccionado),
        };

        return view('livewire.tabla-tareas', compact(
            'responsables',
            'nombresResponsables',
            'kpis',
            'tareaDetalle',
            'creadorDetalle',
            'calendario',
            'hoy',
            'tituloMes',
            'fechaSel',
            'listaTareas',
            'tituloLista',
            'tituloSeccion'
        ));
    }

    private function consultarListado(string $hoy, string $fechaSel)
    {
        $q = TicketTarea::query();
        $this->aplicarFiltrosComunes($q);

        if ($this->filtroEstatus === 'atrasadas') {
            $q->noRealizadas();
            if ($this->soloDia) {
                $q->whereDate('fecha_compromiso', $fechaSel);
            }

            return $this->paginarListado(
                $q->orderByRaw("CASE WHEN prioridad = 'critica' THEN 0 ELSE 1 END")
                    ->orderBy('fecha_compromiso')
                    ->orderBy('titulo')
            );
        }

        if ($this->filtroEstatus === 'completadas') {
            $q->where('estatus', TicketTarea::ESTATUS_COMPLETADA)
                ->whereNotNull('fecha_compromiso');
            if ($this->soloDia) {
                $q->whereDate('fecha_compromiso', $fechaSel);
            } else {
                $inicio = Carbon::create($this->calAnio, $this->calMes, 1)->toDateString();
                $fin = Carbon::create($this->calAnio, $this->calMes, 1)->endOfMonth()->toDateString();
                $q->whereBetween('fecha_compromiso', [$inicio, $fin]);
            }

            return $this->paginarListado($q->orderByDesc('completada_at')->orderBy('titulo'));
        }

        if ($this->filtroEstatus === 'sinfecha') {
            return $this->paginarListado(
                $q->sinFecha()->orderByRaw("FIELD(prioridad, 'critica', 'normal')")->orderBy('titulo')
            );
        }

        // Sin filtro: todo lo que tiene fecha en ese día. Con "hoy": solo pendientes no vencidos.
        $q->whereDate('fecha_compromiso', $fechaSel);
        if ($this->filtroEstatus === 'hoy') {
            $q->pendientes()->whereDate('fecha_compromiso', '>=', $hoy);
        }

        return $this->paginarListado(
            $q->orderByRaw('estatus = ? DESC', [TicketTarea::ESTATUS_PENDIENTE])
                ->orderByRaw("FIELD(prioridad, 'critica', 'normal')")
                ->orderBy('fecha_compromiso')
                ->orderBy('titulo')
        );
    }

    private function paginarListado($query)
    {
        return $query->paginate(max(5, min(50, (int) $this->perPageLista)));
    }

    private function aplicarFiltrosComunes($q)
    {
        $q->when($this->filtroTipo !== '', fn ($inner) => $inner->where('tipo', $this->filtroTipo));
        $q->when(trim($this->search) !== '', function ($inner) {
            $term = '%' . trim($this->search) . '%';
            $idsNombre = $this->idsBusqueda ??= Empleados::where('NombreEmpleado', 'like', $term)
                ->pluck('EmpleadoID')->map(fn ($id) => (int) $id)->all();
            $inner->where(function ($sub) use ($term, $idsNombre) {
                $sub->where('titulo', 'like', $term)
                    ->orWhere('razon', 'like', $term);
                if ($idsNombre !== []) {
                    $sub->orWhereIn('asignado_id', $idsNombre);
                }
                foreach ($idsNombre as $eid) {
                    $sub->orWhereRaw('FIND_IN_SET(?, COALESCE(asignados_ids, ""))', [$eid]);
                }
            });
        });
    }

    private function mapaNombresAsignados(...$grupos)
    {
        $ids = collect($grupos)
            ->flatten()
            ->filter(fn ($t) => $t instanceof TicketTarea)
            ->flatMap(fn (TicketTarea $t) => $t->idsAsignados())
            ->unique()
            ->filter();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Empleados::whereIn('EmpleadoID', $ids)->pluck('NombreEmpleado', 'EmpleadoID');
    }

    private function tareaEntraEnFiltro(TicketTarea $tarea): bool
    {
        $clase = $tarea->claseSemaforo();

        return match ($this->filtroEstatus) {
            'hoy' => $tarea->estatus === TicketTarea::ESTATUS_PENDIENTE && ! $tarea->estaVencida(),
            'atrasadas' => $tarea->estaVencida() || $clase === 'is-critica',
            'completadas' => $clase === 'is-done',
            'sinfecha' => false, // no tienen día en el calendario
            default => true,
        };
    }

    private function construirCalendario(Carbon $inicioMes, Collection $tareasPorDia, string $sel): array
    {
        $inicioGrid = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finGrid = $inicioMes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $semanas = [];
        $cursor = $inicioGrid->copy();

        while ($cursor->lte($finGrid)) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $fechaStr = $cursor->format('Y-m-d');
                $delDia = $tareasPorDia->get($fechaStr, collect())
                    ->filter(fn (TicketTarea $t) => $this->tareaEntraEnFiltro($t))
                    ->values();

                $conteo = [
                    'is-critica' => 0,
                    'is-vencida' => 0,
                    'is-pendiente' => 0,
                    'is-metrica' => 0,
                    'is-done' => 0,
                ];
                foreach ($delDia as $tarea) {
                    $clave = $tarea->claseSemaforo();
                    if (isset($conteo[$clave])) {
                        $conteo[$clave]++;
                    }
                }
                $alerta = 'ninguna';
                if ($conteo['is-critica'] > 0) {
                    $alerta = 'critica';
                } elseif ($conteo['is-vencida'] > 0) {
                    $alerta = 'vencida';
                } elseif (($conteo['is-pendiente'] + $conteo['is-metrica']) > 0) {
                    $alerta = 'pendiente';
                } elseif ($conteo['is-done'] > 0) {
                    $alerta = 'done';
                }

                $semana[] = [
                    'fecha' => $cursor->copy(),
                    'fecha_str' => $fechaStr,
                    'dia' => (int) $cursor->day,
                    'mes_actual' => (int) $cursor->month === (int) $inicioMes->month,
                    'es_hoy' => $cursor->isToday(),
                    'es_seleccionado' => $fechaStr === $sel,
                    'tareas' => $delDia,
                    'conteo' => $conteo,
                    'alerta' => $alerta,
                    'total' => $delDia->count(),
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
        $this->asignados_ids = [];
        $this->fecha_compromiso = '';
        $this->editandoMetrica = false;
        $this->creadorTarea = null;
    }

    /** Las tareas de métrica las genera el comando programado, no un usuario. */
    private function nombreCreador(TicketTarea $tarea): string
    {
        if ($tarea->creador) {
            return $tarea->creador->name ?: $tarea->creador->username;
        }

        return $tarea->tipo === TicketTarea::TIPO_METRICA
            ? 'Sistema (métrica mensual)'
            : 'Sin registro';
    }

    private function authorizeTab(): void
    {
        abort_unless(auth()->user()?->can('tickets.ver-tareas'), 403);
    }

    private function authorizeGestion(): void
    {
        abort_unless(auth()->user()?->can('tickets.gestionar-tareas'), 403);
    }

    /**
     * Cierra el modal abierto con su animación de salida: Alpine anima y luego baja la
     * propiedad (tareasModalCerrable). Los campos no se limpian aquí para que no se
     * vacíen mientras sale; cada abrir* los vuelve a llenar.
     */
    private function cerrarModal(): void
    {
        $this->dispatchBrowserEvent('tareas-modal-cerrar');
    }

    /** Alerta SweetAlert2; el listener vive en partials/tareas-alerta. */
    private function alerta(string $mensaje, string $icono = 'success'): void
    {
        $this->dispatchBrowserEvent('tareas-alerta', ['icono' => $icono, 'mensaje' => $mensaje]);
    }
}
