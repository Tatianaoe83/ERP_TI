@php
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = $meses[$statsCompras['mes']] ?? '';
    $filtrosDash = $filtrosDash ?? ['gerencia_id' => 0, 'centro_id' => 0, 'anio' => (int) now()->year];
    $estatusChart = collect($statsCompras['por_estatus'] ?? []);
    $categoriaChart = collect($statsCompras['por_categoria'] ?? []);
@endphp

<style>
    .dash-filtros { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; align-items: flex-end; margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; }
    html.dark .dash-filtros, .dark .dash-filtros { background: rgba(15, 23, 42, 0.72); border-color: rgba(255, 255, 255, 0.12); }
    .dash-filtro { display: flex; flex-direction: column; gap: 0.35rem; flex: 0 1 180px; min-width: 0; }
    .dash-filtro > span { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
    .dash-filtros .select2-container { width: 100% !important; }
    .dash-charts { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.25rem; }
    @media (min-width: 900px) { .dash-charts { grid-template-columns: 1fr 1fr; } }
    .dash-chart { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; padding: 1rem 1rem 0.75rem; }
    .dash-chart h3 { margin: 0 0 0.35rem; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
    html.dark .dash-chart h3, .dark .dash-chart h3 { color: #94a3b8; }
</style>
<form method="GET" action="{{ route('home') }}" class="dash-filtros">
    <input type="hidden" name="tab" value="compras">
    <div class="dash-filtro">
        <span>Año</span>
        <select name="anio" class="form-control">
            @for($y = (int) now()->year; $y >= (int) now()->year - 4; $y--)
                <option value="{{ $y }}" @if((int) $filtrosDash['anio'] === $y) selected @endif>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <button type="submit" class="index-page__btn-primary">Filtrar</button>
</form>

<div class="dash-charts">
    <div class="dash-chart">
        <h3>Solicitudes por estado</h3>
        <canvas id="chartComprasEstatus"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Activas por categoría</h3>
        <canvas id="chartComprasCategoria"></canvas>
    </div>
</div>

@push('third_party_scripts')
<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script src="{{ asset('js/ux-charts.js') }}"></script>
<script>
(function () {
    function bootDashCompras() {
        if (!window.Chart || !window.UxCharts || document.getElementById('chartComprasEstatus')?.dataset.listo === '1') return;
        UxCharts.aplicar(Chart);
        var estatus = document.getElementById('chartComprasEstatus');
        var categorias = document.getElementById('chartComprasCategoria');
        if (estatus) {
            estatus.dataset.listo = '1';
            var filas = @json($estatusChart);
            var labels = Object.keys(filas);
            new Chart(estatus, {
                type: 'doughnut',
                plugins: [UxCharts.centro],
                data: {
                    labels: labels,
                    datasets: [{
                        data: labels.map(function (k) { return filas[k]; }),
                        backgroundColor: UxCharts.tonos(labels.length),
                        hoverOffset: 8
                    }]
                },
                options: UxCharts.anillo()
            });
        }
        if (categorias) {
            var cats = @json($categoriaChart->values());
            new Chart(categorias, {
                type: 'bar',
                data: {
                    labels: cats.map(function (f) { return f.Categoria || 'Sin categoría'; }),
                    datasets: [{
                        label: 'Activas',
                        data: cats.map(function (f) { return f.total; }),
                        backgroundColor: UxCharts.degradado(categorias),
                        hoverBackgroundColor: '#4ADE80'
                    }]
                },
                options: UxCharts.barras(false)
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootDashCompras);
    } else {
        bootDashCompras();
    }
})();
</script>
@endpush

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
