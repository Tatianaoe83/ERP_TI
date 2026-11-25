<div class="tareas-module" wire:poll.60s>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <button type="button" wire:click="filtrarKpi('hoy')" class="tareas-kpi tareas-kpi--hoy {{ $filtroEstatus === 'hoy' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">Pendientes de hoy</span>
            <span class="tareas-kpi__value">{{ $kpis['hoy'] }}</span>
        </button>
        <button type="button" wire:click="filtrarKpi('sinfecha')" class="tareas-kpi tareas-kpi--muted {{ $filtroEstatus === 'sinfecha' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">Sin fecha asignada</span>
            <span class="tareas-kpi__value">{{ $kpis['sinfecha'] }}</span>
        </button>
        <button type="button" wire:click="filtrarKpi('atrasadas')" class="tareas-kpi tareas-kpi--warn {{ $filtroEstatus === 'atrasadas' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">No realizadas</span>
            <span class="tareas-kpi__value">{{ $kpis['atrasadas'] }}</span>
        </button>
        <button type="button" wire:click="filtrarKpi('completadas')" class="tareas-kpi tareas-kpi--ok {{ $filtroEstatus === 'completadas' ? 'is-active' : '' }}">
            <span class="tareas-kpi__label">Completadas ({{ $tituloMes }})</span>
            <span class="tareas-kpi__value">{{ $kpis['completadas_mes'] }}</span>
        </button>
    </div>

    <div class="index-page__card overflow-hidden tareas-board">
        {{-- Nivel 1: sección principal (Tareas vs Métricas) --}}
        <div class="tareas-section-bar">
            <div class="tareas-section-bar__tabs">
                <span class="tareas-section-title">
                    <i class="fas fa-tasks"></i>
                    {{ $tituloSeccion }}
                </span>
            </div>
            <div class="tareas-section-bar__actions">
                @can('tickets.gestionar-tareas')
                <button type="button" wire:click="abrirModalNuevaTarea" class="index-page__btn-primary">
                    <i class="fas fa-plus"></i> Nueva tarea
                </button>
                @endcan
            </div>
        </div>

        <div class="tareas-cal-wrap">
            <div class="tareas-cal-toolbar">
                <div class="tareas-cal-toolbar__nav">
                    <button type="button" wire:click="mesAnterior" class="tarea-btn" title="Mes anterior"><i class="fas fa-chevron-left"></i></button>
                    <div class="tareas-cal-nav__title">
                        <strong>{{ $tituloMes }}</strong>
                        @if($fechaSel === $hoy)
                        <span class="tarea-badge tarea-badge--pendiente ml-2">Hoy</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="irHoy" class="index-page__btn-secondary">Ir a hoy</button>
                        <button type="button" wire:click="mesSiguiente" class="tarea-btn" title="Mes siguiente"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="tareas-view-bar__filters">
                    <select wire:model.live="filtroTipo" class="form-control tareas-select">
                        <option value="">Todos los tipos</option>
                        <option value="evento">Eventos</option>
                        <option value="metrica">Métricas</option>
                    </select>
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control tareas-search" placeholder="Buscar...">
                </div>
            </div>

            <div class="tareas-cal-legend mb-3">
                <span><i class="tareas-dot tareas-dot--hoy"></i> Hoy</span>
                <span><i class="tareas-dot tareas-dot--seleccionado"></i> Seleccionado</span>
                <span><i class="tareas-dot tareas-dot--pendiente"></i> Pendiente</span>
                <span><i class="tareas-dot tareas-dot--vencida"></i> No realizada</span>
                <span><i class="tareas-dot tareas-dot--critica"></i> Crítica</span>
                <span><i class="tareas-dot tareas-dot--hecha"></i> Completada</span>
            </div>

            <div class="tareas-cal-layout">
                <div class="tareas-cal-grid" role="grid" aria-label="Calendario de tareas">
                    @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $dh)
                    <div class="tareas-cal-head">{{ $dh }}</div>
                    @endforeach

                    @foreach($calendario as $semana)
                        @foreach($semana as $celda)
                        <button type="button"
                            wire:click="seleccionarDia('{{ $celda['fecha_str'] }}')"
                            class="tareas-cal-cell {{ !$celda['mes_actual'] ? 'is-outside' : '' }} {{ $celda['es_hoy'] ? 'is-hoy' : '' }} {{ $celda['es_seleccionado'] ? 'is-selected' : '' }} has-{{ $celda['alerta'] }}"
                            title="{{ $celda['fecha']->format('d/m/Y') }} · {{ $celda['total'] }} tarea(s)">
                            <div class="tareas-cal-cell__day">{{ $celda['dia'] }}</div>
                            @if($celda['total'] > 0)
                            <div class="tareas-cal-marks">
                                @if($celda['conteo']['is-critica'] > 0)
                                <i class="tareas-dot tareas-dot--critica" title="{{ $celda['conteo']['is-critica'] }} crítica(s)"></i>
                                @endif
                                @if($celda['conteo']['is-vencida'] > 0)
                                <i class="tareas-dot tareas-dot--vencida" title="{{ $celda['conteo']['is-vencida'] }} no realizada(s)"></i>
                                @endif
                                @if(($celda['conteo']['is-pendiente'] + $celda['conteo']['is-metrica']) > 0)
                                <i class="tareas-dot tareas-dot--pendiente" title="{{ $celda['conteo']['is-pendiente'] + $celda['conteo']['is-metrica'] }} pendiente(s)"></i>
                                @endif
                                @if($celda['conteo']['is-done'] > 0)
                                <i class="tareas-dot tareas-dot--hecha" title="{{ $celda['conteo']['is-done'] }} completada(s)"></i>
                                @endif
                                <span class="tareas-cal-count">{{ $celda['total'] }}</span>
                            </div>
                            @endif
                        </button>
                        @endforeach
                    @endforeach
                </div>

                <aside class="tareas-dia-panel tareas-dia-panel--side">
                    <div class="tareas-dia-panel__head">
                        <h4 class="tareas-dia-panel__title">
                            <i class="fas fa-list"></i>
                            {{ $tituloLista }}
                        </h4>
                        @if($soloDia)
                        <button type="button" wire:click="verTodas" class="index-page__btn-secondary">Ver todas</button>
                        @endif
                    </div>
                    @if($listaTareas->isEmpty())
                    <p class="text-sm text-slate-500 mb-0">No hay tareas en este filtro. Elija otra etiqueta o un día del calendario.</p>
                    @else
                    <div class="tareas-dia-list">
                        @foreach($listaTareas as $tarea)
                        <div class="tareas-dia-item {{ $tarea->claseSemaforo() }}" wire:key="lista-tarea-{{ $tarea->id }}">
                            <div>
                                <button type="button" class="tareas-dia-item__titulo" wire:click="abrirDetalle({{ $tarea->id }})" title="Ver detalle e historial">{{ $tarea->titulo }}</button>
                                <span class="tarea-badge
                                    @if($tarea->claseSemaforo() === 'is-critica') tarea-badge--critica
                                    @elseif($tarea->claseSemaforo() === 'is-vencida') tarea-badge--warn
                                    @elseif($tarea->claseSemaforo() === 'is-done') tarea-badge--ok
                                    @else tarea-badge--pendiente
                                    @endif">{{ $tarea->etiquetaPrioridad() }}</span>
                                <div class="text-xs opacity-75">
                                    {{ $tarea->tipo === 'metrica' ? 'Métrica' : 'Evento' }}
                                    · {{ $tarea->etiquetaResponsables($nombresResponsables ?? null) }}
                                    @if($tarea->fecha_compromiso)
                                        · {{ $tarea->fecha_compromiso->format('d/m/Y') }}
                                    @else
                                        · Sin fecha
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1 shrink-0">
                                <button type="button" wire:click.stop="abrirDetalle({{ $tarea->id }})" class="tarea-btn" title="Ver detalle e historial">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @can('tickets.gestionar-tareas')
                                @if($tarea->estatus === 'pendiente')
                                <button type="button" wire:click.stop="abrirEditarTexto({{ $tarea->id }})" class="tarea-btn" title="Editar título y descripción">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" wire:click.stop="abrirReagendar({{ $tarea->id }})" class="tarea-btn" title="Reagendar">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                                <button type="button" wire:click.stop="completarTarea({{ $tarea->id }})"
                                        wire:target="completarTarea({{ $tarea->id }})"
                                        wire:loading.attr="disabled"
                                        class="tarea-btn tarea-btn--ok" title="Completar">
                                    <i class="fas fa-check" wire:loading.remove wire:target="completarTarea({{ $tarea->id }})"></i>
                                    <i class="fas fa-spinner fa-spin" wire:loading wire:target="completarTarea({{ $tarea->id }})"></i>
                                </button>
                                @endif
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($listaTareas->total() > $listaTareas->perPage())
                    <div class="tareas-pager">
                        <span class="tareas-pager__info">{{ $listaTareas->firstItem() }}–{{ $listaTareas->lastItem() }} de {{ $listaTareas->total() }}</span>
                        <div class="tareas-pager__nav">
                            <button type="button" class="tarea-btn" wire:click="previousPage" @if($listaTareas->onFirstPage()) disabled @endif title="Anterior">‹</button>
                            <span class="tareas-pager__page">{{ $listaTareas->currentPage() }}/{{ $listaTareas->lastPage() }}</span>
                            <button type="button" class="tarea-btn" wire:click="nextPage" @if(! $listaTareas->hasMorePages()) disabled @endif title="Siguiente">›</button>
                        </div>
                    </div>
                    @endif
                    @endif
                </aside>
            </div>
        </div>
    </div>

    {{-- Modal tarea --}}
    @if($modalTareaAbierto)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalTareaAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()"
         @tareas-modal-cerrar.window="cerrar()">
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
                @if($tareaEditId && $creadorTarea)
                @can('tickets.ver-creador-tarea')
                <p class="tareas-nota">
                    <i class="fas fa-user-edit"></i>
                    Creada por <strong>{{ $creadorTarea }}</strong>
                </p>
                @endcan
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
                <div class="form-group mb-3">
                    <label>Asignar a (personal TI activo)</label>
                    <p class="tareas-asignados__hint">
                        @if($editandoMetrica)
                            Las métricas solo admiten un responsable.
                        @else
                            Puede marcar a más de una persona. Quedan en la misma tarea.
                        @endif
                    </p>
                    <div class="tareas-asignados" role="group" aria-label="Responsables de TI">
                        @foreach($responsables as $emp)
                        <label class="tareas-asignados__item">
                            <input type="checkbox"
                                   wire:model.defer="asignados_ids"
                                   value="{{ $emp->EmpleadoID }}">
                            <span>{{ $emp->NombreEmpleado }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('asignados_ids') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @error('asignados_ids.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="form-group mb-3">
                    <label>Fecha compromiso <span class="text-xs opacity-60 font-normal">(opcional)</span></label>
                    <input type="date" wire:model.defer="fecha_compromiso" class="form-control"
                           @if($editandoMetrica) readonly @endif>
                    @error('fecha_compromiso') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
         @keydown.escape.window="cerrar()"
         @tareas-modal-cerrar.window="cerrar()">
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

    {{-- Detalle de la tarea --}}
    @if($modalDetalleAbierto && $tareaDetalle)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalDetalleAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()"
         @tareas-modal-cerrar.window="cerrar()">
        <div class="tareas-modal tareas-modal--wide">
            <div class="tareas-modal__head">
                <h3>Detalle de la tarea</h3>
                <button type="button" @click="cerrar()" class="tareas-modal__close" aria-label="Cerrar">&times;</button>
            </div>
            <div class="tareas-modal__body">
                <dl class="tareas-detalle">
                    <div class="tareas-detalle__row">
                        <dt>Título</dt>
                        <dd>{{ $tareaDetalle->titulo }}</dd>
                    </div>
                    <div class="tareas-detalle__row">
                        <dt>Descripción</dt>
                        <dd>{{ $tareaDetalle->razon ?: 'Sin descripción' }}</dd>
                    </div>
                    <div class="tareas-detalle__row tareas-detalle__row--inline">
                        <div>
                            <dt>Tipo</dt>
                            <dd>{{ $tareaDetalle->tipo === 'metrica' ? 'Métrica' : 'Evento' }}</dd>
                        </div>
                        <div>
                            <dt>Estado</dt>
                            <dd>{{ $tareaDetalle->etiquetaPrioridad() }}</dd>
                        </div>
                    </div>
                    <div class="tareas-detalle__row">
                        <dt>Responsables</dt>
                        <dd>{{ $tareaDetalle->etiquetaResponsables($nombresResponsables ?? null) }}</dd>
                    </div>
                    <div class="tareas-detalle__row tareas-detalle__row--inline">
                        <div>
                            <dt>Fecha compromiso</dt>
                            <dd>{{ $tareaDetalle->fecha_compromiso ? $tareaDetalle->fecha_compromiso->format('d/m/Y') : 'Sin fecha' }}</dd>
                        </div>
                        @if($tareaDetalle->completada_at)
                        <div>
                            <dt>Completada</dt>
                            <dd>{{ $tareaDetalle->completada_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        @endif
                    </div>
                    @can('tickets.ver-creador-tarea')
                    <div class="tareas-detalle__row">
                        <dt>Creada por</dt>
                        <dd>{{ $creadorDetalle }}</dd>
                    </div>
                    @endcan
                </dl>
                <h4 class="tareas-detalle__historial">Historial</h4>
                <div class="tareas-timeline">
                    @forelse($tareaDetalle->historial as $item)
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
                <div class="tareas-modal__foot">
                    <button type="button" @click="cerrar()" class="index-page__btn-secondary">Cerrar</button>
                    @can('tickets.gestionar-tareas')
                    @if($tareaDetalle->estatus === 'pendiente')
                    <button type="button" wire:click="abrirEditarTexto({{ $tareaDetalle->id }})" class="index-page__btn-primary">
                        Editar título y descripción
                    </button>
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Editar título y descripción --}}
    @if($modalTextoAbierto)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalTextoAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()"
         @tareas-modal-cerrar.window="cerrar()">
        <div class="tareas-modal">
            <div class="tareas-modal__head">
                <h3>Editar título y descripción</h3>
                <button type="button" @click="cerrar()" class="tareas-modal__close" aria-label="Cerrar">&times;</button>
            </div>
            <form wire:submit.prevent="guardarTextoTarea" class="tareas-modal__body">
                <div class="form-group mb-3">
                    <label>Título</label>
                    <input type="text" wire:model.defer="titulo" class="form-control" required maxlength="200">
                    @error('titulo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="form-group mb-3">
                    <label>Descripción</label>
                    <textarea wire:model.defer="razon" class="form-control" rows="4" maxlength="2000" placeholder="¿Para qué es esta tarea?"></textarea>
                    @error('razon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="tareas-modal__foot">
                    <button type="button" @click="cerrar()" class="index-page__btn-secondary">Cancelar</button>
                    <button type="submit" class="index-page__btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <style>
        .tareas-kpi { text-align:left; border:1px solid rgba(148,163,184,.35); border-bottom-width:3px; border-radius:14px; padding:.85rem 1rem; background:#fff; transition:.15s; }
        .dark .tareas-kpi { background:#101010; border-top-color:#334155; border-left-color:#334155; border-right-color:#334155; }
        .tareas-kpi--hoy { border-bottom-color:#60a5fa; }
        .tareas-kpi--hoy .tareas-kpi__value { color:#2563eb; }
        .tareas-kpi--hoy.is-active { border-color:#60a5fa; box-shadow:0 0 0 2px rgba(96,165,250,.28); }
        .tareas-kpi--warn { border-bottom-color:#f59e0b; }
        .tareas-kpi--warn .tareas-kpi__value { color:#f59e0b; }
        .tareas-kpi--warn.is-active { border-color:#f59e0b; box-shadow:0 0 0 2px rgba(245,158,11,.28); }
        .tareas-kpi--muted { border-bottom-color:#94a3b8; }
        .tareas-kpi--muted .tareas-kpi__value { color:#64748b; }
        .dark .tareas-kpi--muted .tareas-kpi__value { color:#94a3b8; }
        .tareas-kpi--muted.is-active { border-color:#94a3b8; box-shadow:0 0 0 2px rgba(148,163,184,.3); }
        .tareas-kpi--ok { border-bottom-color:#22c55e; }
        .tareas-kpi--ok .tareas-kpi__value { color:#16a34a; }
        .tareas-kpi--ok.is-active { border-color:#22c55e; box-shadow:0 0 0 2px rgba(34,197,94,.28); }
        .tareas-kpi__label { display:block; font-size:.75rem; opacity:.75; }
        .tareas-kpi__value { display:block; font-size:1.5rem; font-weight:700; line-height:1.1; }
        .tareas-section-bar { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-bottom:2px solid rgba(148,163,184,.2); background:rgba(248,250,252,.6); flex:0 0 auto; }
        .dark .tareas-section-bar { background:rgba(15,23,42,.5); border-bottom-color:#334155; }
        .tareas-section-bar__tabs { display:flex; gap:.35rem; align-items:center; }
        .tareas-section-title { font-size:.95rem; font-weight:700; }
        .tareas-section-bar__actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
        .tareas-cal-toolbar { display:flex; flex-wrap:wrap; gap:1rem; justify-content:space-between; align-items:center; margin-bottom:1rem; padding:.75rem 1rem; border-radius:12px; background:rgba(148,163,184,.08); border:1px solid rgba(148,163,184,.2); flex:0 0 auto; }
        .dark .tareas-cal-toolbar { background:rgba(30,41,59,.5); border-color:#334155; }
        .tareas-cal-toolbar__nav { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }

        .tareas-view-bar { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; align-items:center; padding:.75rem 1.25rem; border-bottom:1px solid rgba(148,163,184,.2); background:rgba(148,163,184,.05); }
        .dark .tareas-view-bar { background:rgba(30,41,59,.35); }
        .tareas-view-bar__filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; flex:1 1 auto; }
        /* Bootstrap pone .form-control en display:block y width:100%, y dentro del
           flex eso hacía que cada filtro ocupara su propia fila (se veían apilados). */
        .tareas-view-bar__filters .form-control { width:auto; display:inline-block; }
        .tareas-select { flex:0 0 auto; width:auto; min-width:170px; }
        .tareas-search { flex:1 1 240px; width:auto; min-width:200px; max-width:none; }

        @media (max-width: 640px) {
            .tareas-view-bar { flex-direction:column; align-items:stretch; }
            .tareas-view-bar__filters .form-control { width:100%; }
            .tareas-select, .tareas-search { flex:1 1 100%; max-width:none; }
        }

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

        /* ── Modales ──────────────────────────────────────────────────────
           Entrada tipo resorte: la caja sube, se pasa apenas (-3px / 1.008) y se
           asienta; el fondo se oscurece y difumina a la par, y el contenido entra
           escalonado detrás de la caja. Solo transform/opacity/filter: sin reflow. */
        @keyframes tareasBackdropIn {
            from { opacity:0; backdrop-filter:blur(0); }
            to   { opacity:1; backdrop-filter:blur(4px); }
        }
        @keyframes tareasModalIn {
            0%   { opacity:0; transform:translateY(28px) scale(.94); }
            60%  { opacity:1; transform:translateY(-3px) scale(1.008); }
            100% { opacity:1; transform:translateY(0) scale(1); }
        }
        @keyframes tareasModalContentIn {
            from { opacity:0; transform:translateY(8px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .tareas-modal-backdrop {
            position:fixed; inset:0; z-index:1050;
            background:rgba(15,23,42,.55);
            backdrop-filter:blur(4px);
            display:flex; align-items:center; justify-content:center; padding:1rem;
            animation:tareasBackdropIn .3s ease-out both;
        }
        .tareas-modal {
            width:100%; max-width:520px;
            background:#fff; border-radius:16px; overflow:hidden;
            box-shadow:0 20px 50px rgba(0,0,0,.25);
            animation:tareasModalIn .45s cubic-bezier(.22,1,.36,1) both;
        }
        .tareas-modal__head { animation:tareasModalContentIn .35s .08s cubic-bezier(.22,1,.36,1) both; }
        .tareas-modal__body { animation:tareasModalContentIn .35s .14s cubic-bezier(.22,1,.36,1) both; }
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

        .tareas-asignados__hint {
            margin:0 0 .5rem; font-size:.75rem; font-weight:400; line-height:1.4; color:#64748b;
        }
        .dark .tareas-asignados__hint { color:#94a3b8; }
        .tareas-asignados {
            display:flex; flex-direction:column; gap:.15rem;
            max-height:11.5rem; overflow:auto;
            padding:.35rem;
            border:1px solid rgba(148,163,184,.35); border-radius:10px;
            background:rgba(148,163,184,.06);
        }
        .dark .tareas-asignados { border-color:#334155; background:rgba(15,23,42,.45); }
        .tareas-asignados__item {
            display:flex !important; align-items:center; gap:.65rem;
            margin:0 !important; padding:.45rem .65rem; border-radius:8px;
            font-size:.82rem !important; font-weight:500 !important;
            cursor:pointer; color:#1e293b !important;
        }
        .dark .tareas-asignados__item { color:#e2e8f0 !important; }
        .tareas-asignados__item:hover { background:rgba(99,102,241,.1); }
        .tareas-asignados__item input {
            width:1rem; height:1rem; margin:0; accent-color:#1d4ed8; flex:0 0 auto;
        }

        .tareas-modal__foot {
            display:flex; justify-content:flex-end; gap:.5rem;
            margin-top:1.25rem; padding-top:1rem;
            border-top:1px solid rgba(148,163,184,.2);
        }
        .dark .tareas-modal__foot { border-top-color:#334155; }

        /* Salida: más corta que la entrada (220 vs 450ms) y acelerando, como algo que
           se suelta y cae. Debe durar menos que la espera de tareasModalCerrable (240ms). */
        @keyframes tareasBackdropOut {
            from { opacity:1; backdrop-filter:blur(4px); }
            to   { opacity:0; backdrop-filter:blur(0); }
        }
        @keyframes tareasModalOut {
            from { opacity:1; transform:translateY(0) scale(1); }
            to   { opacity:0; transform:translateY(16px) scale(.95); }
        }
        .tareas-modal-backdrop.is-closing { animation:tareasBackdropOut .22s ease-in forwards; pointer-events:none; }
        .tareas-modal-backdrop.is-closing .tareas-modal { animation:tareasModalOut .2s cubic-bezier(.4,0,1,1) forwards; }

        /* Accesibilidad: si el sistema pide menos movimiento, se quita la animación */
        /* Movimiento reducido (p. ej. Windows con "Efectos de animación" apagado): sin
           desplazamiento ni rebote, pero con un fundido corto para que abrir y cerrar
           no se sienta brusco ni se quede el modal congelado esperando al servidor. */
        @keyframes tareasFadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes tareasFadeOut { from { opacity:1; } to { opacity:0; } }
        @media (prefers-reduced-motion: reduce) {
            .tareas-modal-backdrop, .tareas-modal { animation:tareasFadeIn .18s ease-out both; }
            .tareas-modal__head, .tareas-modal__body { animation:none; }
            .tareas-modal-backdrop.is-closing,
            .tareas-modal-backdrop.is-closing .tareas-modal { animation:tareasFadeOut .14s ease-in forwards; }
            .tarea-btn { transition:none; }
        }
        .tareas-timeline { position:relative; padding-left:1rem; }
        .tareas-timeline__item { position:relative; padding-left:1.25rem; padding-bottom:1rem; border-left:2px solid rgba(148,163,184,.35); }
        .tareas-timeline__item:last-child { border-left-color:transparent; padding-bottom:0; }
        .tareas-timeline__dot { position:absolute; left:-6px; top:.2rem; width:10px; height:10px; border-radius:50%; background:#2563eb; }
        .tareas-timeline__title { font-weight:600; margin-bottom:.25rem; display:flex; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
        .tareas-module {
            width:100%;
            display:flex;
            flex-direction:column;
            min-height:calc(100dvh - 210px);
        }
        .tareas-board {
            flex:1 1 auto;
            display:flex;
            flex-direction:column;
            min-height:0;
        }
        .tareas-cal-wrap {
            flex:1 1 auto;
            display:flex;
            flex-direction:column;
            min-height:0;
            padding:1.15rem 1.25rem 1.25rem;
        }
        .tareas-cal-nav__title { font-size:1.1rem; display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; }
        .tareas-cal-legend { display:flex; flex-wrap:wrap; gap:1rem; font-size:.78rem; opacity:.85; flex:0 0 auto; }
        .tareas-dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:.25rem; vertical-align:middle; }
        .tareas-dot--hoy { background:#38bdf8; box-shadow:0 0 0 2px rgba(56,189,248,.35); }
        .tareas-dot--seleccionado { background:#c084fc; box-shadow:0 0 0 2px rgba(192,132,252,.35); }
        .tareas-dot--pendiente { background:#60a5fa; box-shadow:0 0 0 2px rgba(96,165,250,.45); }
        .tareas-dot--vencida { background:#f59e0b; box-shadow:0 0 0 2px rgba(245,158,11,.4); }
        .tareas-dot--critica { background:#ef4444; box-shadow:0 0 0 2px rgba(239,68,68,.4); }
        .tareas-dot--hecha { background:#22c55e; box-shadow:0 0 0 2px rgba(34,197,94,.35); }
        .tareas-cal-layout {
            flex:1 1 auto;
            display:grid;
            grid-template-columns:minmax(0,1.7fr) minmax(340px, 1fr);
            gap:1.25rem;
            align-items:stretch;
            min-height:28rem;
        }
        .tareas-cal-grid {
            display:grid;
            grid-template-columns:repeat(7,minmax(0,1fr));
            grid-template-rows:auto;
            grid-auto-rows:minmax(4.75rem, 1fr);
            gap:1px;
            background:rgba(148,163,184,.35);
            border:1px solid rgba(148,163,184,.35);
            border-radius:12px;
            overflow:hidden;
            height:100%;
            min-height:28rem;
        }
        .tareas-cal-head { background:#f8fafc; padding:.55rem .45rem; text-align:center; font-size:.72rem; font-weight:700; text-transform:uppercase; color:#64748b; }
        .dark .tareas-cal-head { background:#1e293b; color:#94a3b8; }
        .tareas-cal-cell {
            background:#fff; min-height:4.75rem; height:100%; padding:.5rem .45rem .4rem;
            display:flex; flex-direction:column; align-items:flex-start; justify-content:space-between;
            gap:.35rem; border:none; text-align:left; width:100%; cursor:pointer;
            transition:background .12s, box-shadow .12s;
        }
        .dark .tareas-cal-cell { background:#0f172a; }
        .tareas-cal-cell.has-pendiente { background:#eff6ff; }
        .tareas-cal-cell.has-vencida { background:#fffbeb; }
        .tareas-cal-cell.has-critica { background:#fef2f2; }
        .tareas-cal-cell.has-done { background:#f0fdf4; }
        .dark .tareas-cal-cell.has-pendiente { background:rgba(96,165,250,.16); }
        .dark .tareas-cal-cell.has-vencida { background:rgba(245,158,11,.14); }
        .dark .tareas-cal-cell.has-critica { background:rgba(239,68,68,.16); }
        .dark .tareas-cal-cell.has-done { background:rgba(34,197,94,.14); }
        .tareas-cal-cell:hover { filter:brightness(.97); }
        .tareas-cal-cell.is-outside { opacity:.4; }
        .tareas-cal-cell.is-hoy { background:#f0f9ff; }
        .dark .tareas-cal-cell.is-hoy { background:rgba(56,189,248,.14); }
        .tareas-cal-cell.is-selected { box-shadow:inset 0 0 0 2px #8b5cf6; background:#faf5ff; }
        .dark .tareas-cal-cell.is-selected { background:rgba(192,132,252,.14); }
        .tareas-cal-cell.is-hoy.is-selected { box-shadow:inset 0 0 0 2px #0ea5e9; background:#e0f2fe; }
        .tareas-cal-cell__day { font-size:.85rem; font-weight:700; color:#334155; }
        .dark .tareas-cal-cell__day { color:#e2e8f0; }
        .tareas-cal-cell.is-hoy .tareas-cal-cell__day { color:#0369a1; }
        .tareas-cal-cell.is-selected .tareas-cal-cell__day { color:#6d28d9; }
        .dark .tareas-cal-cell.is-hoy .tareas-cal-cell__day { color:#38bdf8; }
        .dark .tareas-cal-cell.is-selected .tareas-cal-cell__day { color:#d8b4fe; }
        .tareas-cal-marks { display:flex; flex-wrap:wrap; align-items:center; gap:4px; }
        .tareas-cal-count {
            margin-left:auto; font-size:.65rem; font-weight:700; color:#64748b;
            min-width:1.1rem; text-align:right;
        }
        .dark .tareas-cal-count { color:#94a3b8; }
        .tareas-dia-panel { border:1px solid #e2e8f0; border-radius:14px; padding:1rem; background:#fff; }
        .dark .tareas-dia-panel { background:#0f172a; border-color:#334155; }
        .tareas-dia-panel--side { min-height:100%; max-height:none; height:100%; overflow:hidden; display:flex; flex-direction:column; }
        .tareas-dia-panel__head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; margin-bottom:.5rem; flex:0 0 auto; }
        .tareas-dia-panel__title { margin:0; font-size:.95rem; font-weight:700; display:flex; align-items:center; gap:.45rem; }
        /* align-content:start: sin él la fila única del grid se estira a todo el panel. */
        .tareas-dia-list { display:grid; align-content:start; gap:.5rem; flex:1 1 auto; overflow:auto; min-height:0; }
        .tareas-dia-item { display:flex; justify-content:space-between; align-items:center; gap:.75rem; padding:.65rem .75rem; border-radius:10px; background:#fff; border:1px solid #e2e8f0; border-left-width:4px; }
        .tareas-dia-item > div:first-child { min-width:0; flex:1; }
        .tareas-dia-item__titulo {
            display:inline; margin:0 .4rem 0 0; padding:0; border:0; background:transparent;
            font:inherit; font-weight:700; color:inherit; text-align:left; cursor:pointer;
        }
        .tareas-dia-item__titulo:hover { text-decoration:underline; }
        .tareas-detalle { margin:0; display:grid; gap:.9rem; }
        .tareas-detalle__row dt {
            margin:0 0 .2rem; font-size:.72rem; font-weight:700; letter-spacing:.04em;
            text-transform:uppercase; color:#64748b;
        }
        .tareas-detalle__row dd { margin:0; font-size:.92rem; line-height:1.45; white-space:pre-wrap; overflow-wrap:anywhere; }
        .tareas-detalle__row--inline { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
        .tareas-detalle__historial { margin:1.15rem 0 .75rem; font-size:.95rem; font-weight:700; }
        .dark .tareas-detalle__row dt { color:#94a3b8; }
        .dark .tareas-dia-item { background:#101010; border-color:#334155; }
        .tareas-dia-item.is-pendiente { border-color:#bfdbfe; border-left-color:#3b82f6; background:#eff6ff; }
        .dark .tareas-dia-item.is-pendiente { border-color:#93c5fd; background:rgba(96,165,250,.16); }
        .tareas-dia-item.is-metrica { border-color:#99f6e4; border-left-color:#14b8a6; background:#f0fdfa; }
        .tareas-dia-item.is-critica { border-color:#fecaca; border-left-color:#ef4444; background:#fef2f2; }
        .tareas-dia-item.is-vencida { border-color:#fde68a; border-left-color:#f59e0b; background:#fffbeb; }
        .tareas-dia-item.is-done { border-color:#bbf7d0; border-left-color:#22c55e; background:#f0fdf4; opacity:1; }
        .dark .tareas-dia-item.is-metrica { background:rgba(20,184,166,.12); }
        .dark .tareas-dia-item.is-critica { background:rgba(239,68,68,.12); }
        .dark .tareas-dia-item.is-vencida { background:rgba(245,158,11,.12); }
        .dark .tareas-dia-item.is-done { background:rgba(34,197,94,.12); }
        .tareas-pager {
            display:flex; align-items:center; justify-content:space-between; gap:.75rem;
            margin-top:.75rem; padding-top:.75rem; border-top:1px solid #e2e8f0; flex:0 0 auto;
        }
        .dark .tareas-pager { border-top-color:#334155; }
        .tareas-pager__info { font-size:.75rem; color:#64748b; }
        .tareas-pager__nav { display:flex; align-items:center; gap:.35rem; }
        .tareas-pager__page { font-size:.78rem; font-weight:600; min-width:2.6rem; text-align:center; }
        .tareas-pager .tarea-btn { min-width:2rem; padding:.25rem .5rem; }
        .tareas-pager .tarea-btn:disabled { opacity:.35; cursor:not-allowed; }

        @media (max-width: 1200px) {
            .tareas-cal-layout { grid-template-columns:minmax(0,1.35fr) minmax(280px, 1fr); }
        }
        @media (max-width: 1024px) {
            .tareas-module { min-height:0; }
            .tareas-cal-layout {
                grid-template-columns:1fr;
                min-height:0;
            }
            .tareas-cal-grid {
                min-height:22rem;
                height:auto;
                grid-auto-rows:minmax(3.5rem, 4.4rem);
            }
            .tareas-dia-panel--side {
                height:auto;
                max-height:min(48vh, 26rem);
            }
        }
        @media (max-width: 640px) {
            .tareas-cal-wrap { padding:.85rem; }
            .tareas-cal-grid { min-height:18rem; grid-auto-rows:minmax(3rem, 3.6rem); }
            .tareas-cal-cell { min-height:3rem; padding:.3rem .25rem; }
            .tareas-cal-cell__day { font-size:.75rem; }
            .tareas-kpi { padding:.7rem .8rem; }
            .tareas-kpi__value { font-size:1.25rem; }
            .tareas-cal-toolbar { padding:.6rem .7rem; }
        }
    </style>

    @include('partials.tareas-modal-js')
    @include('partials.tareas-alerta')
</div>
