@php
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = $meses[$statsCompras['mes']] ?? '';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Solicitudes activas</p>
                <p class="dash-kpi__hint">Pendientes, en proceso y pausadas</p>
                <p class="dash-kpi__value">{{ $statsCompras['activos'] }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-inbox"></i></span>
        </div>
    </div>

    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Atendidas en {{ $nombreMes }}</p>
                <p class="dash-kpi__value">{{ $statsCompras['atendidos_mes'] }}</p>
                <p class="dash-kpi__hint">{{ $statsCompras['anio'] }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-check"></i></span>
        </div>
    </div>

    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Nuevas en {{ $nombreMes }}</p>
                <p class="dash-kpi__value">{{ $statsCompras['creados_mes'] }}</p>
                <p class="dash-kpi__hint">Solicitudes registradas</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-plus"></i></span>
        </div>
    </div>

    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Total histórico</p>
                <p class="dash-kpi__value">{{ $statsCompras['total'] }}</p>
                <p class="dash-kpi__hint">Mantenimientos de compras</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-layer-group"></i></span>
        </div>
    </div>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel__head">
        <h3 class="dash-panel__title">
            <i class="fas fa-tasks"></i>
            Estado de solicitudes
        </h3>
        @can('ver-compras')
            <a href="{{ route('tickets-mantenimiento.index') }}" class="dash-panel__link">Ver tablero</a>
        @endcan
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="dash-stat text-center">
            <p class="dash-stat__label">Pendiente</p>
            <p class="dash-stat__value">{{ $statsCompras['por_estatus']['Pendiente'] ?? 0 }}</p>
        </div>
        <div class="dash-stat text-center">
            <p class="dash-stat__label">En proceso</p>
            <p class="dash-stat__value">{{ $statsCompras['por_estatus']['En proceso'] ?? 0 }}</p>
        </div>
        <div class="dash-stat text-center">
            <p class="dash-stat__label">Pausado</p>
            <p class="dash-stat__value">{{ $statsCompras['por_estatus']['Pausado'] ?? 0 }}</p>
        </div>
        <div class="dash-stat text-center">
            <p class="dash-stat__label">Atendido</p>
            <p class="dash-stat__value">{{ $statsCompras['por_estatus']['Atendido'] ?? 0 }}</p>
        </div>
        <div class="dash-stat text-center">
            <p class="dash-stat__label">Cancelado</p>
            <p class="dash-stat__value">{{ $statsCompras['por_estatus']['Cancelado'] ?? 0 }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="dash-panel">
        <h3 class="dash-panel__title mb-3">
            <i class="fas fa-tags"></i>
            Categorías con más solicitudes activas
        </h3>
        <div class="space-y-2">
            @forelse($statsCompras['por_categoria'] as $categoria)
            <div class="dash-row">
                <span class="dash-row__name">{{ $categoria->Categoria ?: 'Sin categoría' }}</span>
                <span class="dash-chip">{{ $categoria->total }}</span>
            </div>
            @empty
            <p class="dash-kpi__hint text-center py-4">No hay solicitudes activas por categoría</p>
            @endforelse
        </div>
    </div>

    <div class="dash-panel">
        <h3 class="dash-panel__title mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            Prioridad de solicitudes activas
        </h3>
        <div class="space-y-2">
            @forelse($statsCompras['por_prioridad'] as $prioridad)
            <div class="dash-row">
                <span class="dash-row__name">{{ $prioridad->Prioridad ?: 'Sin prioridad' }}</span>
                <span class="dash-chip">{{ $prioridad->total }}</span>
            </div>
            @empty
            <p class="dash-kpi__hint text-center py-4">No hay solicitudes activas por prioridad</p>
            @endforelse
        </div>
    </div>
</div>

<p class="dash-section-title">Accesos rápidos</p>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
    @can('ver-compras')
    <a href="{{ route('tickets-mantenimiento.index') }}" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-wrench"></i></span>
        <span class="dash-quick__name">Mantenimientos</span>
        <span class="dash-quick__hint">Tablero de compras</span>
    </a>
    @endcan
</div>
