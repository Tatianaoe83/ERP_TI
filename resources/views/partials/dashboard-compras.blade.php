@php
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = $meses[$statsCompras['mes']] ?? '';
    $filtrosDash = $filtrosDash ?? ['gerencia_id' => 0, 'centro_id' => 0, 'anio' => (int) now()->year];
    $estatusChart = collect($statsCompras['por_estatus'] ?? []);
    $categoriaChart = collect($statsCompras['por_categoria'] ?? []);
    $chartComprasMovimiento = [
        ['nombre' => 'Activas', 'total' => (int) ($statsCompras['activos'] ?? 0)],
        ['nombre' => 'Atendidas en el mes', 'total' => (int) ($statsCompras['atendidos_mes'] ?? 0)],
        ['nombre' => 'Nuevas en el mes', 'total' => (int) ($statsCompras['creados_mes'] ?? 0)],
    ];
    $chartComprasPrioridad = collect($statsCompras['por_prioridad'] ?? [])->map(fn ($p) => [
        'nombre' => $p->Prioridad ?: 'Sin prioridad',
        'total' => (int) $p->total,
    ])->values();
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
<form method="GET" action="{{ route('home') }}" class="index-page__filters">
    <input type="hidden" name="tab" value="compras">
    <div class="form-group">
        <label for="dash-compras-anio">Año</label>
        <select id="dash-compras-anio" name="anio" class="jz form-control" onchange="this.form.submit()">
            @for($y = (int) now()->year; $y >= (int) now()->year - 4; $y--)
                <option value="{{ $y }}" @if((int) $filtrosDash['anio'] === $y) selected @endif>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="form-group d-flex align-items-end">
        <a href="{{ route('home', ['tab' => 'compras']) }}" class="index-page__btn-primary">
            <i class="fa fa-times"></i> Limpiar filtros
        </a>
    </div>
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
    <div class="dash-chart">
        <h3>Movimiento de {{ $nombreMes }}</h3>
        <canvas id="chartComprasMovimiento"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Prioridad de activas</h3>
        <canvas id="chartComprasPrioridad"></canvas>
    </div>
</div>

@push('third_party_scripts')
<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script src="{{ asset('js/ux-charts.js') }}?v=20261022l"></script>
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
                        hoverBackgroundColor: '#0066FF'
                    }]
                },
                options: UxCharts.barras(false)
            });
        }
        var movimiento = document.getElementById('chartComprasMovimiento');
        if (movimiento) {
            var filasM = @json($chartComprasMovimiento);
            new Chart(movimiento, {
                type: 'bar',
                data: {
                    labels: filasM.map(function (f) { return f.nombre; }),
                    datasets: [{
                        label: 'Solicitudes',
                        data: filasM.map(function (f) { return f.total; }),
                        backgroundColor: ['#0052CC', '#36B37E', '#FFAB00'],
                        borderRadius: 8
                    }]
                },
                options: UxCharts.barras(false)
            });
        }
        var prioridad = document.getElementById('chartComprasPrioridad');
        if (prioridad) {
            var filasP = @json($chartComprasPrioridad);
            if (!filasP.length) filasP = [{ nombre: 'Sin activas', total: 1 }];
            var vacia = filasP[0].nombre === 'Sin activas';
            new Chart(prioridad, {
                type: 'doughnut',
                plugins: vacia ? [] : [UxCharts.centro],
                data: {
                    labels: filasP.map(function (f) { return f.nombre; }),
                    datasets: [{
                        data: filasP.map(function (f) { return f.total; }),
                        backgroundColor: vacia ? ['#E2E8F0'] : UxCharts.tonos(filasP.length)
                    }]
                },
                options: UxCharts.anillo()
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
