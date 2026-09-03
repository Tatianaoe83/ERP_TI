<div class="tareas-module" wire:poll.60s>
    @include('partials.tareas-alerta', ['mensaje' => session('tareas_mensaje')])
    @include('partials.tareas-alerta', ['mensaje' => session('tareas_error'), 'tipo' => 'error'])

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <button type="button" wire:click="filtrarKpi('hoy')" class="tareas-kpi {{ $filtroEstatus === 'hoy' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">Pendientes de hoy</span>
            <span class="tareas-kpi__value">{{ $kpis['hoy'] }}</span>
        </button>
        <button type="button" wire:click="filtrarKpi('criticas')" class="tareas-kpi tareas-kpi--danger {{ $filtroEstatus === 'criticas' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">Críticas (+2 días)</span>
            <span class="tareas-kpi__value">{{ $kpis['criticas'] }}</span>
        </button>
        <button type="button" wire:click="filtrarKpi('completadas')" class="tareas-kpi tareas-kpi--ok {{ $filtroEstatus === 'completadas' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">Completadas</span>
            <span class="tareas-kpi__value">{{ $kpis['completadas_mes'] }}</span>
        </button>
    </div>

    <div class="index-page__card overflow-hidden">
        {{-- Nivel 1: sección principal (Tareas vs Métricas) --}}
        <div class="tareas-section-bar">
            <div class="tareas-section-bar__tabs">
                <span class="tareas-section-title"><i class="fas fa-tasks"></i> Mis tareas de hoy</span>
            </div>
            <div class="tareas-section-bar__actions">
                @can('tickets.gestionar-tareas')
                <button type="button" wire:click="abrirModalNuevaTarea" class="index-page__btn-primary">
                    <i class="fas fa-plus"></i> Nueva tarea
                </button>
                @endcan
            </div>
        </div>

        @if($modoLista === 'calendario')
        <div class="tareas-cal-wrap p-4">
            {{-- Nivel 2: controles del calendario (mes + vista) --}}
            <div class="tareas-cal-toolbar">
                <div class="tareas-cal-toolbar__nav">
                    <button type="button" wire:click="mesAnterior" class="tarea-btn" title="Mes anterior"><i class="fas fa-chevron-left"></i></button>
                    <div class="tareas-cal-nav__title">
                        <strong>{{ ucfirst($tituloMes) }}</strong>
                        @if($fechaSel === $hoy)
                        <span class="tarea-badge tarea-badge--pendiente ml-2">Hoy</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="irHoy" class="index-page__btn-secondary">Ir a hoy</button>
                        <button type="button" wire:click="mesSiguiente" class="tarea-btn" title="Mes siguiente"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="tareas-cal-toolbar__vista">
                    <span class="tareas-cal-toolbar__label">Vista:</span>
                    <div class="tareas-vista-toggle">
                        <button type="button" wire:click="$set('modoLista', 'calendario')" class="tareas-vista-btn {{ $modoLista === 'calendario' ? 'is-active' : '' }}">
                            <i class="fas fa-calendar-alt"></i> Calendario
                        </button>
                        <button type="button" wire:click="$set('modoLista', 'tarjetas')" class="tareas-vista-btn {{ $modoLista === 'tarjetas' ? 'is-active' : '' }}">
                            <i class="fas fa-th-large"></i> Tarjetas
                        </button>
                    </div>
                </div>
            </div>

            <div class="tareas-cal-legend mb-3">
                <span><i class="tareas-dot tareas-dot--hoy"></i> Hoy</span>
                <span><i class="tareas-dot tareas-dot--seleccionado"></i> Día seleccionado</span>
                <span><i class="tareas-dot tareas-dot--evento"></i> Evento</span>
                <span><i class="tareas-dot tareas-dot--metrica"></i> Métrica</span>
                <span><i class="tareas-dot tareas-dot--critica"></i> Crítica</span>
            </div>

            <div class="tareas-cal-grid">
                @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $dh)
                <div class="tareas-cal-head">{{ $dh }}</div>
                @endforeach

                @foreach($calendario as $semana)
                    @foreach($semana as $celda)
                    <button type="button"
                        wire:click="seleccionarDia('{{ $celda['fecha_str'] }}')"
                        class="tareas-cal-cell {{ !$celda['mes_actual'] ? 'is-outside' : '' }} {{ $celda['es_hoy'] ? 'is-hoy' : '' }} {{ $celda['es_seleccionado'] ? 'is-selected' : '' }}"
                        title="Ver tareas del {{ $celda['fecha']->format('d/m/Y') }}">
                        <div class="tareas-cal-cell__day">{{ $celda['dia'] }}</div>
                        <div class="tareas-cal-cell__items">
                            @foreach($celda['tareas'] as $tarea)
                            @php
                                $cls = $tarea->prioridad === 'critica' ? 'is-critica' : ($tarea->tipo === 'metrica' ? 'is-metrica' : 'is-evento');
                                if ($tarea->estatus === 'completada') $cls .= ' is-done';
                            @endphp
                            <span role="button" tabindex="0"
                                wire:click.stop="abrirHistorial({{ $tarea->id }})"
                                class="tareas-cal-item {{ $cls }}"
                                title="{{ $tarea->titulo }}">
                                {{ Str::limit($tarea->titulo, 22) }}
                            </span>
                            @endforeach
                        </div>
                    </button>
                    @endforeach
                @endforeach
            </div>

            @if($tareasSinFecha->isNotEmpty())
            <div class="tareas-dia-panel mt-4">
                <h4 class="tareas-dia-panel__title">
                    <i class="fas fa-hourglass-half"></i>
                    Sin fecha de compromiso
                </h4>
                <div class="tareas-dia-list">
                    @foreach($tareasSinFecha as $tarea)
                    <div class="tareas-dia-item {{ $tarea->prioridad === 'critica' ? 'is-critica' : '' }}">
                        <div>
                            <strong>{{ $tarea->titulo }}</strong>
                            <div class="text-xs opacity-75">
                                {{ $tarea->tipo === 'metrica' ? 'Métrica mensual' : 'Evento' }}
                                · {{ optional($tarea->asignado)->NombreEmpleado ?: 'Por asignar' }}
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @can('tickets.gestionar-tareas')
                            @if($tarea->estatus === 'pendiente')
                            <button type="button" wire:click.stop="completarTarea({{ $tarea->id }})"
                                    wire:target="completarTarea({{ $tarea->id }})"
                                    wire:loading.attr="disabled"
                                    class="tarea-btn tarea-btn--ok" title="Completar">
                                <i class="fas fa-check" wire:loading.remove wire:target="completarTarea({{ $tarea->id }})"></i>
                                <i class="fas fa-spinner fa-spin" wire:loading wire:target="completarTarea({{ $tarea->id }})"></i>
                            </button>
                            @endif
                            @endcan
                            <button type="button" wire:click.stop="abrirHistorial({{ $tarea->id }})" class="tarea-btn" title="Historial"><i class="fas fa-history"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="tareas-dia-panel mt-4">
                <h4 class="tareas-dia-panel__title">
                    <i class="fas fa-calendar-day"></i>
                    Tareas del {{ ucfirst($etiquetaDiaSeleccionado) }}
                </h4>
                @if($tareasDiaSeleccionado->isEmpty())
                <p class="text-sm text-slate-500 mb-0">No hay tareas programadas para esta fecha.</p>
                @else
                <div class="tareas-dia-list">
                    @foreach($tareasDiaSeleccionado as $tarea)
                    <div class="tareas-dia-item {{ $tarea->prioridad === 'critica' ? 'is-critica' : '' }} {{ $tarea->estatus === 'completada' ? 'is-done' : '' }}">
                        <div>
                            <strong>{{ $tarea->titulo }}</strong>
                            <div class="text-xs opacity-75">
                                {{ $tarea->tipo === 'metrica' ? 'Métrica mensual' : 'Evento' }}
                                · {{ optional($tarea->asignado)->NombreEmpleado ?: 'Por asignar' }}
                                @if($tarea->estatus === 'completada') · Completada @endif
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @can('tickets.gestionar-tareas')
                            @if($tarea->estatus === 'pendiente')
                            <button type="button" wire:click.stop="completarTarea({{ $tarea->id }})"
                                    wire:target="completarTarea({{ $tarea->id }})"
                                    wire:loading.attr="disabled"
                                    class="tarea-btn tarea-btn--ok" title="Completar">
                                <i class="fas fa-check" wire:loading.remove wire:target="completarTarea({{ $tarea->id }})"></i>
                                <i class="fas fa-spinner fa-spin" wire:loading wire:target="completarTarea({{ $tarea->id }})"></i>
                            </button>
                            @endif
                            @endcan
                            <button type="button" wire:click.stop="abrirHistorial({{ $tarea->id }})" class="tarea-btn" title="Historial"><i class="fas fa-history"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="tareas-view-bar">
            <div class="tareas-view-bar__filters">
                <select wire:model.live="filtroTipo" class="form-control tareas-select">
                    <option value="">Todos los tipos</option>
                    <option value="evento">Eventos</option>
                    <option value="metrica">Métricas</option>
                </select>
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control tareas-search" placeholder="Buscar título, razón o responsable...">
            </div>
            <div class="tareas-view-bar__vista">
                <span class="tareas-cal-toolbar__label">Vista:</span>
                <div class="tareas-vista-toggle">
                    <button type="button" wire:click="$set('modoLista', 'calendario')" class="tareas-vista-btn {{ $modoLista === 'calendario' ? 'is-active' : '' }}">
                        <i class="fas fa-calendar-alt"></i> Calendario
                    </button>
                    <button type="button" wire:click="$set('modoLista', 'tarjetas')" class="tareas-vista-btn {{ $modoLista === 'tarjetas' ? 'is-active' : '' }}">
                        <i class="fas fa-th-large"></i> Tarjetas
                    </button>
                </div>
            </div>
        </div>
        <div class="tareas-grid p-4">
            @forelse($tareas as $tarea)
            @php
                $esCritica = $tarea->prioridad === 'critica';
                $esVencida = $tarea->estaVencida();
                $esCompletada = $tarea->estatus === 'completada';
                $cardClass = $esCompletada
                    ? 'tarea-card--completada'
                    : ($esCritica ? 'tarea-card--critica' : ($esVencida ? 'tarea-card--vencida' : 'tarea-card--normal'));
            @endphp
            <article wire:key="tarea-{{ $tarea->id }}" class="tarea-card {{ $cardClass }}">
                <div class="tarea-card__head">
                    <div>
                        <span class="tarea-card__tipo">{{ $tarea->tipo === 'metrica' ? 'Métrica' : 'Evento' }}</span>
                        <h3 class="tarea-card__title">{{ $tarea->titulo }}</h3>
                    </div>
                    @if($tarea->estatus === 'completada')
                    <span class="tarea-badge tarea-badge--ok"><i class="fas fa-check"></i> Completada</span>
                    @elseif($esCritica)
                    <span class="tarea-badge tarea-badge--critica"><i class="fas fa-exclamation-triangle"></i> Crítica</span>
                    @elseif($esVencida)
                    <span class="tarea-badge tarea-badge--warn"><i class="fas fa-clock"></i> Vencida</span>
                    @else
                    <span class="tarea-badge tarea-badge--pendiente">Pendiente</span>
                    @endif
                </div>

                @if($tarea->razon)
                <p class="tarea-card__razon">{{ Str::limit($tarea->razon, 140) }}</p>
                @endif

                <div class="tarea-card__meta">
                    @php $asignadoNombre = optional($tarea->asignado)->NombreEmpleado; @endphp
                    <div class="{{ $asignadoNombre ? '' : 'tarea-card__sin-asignar' }}">
                        <i class="fas fa-user"></i> {{ $asignadoNombre ?: 'Por asignar' }}
                    </div>
                    <div><i class="fas fa-calendar-day"></i> {{ optional($tarea->fecha_compromiso)->format('d/m/Y') ?? 'Sin fecha' }}</div>
                </div>

                <div class="tarea-card__actions">
                    <button type="button" wire:click="abrirHistorial({{ $tarea->id }})" class="tarea-btn" title="Historial">
                        <i class="fas fa-history"></i>
                    </button>
                    @can('tickets.gestionar-tareas')
                    @if($tarea->estatus === 'pendiente')
                    <button type="button" wire:click="abrirReagendar({{ $tarea->id }})" class="tarea-btn" title="Reagendar">
                        <i class="fas fa-calendar-alt"></i>
                    </button>
                    {{-- En métricas el modal solo deja cambiar el responsable --}}
                    <button type="button" wire:click="abrirModalEditarTarea({{ $tarea->id }})"
                            class="tarea-btn {{ $asignadoNombre ? '' : 'tarea-btn--alerta' }}"
                            title="{{ $tarea->tipo === 'metrica' ? 'Cambiar responsable' : 'Editar' }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" wire:click="completarTarea({{ $tarea->id }})"
                            wire:target="completarTarea({{ $tarea->id }})"
                            wire:loading.attr="disabled"
                            class="tarea-btn tarea-btn--ok" title="Completar">
                        <i class="fas fa-check" wire:loading.remove wire:target="completarTarea({{ $tarea->id }})"></i>
                        <i class="fas fa-spinner fa-spin" wire:loading wire:target="completarTarea({{ $tarea->id }})"></i>
                    </button>
                    @endif
                    @endcan
                </div>
            </article>
            @empty
            <div class="tareas-empty col-span-full">
                <i class="fas fa-clipboard-list"></i>
                <p>No hay tareas con los filtros actuales.</p>
                @can('tickets.gestionar-tareas')
                <button type="button" wire:click="abrirModalNuevaTarea" class="index-page__btn-primary mt-3">Crear primera tarea</button>
                @endcan
            </div>
            @endforelse
        </div>

        @if($tareas->hasPages())
        <div class="px-4 pb-4">{{ $tareas->links() }}</div>
        @endif
        @endif
    </div>

    {{-- Modal tarea --}}
    @if($modalTareaAbierto)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalTareaAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()">
        <div class="tareas-modal">
            <div class="tareas-modal__head">
                <h3>
                    @if($editandoMetrica) Cambiar responsable
                    @elseif($tareaEditId) Editar tarea
                    @else Nueva tarea / evento @endif
                </h3>
                <button type="button" @click="cerrar()" class="tareas-modal__close" aria-label="Cerrar">&times;</button>
            </div>
            <form wire:submit.prevent="guardarTarea" class="tareas-modal__body">
                @if($editandoMetrica)
                <p class="tareas-nota">
                    <i class="fas fa-info-circle"></i>
                    Es una <strong>métrica mensual</strong>: su título y razón los vuelve a generar la
                    plantilla cada mes, por eso aquí solo se cambia el responsable.
                    Para mover la fecha usa <strong>Reagendar</strong>.
                </p>
                @endif

                <div class="form-group mb-3">
                    <label>Título</label>
                    <input type="text" wire:model.defer="titulo" class="form-control" required
                           @if($editandoMetrica) readonly @endif>
                    @error('titulo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="form-group mb-3">
                    <label>Razón / descripción</label>
                    <textarea wire:model.defer="razon" class="form-control" rows="3"
                              placeholder="¿Para qué es esta tarea?"
                              @if($editandoMetrica) readonly @endif></textarea>
                    @error('razon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div class="form-group">
                        <label>Asignar a (personal TI activo)</label>
                        <select wire:model.defer="asignado_id" class="form-control" required autofocus>
                            <option value="">Seleccione responsable de TI</option>
                            @foreach($responsables as $emp)
                            <option value="{{ $emp->EmpleadoID }}">{{ $emp->NombreEmpleado }}</option>
                            @endforeach
                        </select>
                        @error('asignado_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Fecha compromiso <span class="text-xs opacity-60 font-normal">(opcional)</span></label>
                        <input type="date" wire:model.defer="fecha_compromiso" class="form-control"
                               @if($editandoMetrica) readonly @endif>
                        @error('fecha_compromiso') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="tareas-modal__foot">
                    <button type="button" @click="cerrar()" class="index-page__btn-secondary">Cancelar</button>
                    <button type="submit" class="index-page__btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal reagendar --}}
    @if($modalReagendarAbierto)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalReagendarAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()">
        <div class="tareas-modal">
            <div class="tareas-modal__head">
                <h3>Reagendar tarea</h3>
                <button type="button" @click="cerrar()" class="tareas-modal__close">&times;</button>
            </div>
            <form wire:submit.prevent="guardarReagendar" class="tareas-modal__body">
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Indique la nueva fecha y el motivo del cambio. Quedará registrado en el historial.</p>
                <div class="form-group mb-3">
                    <label>Nueva fecha compromiso</label>
                    <input type="date" wire:model.defer="reagendar_fecha" class="form-control" required>
                    @error('reagendar_fecha') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="form-group mb-3">
                    <label>Motivo</label>
                    <textarea wire:model.defer="reagendar_motivo" class="form-control" rows="3" required placeholder="Ej. espera de proveedor, cambio de prioridades..."></textarea>
                    @error('reagendar_motivo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="tareas-modal__foot">
                    <button type="button" @click="cerrar()" class="index-page__btn-secondary">Cancelar</button>
                    <button type="submit" class="index-page__btn-primary">Reagendar</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal historial --}}
    @if($modalHistorialAbierto && $historialTarea)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalHistorialAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()">
        <div class="tareas-modal tareas-modal--wide">
            <div class="tareas-modal__head">
                <h3>Historial — {{ $historialTarea->titulo }}</h3>
                <button type="button" @click="cerrar()" class="tareas-modal__close">&times;</button>
            </div>
            <div class="tareas-modal__body">
                <div class="tareas-timeline">
                    @forelse($historialTarea->historial as $item)
                    <div class="tareas-timeline__item">
                        <div class="tareas-timeline__dot"></div>
                        <div class="tareas-timeline__content">
                            <div class="tareas-timeline__title">
                                {{ ucfirst($item->accion) }}
                                <span class="text-xs text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($item->motivo)
                            <p class="text-sm"><strong>Motivo:</strong> {{ $item->motivo }}</p>
                            @endif
                            @if($item->fecha_compromiso_anterior || $item->fecha_compromiso_nueva)
                            <p class="text-sm">
                                Fecha:
                                {{ optional($item->fecha_compromiso_anterior)->format('d/m/Y') ?? '—' }}
                                →
                                {{ optional($item->fecha_compromiso_nueva)->format('d/m/Y') ?? '—' }}
                            </p>
                            @endif
                            @if($item->notas)
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $item->notas }}</p>
                            @endif
                            @if($item->usuario)
                            <p class="text-xs text-slate-500">Por: {{ $item->usuario->name }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-slate-500">Sin movimientos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
        .tareas-kpi { text-align:left; border:1px solid rgba(148,163,184,.35); border-radius:14px; padding:.85rem 1rem; background:#fff; transition:.15s; }
        .dark .tareas-kpi { background:#101010; border-color:#334155; }
        .tareas-kpi.is-active { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.25); }
        .tareas-kpi--danger .tareas-kpi__value { color:#ef4444; }
        .tareas-kpi--ok .tareas-kpi__value { color:#10b981; }
        .tareas-kpi--info .tareas-kpi__value { color:#6366f1; }
        .tareas-kpi__label { display:block; font-size:.75rem; opacity:.75; }
        .tareas-kpi__value { display:block; font-size:1.5rem; font-weight:700; line-height:1.1; }
        .tareas-section-bar { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-bottom:2px solid rgba(148,163,184,.2); background:rgba(248,250,252,.6); }
        .dark .tareas-section-bar { background:rgba(15,23,42,.5); border-bottom-color:#334155; }
        .tareas-section-bar__tabs { display:flex; gap:.35rem; align-items:center; }
        .tareas-section-title { font-size:.95rem; font-weight:700; }
        .tareas-section-bar__actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
        .tareas-cal-toolbar { display:flex; flex-wrap:wrap; gap:1rem; justify-content:space-between; align-items:center; margin-bottom:1rem; padding:.75rem 1rem; border-radius:12px; background:rgba(148,163,184,.08); border:1px solid rgba(148,163,184,.2); }
        .dark .tareas-cal-toolbar { background:rgba(30,41,59,.5); border-color:#334155; }
        .tareas-cal-toolbar__nav { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
        .tareas-cal-toolbar__vista, .tareas-view-bar__vista { display:flex; align-items:center; gap:.5rem; }
        .tareas-cal-toolbar__label { font-size:.78rem; opacity:.65; font-weight:600; text-transform:uppercase; letter-spacing:.03em; }
        .tareas-view-bar { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; align-items:center; padding:.75rem 1.25rem; border-bottom:1px solid rgba(148,163,184,.2); background:rgba(148,163,184,.05); }
        .dark .tareas-view-bar { background:rgba(30,41,59,.35); }
        .tareas-view-bar__filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; flex:1 1 auto; }
        /* Bootstrap pone .form-control en display:block y width:100%, y dentro del
           flex eso hacía que cada filtro ocupara su propia fila (se veían apilados). */
        .tareas-view-bar__filters .form-control { width:auto; display:inline-block; }
        .tareas-select { flex:0 0 auto; width:auto; min-width:170px; }
        .tareas-search { flex:1 1 240px; width:auto; min-width:200px; max-width:380px; }

        @media (max-width: 640px) {
            .tareas-view-bar { flex-direction:column; align-items:stretch; }
            .tareas-view-bar__filters .form-control { width:100%; }
            .tareas-select, .tareas-search { flex:1 1 100%; max-width:none; }
        }
        .tareas-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
        /* ── Tarjeta de tarea ─────────────────────────────────────────────
           Franja lateral de color = estado (se lee de un vistazo, y no depende
           solo del color: el badge de arriba lo repite en texto).
           Hover/press solo con transform+opacity para no provocar reflow.     */
        .tarea-card {
            position:relative;
            border:1px solid rgba(148,163,184,.3);
            border-radius:14px;
            padding:1rem 1rem 1rem 1.15rem;
            background:#fff;
            display:flex; flex-direction:column; gap:.6rem;
            box-shadow:0 1px 2px rgba(15,23,42,.05);
            transition:transform .18s ease-out, box-shadow .18s ease-out, border-color .18s ease-out;
        }
        .tarea-card::before {
            content:''; position:absolute; left:0; top:0; bottom:0;
            width:3px; border-radius:14px 0 0 14px;
            background:#cbd5e1;
        }
        .tarea-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.1); }
        .dark .tarea-card { background:#0f172a; border-color:#334155; box-shadow:none; }
        .dark .tarea-card:hover { box-shadow:0 8px 20px rgba(0,0,0,.45); }

        .tarea-card--critica { border-color:rgba(239,68,68,.5); }
        .tarea-card--critica::before { background:#ef4444; }
        .tarea-card--vencida { border-color:rgba(245,158,11,.5); }
        .tarea-card--vencida::before { background:#f59e0b; }
        .tarea-card--normal::before { background:#3b82f6; }
        /* Completada: se atenúa y el título va tachado, para que no compita
           visualmente con lo que sigue pendiente. */
        .tarea-card--completada { opacity:.72; }
        .tarea-card--completada::before { background:#10b981; }
        .tarea-card--completada .tarea-card__title { text-decoration:line-through; text-decoration-thickness:1px; }
        .tarea-card--completada:hover { opacity:1; }

        .tarea-card__head { display:flex; justify-content:space-between; gap:.6rem; align-items:flex-start; }
        .tarea-card__tipo {
            display:inline-block; font-size:.65rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.06em;
            color:#64748b; background:rgba(148,163,184,.16);
            padding:.15rem .45rem; border-radius:5px;
        }
        .dark .tarea-card__tipo { color:#94a3b8; background:rgba(148,163,184,.14); }
        .tarea-card__title {
            font-size:.98rem; font-weight:650; line-height:1.35;
            margin:.4rem 0 0; color:#0f172a;
            /* wrap antes que truncar: el título trae el mes y se pierde al cortar */
            overflow-wrap:anywhere;
        }
        .dark .tarea-card__title { color:#f1f5f9; }
        .tarea-card__razon {
            font-size:.83rem; line-height:1.5; margin:0; color:#475569;
            padding-left:.7rem; border-left:2px solid rgba(148,163,184,.3);
        }
        .dark .tarea-card__razon { color:#94a3b8; border-left-color:#334155; }
        .tarea-card__meta {
            display:grid; gap:.3rem; font-size:.8rem; color:#475569;
            padding-top:.55rem; border-top:1px dashed rgba(148,163,184,.3);
        }
        .dark .tarea-card__meta { color:#94a3b8; border-top-color:#334155; }
        .tarea-card__meta > div { display:flex; align-items:center; gap:.45rem; }
        .tarea-card__meta i { width:13px; text-align:center; opacity:.6; flex:0 0 auto; }
        .tarea-card__actions {
            display:flex; gap:.35rem; margin-top:auto;
            padding-top:.6rem; border-top:1px solid rgba(148,163,184,.18);
            justify-content:flex-end;
        }
        .dark .tarea-card__actions { border-top-color:rgba(51,65,85,.7); }
        /* 36px visuales, pero el ::after extiende el área táctil a 44px (regla de
           touch target) sin alterar el layout de la fila de acciones. */
        .tarea-btn {
            position:relative;
            width:36px; height:36px; border-radius:9px;
            border:1px solid rgba(148,163,184,.35); background:transparent;
            display:inline-flex; align-items:center; justify-content:center;
            color:#475569; font-size:.82rem; cursor:pointer;
            transition:background .15s ease-out, color .15s ease-out, border-color .15s ease-out, transform .1s ease-out;
        }
        .tarea-btn::after { content:''; position:absolute; inset:-4px; }
        .tarea-btn:hover { background:rgba(148,163,184,.14); color:#0f172a; }
        .tarea-btn:active { transform:scale(.94); }
        .tarea-btn:focus-visible { outline:2px solid #3b82f6; outline-offset:2px; }
        .dark .tarea-btn { color:#94a3b8; border-color:#334155; }
        .dark .tarea-btn:hover { background:rgba(148,163,184,.16); color:#f1f5f9; }
        /* Mientras Livewire procesa: sin hover ni click, y se nota que está ocupado */
        .tarea-btn:disabled { opacity:.55; cursor:progress; pointer-events:none; }
        .tarea-btn--ok { color:#10b981; border-color:rgba(16,185,129,.45); }
        .tarea-btn--ok:hover { background:rgba(16,185,129,.14); color:#059669; }
        .dark .tarea-btn--ok { color:#34d399; border-color:rgba(16,185,129,.4); }
        /* Resalta el botón de asignar cuando la tarea todavía no tiene dueño */
        .tarea-btn--alerta { color:#6366f1; border-color:rgba(99,102,241,.45); background:rgba(99,102,241,.08); }
        .tarea-btn--alerta:hover { background:rgba(99,102,241,.18); color:#4f46e5; }
        .dark .tarea-btn--alerta { color:#a5b4fc; border-color:rgba(99,102,241,.4); background:rgba(99,102,241,.12); }

        /* Nota informativa dentro del modal (por qué una métrica no se edita entera) */
        .tareas-nota {
            display:flex; gap:.6rem; align-items:flex-start;
            margin:0 0 1.1rem; padding:.75rem .9rem; border-radius:10px;
            background:rgba(99,102,241,.08); border:1px solid rgba(99,102,241,.25);
            color:#4338ca; font-size:.8rem; line-height:1.5;
        }
        .tareas-nota i { margin-top:.15rem; flex:0 0 auto; }
        .dark .tareas-nota { background:rgba(99,102,241,.12); border-color:rgba(99,102,241,.35); color:#c7d2fe; }
        /* Campos bloqueados en modo métrica */
        .tareas-modal__body .form-control[readonly] {
            background:rgba(148,163,184,.12); cursor:not-allowed; opacity:.75;
        }
        .dark .tareas-modal__body .form-control[readonly] { background:rgba(148,163,184,.08); }
        .tarea-badge { display:inline-flex; align-items:center; align-self:flex-start; gap:.25rem; border-radius:999px; padding:.1rem .5rem; font-size:.68rem; line-height:1.4; font-weight:600; white-space:nowrap; flex:0 0 auto; }
        .tarea-badge i { font-size:.62rem; }
        .tarea-badge--critica { background:#fee2e2; color:#b91c1c; }
        .tarea-badge--warn { background:#fef3c7; color:#b45309; }
        .tarea-badge--ok { background:#d1fae5; color:#047857; }
        .tarea-badge--pendiente { background:#dbeafe; color:#1d4ed8; }
        .tarea-badge--muted { background:#e2e8f0; color:#475569; }

        /* Sin responsable asignado: se atenúa para distinguirlo de una tarea con dueño */
        .tarea-card__sin-asignar { opacity:.6; font-style:italic; }

        .tareas-empty { text-align:center; padding:3rem 1rem; color:#64748b; }
        .tareas-empty i { font-size:2rem; margin-bottom:.5rem; display:block; }
        /* ── Modales ──────────────────────────────────────────────────────
           Entrada: backdrop en fade y caja con scale+translate (200ms ease-out).
           Solo transform/opacity, nada que provoque reflow.                   */
        @keyframes tareasBackdropIn { from { opacity:0; } to { opacity:1; } }
        @keyframes tareasModalIn {
            from { opacity:0; transform:translateY(10px) scale(.97); }
            to   { opacity:1; transform:translateY(0)    scale(1);   }
        }

        .tareas-modal-backdrop {
            position:fixed; inset:0; z-index:1050;
            background:rgba(15,23,42,.55);
            backdrop-filter:blur(2px);
            display:flex; align-items:center; justify-content:center; padding:1rem;
            animation:tareasBackdropIn .18s ease-out;
        }
        .tareas-modal {
            width:100%; max-width:520px;
            background:#fff; border-radius:16px; overflow:hidden;
            box-shadow:0 20px 50px rgba(0,0,0,.25);
            animation:tareasModalIn .2s cubic-bezier(.2,.8,.3,1);
        }
        .dark .tareas-modal { background:#101010; color:#f8fafc; box-shadow:0 20px 50px rgba(0,0,0,.6); }
        .tareas-modal--wide { max-width:680px; }

        .tareas-modal__head {
            display:flex; justify-content:space-between; align-items:center;
            gap:1rem; padding:1.1rem 1.25rem;
            border-bottom:1px solid rgba(148,163,184,.25);
        }
        .dark .tareas-modal__head { border-bottom-color:#334155; }
        .tareas-modal__head h3 { margin:0; font-size:1.05rem; font-weight:700; }
        .tareas-modal__close {
            border:none; background:transparent; font-size:1.5rem; line-height:1;
            opacity:.6; cursor:pointer; color:inherit;
            width:34px; height:34px; border-radius:8px; flex:0 0 auto;
            display:inline-flex; align-items:center; justify-content:center;
            transition:opacity .15s ease-out, background .15s ease-out;
        }
        .tareas-modal__close:hover { opacity:1; background:rgba(148,163,184,.16); }
        .tareas-modal__close:focus-visible { outline:2px solid #3b82f6; outline-offset:2px; }

        .tareas-modal__body { padding:1.25rem; max-height:70vh; overflow:auto; }
        .tareas-modal__body label {
            display:block; font-size:.8rem; font-weight:600;
            margin-bottom:.35rem; color:#334155;
        }
        .dark .tareas-modal__body label { color:#cbd5e1; }
        /* 44px de alto: entra cómodo al tacto y evita el auto-zoom de iOS */
        .tareas-modal__body .form-control { min-height:44px; font-size:.9rem; }
        .tareas-modal__body textarea.form-control { min-height:auto; }

        .tareas-modal__foot {
            display:flex; justify-content:flex-end; gap:.5rem;
            margin-top:1.25rem; padding-top:1rem;
            border-top:1px solid rgba(148,163,184,.2);
        }
        .dark .tareas-modal__foot { border-top-color:#334155; }

        /* Salida: más corta que la entrada (140 vs 200ms) para que se sienta ágil */
        @keyframes tareasBackdropOut { from { opacity:1; } to { opacity:0; } }
        @keyframes tareasModalOut {
            from { opacity:1; transform:translateY(0)   scale(1);   }
            to   { opacity:0; transform:translateY(6px) scale(.98); }
        }
        .tareas-modal-backdrop.is-closing { animation:tareasBackdropOut .14s ease-in forwards; }
        .tareas-modal-backdrop.is-closing .tareas-modal { animation:tareasModalOut .14s ease-in forwards; }

        /* Accesibilidad: si el sistema pide menos movimiento, se quita la animación */
        @media (prefers-reduced-motion: reduce) {
            .tareas-modal-backdrop, .tareas-modal,
            .tareas-modal-backdrop.is-closing, .tareas-modal-backdrop.is-closing .tareas-modal { animation:none; }
            .tarea-card, .tarea-btn { transition:none; }
            .tarea-card:hover { transform:none; }
        }
        .tareas-timeline { position:relative; padding-left:1rem; }
        .tareas-timeline__item { position:relative; padding-left:1.25rem; padding-bottom:1rem; border-left:2px solid rgba(148,163,184,.35); }
        .tareas-timeline__item:last-child { border-left-color:transparent; padding-bottom:0; }
        .tareas-timeline__dot { position:absolute; left:-6px; top:.2rem; width:10px; height:10px; border-radius:50%; background:#2563eb; }
        .tareas-timeline__title { font-weight:600; margin-bottom:.25rem; display:flex; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
        .tareas-vista-toggle { display:flex; gap:2px; padding:2px; border-radius:10px; background:rgba(148,163,184,.15); }
        .tareas-vista-btn { border:none; background:transparent; border-radius:8px; padding:.4rem .75rem; font-size:.8rem; opacity:.75; transition:.15s; }
        .tareas-vista-btn.is-active { background:#fff; opacity:1; box-shadow:0 1px 3px rgba(0,0,0,.12); font-weight:600; }
        .dark .tareas-vista-btn.is-active { background:#1e293b; color:#f8fafc; }
        .tareas-cal-wrap { }
        .tareas-cal-nav__title { font-size:1.1rem; display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; }
        .tareas-cal-legend { display:flex; flex-wrap:wrap; gap:1rem; font-size:.78rem; opacity:.85; }
        .tareas-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:.25rem; vertical-align:middle; }
        .tareas-dot--hoy { background:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.35); }
        .tareas-dot--seleccionado { background:#f59e0b; box-shadow:0 0 0 2px rgba(245,158,11,.35); }
        .tareas-dot--evento { background:#6366f1; }
        .tareas-dot--metrica { background:#0ea5e9; }
        .tareas-dot--programada { background:#94a3b8; border:1px dashed #64748b; }
        .tareas-dot--critica { background:#ef4444; }
        .tareas-cal-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:1px; background:rgba(148,163,184,.35); border:1px solid rgba(148,163,184,.35); border-radius:12px; overflow:hidden; }
        .tareas-cal-head { background:#f8fafc; padding:.45rem; text-align:center; font-size:.72rem; font-weight:700; text-transform:uppercase; color:#64748b; }
        .dark .tareas-cal-head { background:#1e293b; color:#94a3b8; }
        .tareas-cal-cell { background:#fff; min-height:96px; padding:.35rem; display:flex; flex-direction:column; gap:.25rem; border:none; text-align:left; width:100%; cursor:pointer; transition:background .12s; }
        .dark .tareas-cal-cell { background:#0f172a; }
        .tareas-cal-cell:hover { background:rgba(37,99,235,.06); }
        .tareas-cal-cell.is-outside { opacity:.45; }
        .tareas-cal-cell.is-hoy { background:linear-gradient(180deg,rgba(37,99,235,.12),transparent); }
        .tareas-cal-cell.is-selected { box-shadow:inset 0 0 0 2px #f59e0b; background:linear-gradient(180deg,rgba(245,158,11,.1),transparent); }
        .tareas-cal-cell.is-hoy.is-selected { box-shadow:inset 0 0 0 2px #2563eb, inset 0 0 0 4px rgba(245,158,11,.5); }
        .tareas-cal-cell__day { font-size:.78rem; font-weight:700; opacity:.8; }
        .tareas-cal-cell.is-hoy .tareas-cal-cell__day { color:#2563eb; }
        .tareas-cal-cell.is-selected .tareas-cal-cell__day { color:#d97706; }
        .tareas-cal-cell__items { display:flex; flex-direction:column; gap:2px; }
        .tareas-cal-item { border:none; border-radius:6px; padding:2px 5px; font-size:.68rem; text-align:left; line-height:1.25; cursor:pointer; }
        .tareas-cal-item.is-evento { background:#e0e7ff; color:#3730a3; }
        .tareas-cal-item.is-metrica { background:#e0f2fe; color:#0369a1; }
        .tareas-cal-item.is-programada { background:#f1f5f9; color:#64748b; border:1px dashed #cbd5e1; cursor:default; }
        .tareas-cal-item.is-critica { background:#fee2e2; color:#b91c1c; font-weight:700; }
        .tareas-cal-item.is-done { opacity:.55; text-decoration:line-through; }
        .tareas-dia-panel { border:1px solid rgba(245,158,11,.35); border-radius:14px; padding:1rem; background:rgba(245,158,11,.06); }
        .tareas-dia-panel__title { margin:0 0 .75rem; font-size:1rem; font-weight:700; }
        .tareas-dia-list { display:grid; gap:.5rem; }
        .tareas-dia-item { display:flex; justify-content:space-between; align-items:center; gap:.75rem; padding:.65rem .75rem; border-radius:10px; background:#fff; border:1px solid rgba(148,163,184,.25); }
        .dark .tareas-dia-item { background:#101010; }
        .tareas-dia-item.is-critica { border-color:#ef4444; background:rgba(239,68,68,.08); }
        .tareas-dia-item.is-programada { border-style:dashed; opacity:.85; }
        .tareas-dia-item.is-done { opacity:.6; }
    </style>

    @include('partials.tareas-modal-js')
</div>
