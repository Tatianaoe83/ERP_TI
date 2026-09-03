<div class="prod-tareas space-y-4 pt-3">
    @if (session('prod_tareas_mensaje'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
        <i class="fas fa-check-circle mr-1"></i> {{ session('prod_tareas_mensaje') }}
    </div>
    @endif

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

    <div class="rounded-lg border border-gray-200 dark:border-[#2A2F3A] overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold m-0">Rendimiento por personal de TI</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0 mt-1">Carga actual de tareas asignadas a usuarios activos del área.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-2">Usuario</th>
                        <th class="px-4 py-2">Pendientes</th>
                        <th class="px-4 py-2">De hoy</th>
                        <th class="px-4 py-2">Críticas</th>
                        <th class="px-4 py-2">Sin fecha</th>
                        <th class="px-4 py-2">Completadas (mes)</th>
                        <th class="px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rendimiento as $row)
                    <tr class="border-t border-gray-100 dark:border-[#2A2F3A]">
                        <td class="px-4 py-2 font-medium">{{ $row['empleado']->NombreEmpleado ?? 'Sin asignar (métricas)' }}</td>
                        <td class="px-4 py-2">{{ $row['pendientes'] }}</td>
                        <td class="px-4 py-2">{{ $row['hoy'] }}</td>
                        <td class="px-4 py-2 {{ $row['criticas'] > 0 ? 'text-red-500 font-semibold' : '' }}">{{ $row['criticas'] }}</td>
                        <td class="px-4 py-2">{{ $row['sin_fecha'] }}</td>
                        <td class="px-4 py-2">{{ $row['completadas_mes'] }}</td>
                        <td class="px-4 py-2">{{ $row['total'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aún no hay tareas asignadas al personal de TI.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-[#2A2F3A] overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-[#2A2F3A] flex flex-wrap items-center justify-between gap-2">
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
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Día de creación</th>
                        <th class="px-4 py-2">Estado</th>
                        @can('tickets.gestionar-tareas')
                        <th class="px-4 py-2">Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($metricas as $metrica)
                    <tr class="border-t border-gray-100 dark:border-[#2A2F3A]" wire:key="prod-metrica-{{ $metrica->id }}">
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $metrica->nombre }}</div>
                            @if($metrica->descripcion)
                            <div class="text-xs text-gray-500">{{ Str::limit($metrica->descripcion, 80) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2">Día <strong>{{ $metrica->dia_compromiso }}</strong></td>
                        <td class="px-4 py-2">
                            @if($metrica->activo)
                            <span class="tarea-badge tarea-badge--ok">Activa</span>
                            @else
                            <span class="tarea-badge tarea-badge--muted">Inactiva</span>
                            @endif
                        </td>
                        @can('tickets.gestionar-tareas')
                        <td class="px-4 py-2">
                            <button type="button" wire:click="abrirModalMetrica({{ $metrica->id }})" class="tarea-btn" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">Sin métricas configuradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($modalMetricaAbierto)
    <div class="tareas-modal-backdrop" wire:click.self="$set('modalMetricaAbierto', false)">
        <div class="tareas-modal">
            <div class="tareas-modal__head">
                <h3>{{ $metricaEditId ? 'Editar métrica mensual' : 'Nueva métrica mensual' }}</h3>
                <button type="button" wire:click="$set('modalMetricaAbierto', false)" class="tareas-modal__close">&times;</button>
            </div>
            <form wire:submit.prevent="guardarMetrica" class="tareas-modal__body">
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">
                    Esta tarea se generará automáticamente <strong>una vez al mes</strong> en el día que indiques. No requiere responsable.
                </p>
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
                    <button type="button" wire:click="$set('modalMetricaAbierto', false)" class="index-page__btn-secondary">Cancelar</button>
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
        .prod-tareas .tareas-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:1050; display:flex; align-items:center; justify-content:center; padding:1rem; }
        .prod-tareas .tareas-modal { width:100%; max-width:520px; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.25); }
        .dark .prod-tareas .tareas-modal { background:#101010; color:#f8fafc; }
        .prod-tareas .tareas-modal__head { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.1rem; border-bottom:1px solid rgba(148,163,184,.25); }
        .prod-tareas .tareas-modal__head h3 { margin:0; font-size:1.05rem; font-weight:700; }
        .prod-tareas .tareas-modal__close { border:none; background:transparent; font-size:1.5rem; line-height:1; opacity:.7; }
        .prod-tareas .tareas-modal__body { padding:1rem 1.1rem; max-height:70vh; overflow:auto; }
        .prod-tareas .tareas-modal__foot { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
    </style>
</div>
