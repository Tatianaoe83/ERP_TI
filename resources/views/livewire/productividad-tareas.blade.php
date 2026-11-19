<div class="prod-tareas space-y-4 pt-3">
    @include('partials.tareas-alerta', ['mensaje' => session('prod_tareas_mensaje')])

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-2.5">
        <div class="tareas-kpi">
            <span class="tareas-kpi__label">Pendientes</span>
            <span class="tareas-kpi__value">{{ $kpis['pendientes'] }}</span>
        </div>
        <div class="tareas-kpi">
            <span class="tareas-kpi__label">Pendientes de hoy</span>
            <span class="tareas-kpi__value">{{ $kpis['hoy'] }}</span>
        </div>
        <div class="tareas-kpi tareas-kpi--danger">
            <span class="tareas-kpi__label">Críticas</span>
            <span class="tareas-kpi__value">{{ $kpis['criticas'] }}</span>
        </div>
        <div class="tareas-kpi tareas-kpi--ok">
            <span class="tareas-kpi__label">Completadas (mes)</span>
            <span class="tareas-kpi__value">{{ $kpis['completadas_mes'] }}</span>
        </div>
        <div class="tareas-kpi">
            <span class="tareas-kpi__label">Sin fecha</span>
            <span class="tareas-kpi__value">{{ $kpis['sin_fecha'] }}</span>
        </div>
        <div class="tareas-kpi tareas-kpi--info">
            <span class="tareas-kpi__label">Métricas activas</span>
            <span class="tareas-kpi__value">{{ $kpis['metricas_activas'] }}</span>
        </div>
    </div>

    @php
        $chartTareas = [
            'carga' => [
                ['nombre' => 'Pendientes', 'total' => (int) $kpis['pendientes']],
                ['nombre' => 'De hoy', 'total' => (int) $kpis['hoy']],
                ['nombre' => 'Críticas', 'total' => (int) $kpis['criticas']],
                ['nombre' => 'Completadas', 'total' => (int) $kpis['completadas_mes']],
                ['nombre' => 'Sin fecha', 'total' => (int) $kpis['sin_fecha']],
                ['nombre' => 'Métricas activas', 'total' => (int) $kpis['metricas_activas']],
            ],
            'personal' => collect($rendimiento)->map(fn ($row) => [
                'nombre' => $row['empleado']->NombreEmpleado ?? 'Sin asignar',
                'pendientes' => (int) $row['pendientes'],
                'criticas' => (int) $row['criticas'],
                'completadas' => (int) $row['completadas_mes'],
            ])->values()->all(),
            'metricas' => [
                'Activas' => $metricas->where('activo', true)->count(),
                'Inactivas' => $metricas->where('activo', false)->count(),
            ],
        ];
    @endphp
    <script id="productividad-tareas-json" type="application/json">{!! json_encode($chartTareas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) !!}</script>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="rounded-xl p-4 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold m-0 mb-2">Carga de tareas</h3>
            <div class="relative h-[260px]"><canvas id="chartTareasCarga" role="img" aria-label="Carga de tareas"></canvas></div>
        </div>
        <div class="rounded-xl p-4 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold m-0 mb-2">Métricas mensuales</h3>
            <div class="relative h-[260px]"><canvas id="chartTareasMetricas" role="img" aria-label="Métricas activas e inactivas"></canvas></div>
        </div>
    </div>
    <div class="rounded-xl p-4 border border-gray-200 dark:border-[#2A2F3A]">
        <h3 class="text-sm font-semibold m-0 mb-1">Carga por persona</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Pendientes, críticas y completadas del mes. El detalle sigue en la tabla.</p>
        <div class="relative h-[280px]"><canvas id="chartTareasPersonal" role="img" aria-label="Carga de tareas por persona"></canvas></div>
    </div>

    <div class="index-page__card">
        <div class="px-4 py-3">
            <h3 class="text-sm font-semibold m-0">Rendimiento por personal de TI</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0 mt-1">Carga actual de tareas asignadas a usuarios activos del área.</p>
        </div>
        <div class="index-page__table-wrap table-responsive">
            <table class="table index-table index-table--sin-accion w-full">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Pendientes</th>
                        <th>De hoy</th>
                        <th>Críticas</th>
                        <th>Sin fecha</th>
                        <th>Completadas (mes)</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rendimiento as $row)
                    <tr>
                        <td>{{ $row['empleado']->NombreEmpleado ?? 'Sin asignar (métricas)' }}</td>
                        <td><span class="erp-chip erp-chip--aviso">{{ $row['pendientes'] }}</span></td>
                        <td>{{ $row['hoy'] }}</td>
                        <td>
                            @if($row['criticas'] > 0)
                                <span class="erp-chip erp-chip--alerta">{{ $row['criticas'] }}</span>
                            @else
                                <span class="erp-chip erp-chip--neutro">0</span>
                            @endif
                        </td>
                        <td>{{ $row['sin_fecha'] }}</td>
                        <td><span class="erp-chip erp-chip--ok">{{ $row['completadas_mes'] }}</span></td>
                        <td>{{ $row['total'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">Aún no hay tareas asignadas al personal de TI.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="index-page__card">
        <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold m-0">Métricas mensuales</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-0 mt-1">Tareas que se crean solas una vez al mes. El detalle de cumplimiento se ve aquí, no en Tareas.</p>
            </div>
            @can('tickets.gestionar-tareas')
            <div class="flex gap-2">
                <button type="button" wire:click="generarMetricasAhora" class="index-page__btn-secondary text-sm">
                    <i class="fas fa-sync-alt"></i> Generar mes actual
                </button>
                <button type="button" wire:click="abrirModalMetrica" class="index-page__btn-primary text-sm">
                    <i class="fas fa-plus"></i> Nueva métrica
                </button>
            </div>
            @endcan
        </div>
        <div class="index-page__table-wrap table-responsive">
            <table class="table index-table w-full @cannot('tickets.gestionar-tareas') index-table--sin-accion @endcannot">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Día de creación</th>
                        <th>Estado</th>
                        @can('tickets.ver-creador-tarea')
                        <th>Creada por</th>
                        @endcan
                        @can('tickets.gestionar-tareas')
                        <th>Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($metricas as $metrica)
                    <tr wire:key="prod-metrica-{{ $metrica->id }}">
                        <td>
                            <div>{{ $metrica->nombre }}</div>
                            @if($metrica->descripcion)
                            <div class="text-xs text-gray-500">{{ Str::limit($metrica->descripcion, 80) }}</div>
                            @endif
                        </td>
                        <td>Día {{ $metrica->dia_compromiso }}</td>
                        <td>
                            @if($metrica->activo)
                            <span class="erp-chip erp-chip--ok">Activa</span>
                            @else
                            <span class="erp-chip erp-chip--neutro">Inactiva</span>
                            @endif
                        </td>
                        @can('tickets.ver-creador-tarea')
                        <td>{{ optional($metrica->creador)->name ?: (optional($metrica->creador)->username ?: 'Sin registro') }}</td>
                        @endcan
                        @can('tickets.gestionar-tareas')
                        <td>
                            <button type="button" wire:click="abrirModalMetrica({{ $metrica->id }})" class="tarea-btn" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 3 + (auth()->user()->can('tickets.ver-creador-tarea') ? 1 : 0) + (auth()->user()->can('tickets.gestionar-tareas') ? 1 : 0) }}">Sin métricas configuradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($modalMetricaAbierto)
    <div class="tareas-modal-backdrop"
         x-data="tareasModalCerrable('modalMetricaAbierto')"
         :class="{ 'is-closing': !abierto }"
         @click.self="cerrar()"
         @keydown.escape.window="cerrar()">
        <div class="tareas-modal">
            <div class="tareas-modal__head">
                <h3>{{ $metricaEditId ? 'Editar métrica mensual' : 'Nueva métrica mensual' }}</h3>
                <button type="button" @click="cerrar()" class="tareas-modal__close">&times;</button>
            </div>
            <form wire:submit.prevent="guardarMetrica" class="tareas-modal__body">
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">
                    Esta tarea se generará automáticamente <strong>una vez al mes</strong> en el día que indiques. No requiere responsable.
                </p>
                @if($metricaEditId && $metricaCreador)
                @can('tickets.ver-creador-tarea')
                <p class="prod-tareas__creador">
                    <i class="fas fa-user-edit"></i>
                    Creada por <strong>{{ $metricaCreador }}</strong>
                </p>
                @endcan
                @endif

                <div class="form-group mb-3">
                    <label>Nombre de la métrica / tarea mensual</label>
                    <input type="text" wire:model.defer="metrica_nombre" class="form-control" required placeholder="Ej. Envío de reporte mensual">
                    @error('metrica_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="form-group mb-3">
                    <label>Descripción / razón</label>
                    <textarea wire:model.defer="metrica_descripcion" class="form-control" rows="2" placeholder="¿Qué debe hacerse cada mes?"></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Día del mes en que se crea la tarea (1–28)</label>
                    <input type="number" min="1" max="28" wire:model.defer="metrica_dia_compromiso" class="form-control" required>
                    <small class="text-muted">Ej. día 1 = se crea el 1 de cada mes; día 15 = el 15 de cada mes.</small>
                    @error('metrica_dia_compromiso') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <label class="inline-flex items-center gap-2 mb-4">
                    <input type="checkbox" wire:model.defer="metrica_activo">
                    <span>Métrica activa (generar tareas automáticamente)</span>
                </label>
                <div class="tareas-modal__foot">
                    <button type="button" @click="cerrar()" class="index-page__btn-secondary">Cancelar</button>
                    <button type="submit" class="index-page__btn-primary">Guardar métrica</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <style>
        .prod-tareas .tareas-kpi { text-align:left; border:1px solid rgba(148,163,184,.35); border-radius:14px; padding:.85rem 1rem; background:#fff; }
        .dark .prod-tareas .tareas-kpi { background:#101010; border-color:#334155; }
        .prod-tareas .tareas-kpi--danger .tareas-kpi__value { color:#ef4444; }
        .prod-tareas .tareas-kpi--ok .tareas-kpi__value { color:#10b981; }
        .prod-tareas .tareas-kpi--info .tareas-kpi__value { color:#6366f1; }
        .prod-tareas .tareas-kpi__label { display:block; font-size:.75rem; opacity:.75; }
        .prod-tareas .tareas-kpi__value { display:block; font-size:1.35rem; font-weight:700; line-height:1.1; }
        .prod-tareas .tarea-badge { display:inline-flex; align-items:center; gap:.25rem; border-radius:999px; padding:.2rem .55rem; font-size:.72rem; font-weight:600; }
        .prod-tareas .tarea-badge--ok { background:#d1fae5; color:#047857; }
        .prod-tareas .tarea-badge--muted { background:#e2e8f0; color:#475569; }
        .prod-tareas .tarea-btn { width:34px; height:34px; border-radius:10px; border:1px solid rgba(148,163,184,.35); background:transparent; display:inline-flex; align-items:center; justify-content:center; }
        /* Misma entrada que los modales de tabla-tareas: backdrop en fade y caja
           con scale+translate. Solo transform/opacity. */
        @keyframes prodBackdropIn { from { opacity:0; } to { opacity:1; } }
        @keyframes prodModalIn {
            from { opacity:0; transform:translateY(10px) scale(.97); }
            to   { opacity:1; transform:translateY(0)    scale(1);   }
        }
        .prod-tareas .tareas-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); backdrop-filter:blur(2px); z-index:1050; display:flex; align-items:center; justify-content:center; padding:1rem; animation:prodBackdropIn .18s ease-out; }
        .prod-tareas .tareas-modal { width:100%; max-width:520px; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.25); animation:prodModalIn .2s cubic-bezier(.2,.8,.3,1); }
        .dark .prod-tareas .tareas-modal { background:#101010; color:#f8fafc; box-shadow:0 20px 50px rgba(0,0,0,.6); }
        .prod-tareas__creador { display:flex; align-items:center; gap:.4rem; font-size:.82rem; margin-bottom:.75rem; padding:.5rem .7rem; border-radius:10px; background:rgba(99,102,241,.1); color:#4338ca; }
        .dark .prod-tareas__creador { background:rgba(99,102,241,.18); color:#c7d2fe; }
        .prod-tareas .tareas-modal__body .form-control { min-height:44px; font-size:.9rem; }
        .prod-tareas .tareas-modal__body textarea.form-control { min-height:auto; }
        /* Salida más corta que la entrada (140 vs 200ms) */
        @keyframes prodBackdropOut { from { opacity:1; } to { opacity:0; } }
        @keyframes prodModalOut {
            from { opacity:1; transform:translateY(0)   scale(1);   }
            to   { opacity:0; transform:translateY(6px) scale(.98); }
        }
        .prod-tareas .tareas-modal-backdrop.is-closing { animation:prodBackdropOut .14s ease-in forwards; }
        .prod-tareas .tareas-modal-backdrop.is-closing .tareas-modal { animation:prodModalOut .14s ease-in forwards; }
        @media (prefers-reduced-motion: reduce) {
            .prod-tareas .tareas-modal-backdrop, .prod-tareas .tareas-modal,
            .prod-tareas .tareas-modal-backdrop.is-closing,
            .prod-tareas .tareas-modal-backdrop.is-closing .tareas-modal { animation:none; }
        }
        .prod-tareas .tareas-modal__head { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.1rem; border-bottom:1px solid rgba(148,163,184,.25); }
        .prod-tareas .tareas-modal__head h3 { margin:0; font-size:1.05rem; font-weight:700; }
        .prod-tareas .tareas-modal__close { border:none; background:transparent; font-size:1.5rem; line-height:1; opacity:.7; }
        .prod-tareas .tareas-modal__body { padding:1rem 1.1rem; max-height:70vh; overflow:auto; }
        .prod-tareas .tareas-modal__foot { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
    </style>

    @include('partials.tareas-modal-js')
</div>
