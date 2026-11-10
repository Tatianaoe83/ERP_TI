@php
    $p = $productividad ?? $productividadMovimientos ?? [];
    $horas = function ($valor) {
        if ($valor === null) {
            return 'Sin datos';
        }
        if ($valor < 24) {
            return $valor . ' h';
        }

        return round($valor / 24, 1) . ' días';
    };
    $serie = function ($filas) {
        return collect($filas ?? [])->map(fn ($fila) => [
            'nombre' => $fila['nombre'] ?? '',
            'total' => (int) ($fila['total'] ?? 0),
        ])->values();
    };
@endphp

@if(!empty($tituloSeccion))
<p class="dash-section-title">{{ $tituloSeccion }}</p>
@endif
<div class="dash-kpi-grid dash-kpi-grid--ops">
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Pendiente aprobación</p>
                <p class="dash-kpi__hint">Esperan el visto bueno</p>
                <p class="dash-kpi__value">{{ $p['pendientes'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-clock"></i></span>
        </div>
    </div>
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Aprobadas</p>
                <p class="dash-kpi__hint">Firmadas y sin aplicar en Neodata</p>
                <p class="dash-kpi__value">{{ $p['aprobadas'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-signature"></i></span>
        </div>
    </div>
    <div class="dash-kpi dash-kpi--ok">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Aplicadas</p>
                <p class="dash-kpi__hint">{{ $p['total'] ?? 0 }} solicitudes en el filtro</p>
                <p class="dash-kpi__value">{{ $p['aplicadas'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-check"></i></span>
        </div>
    </div>
    <div class="dash-kpi {{ ($p['rechazadas'] ?? 0) > 0 ? 'dash-kpi--alerta' : '' }}">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Rechazadas</p>
                <p class="dash-kpi__hint">El visto bueno no pasó</p>
                <p class="dash-kpi__value">{{ $p['rechazadas'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-times"></i></span>
        </div>
    </div>
</div>

<div class="dash-kpi-grid prod-mov-tiempos" style="margin-top:1rem;">
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Hasta la firma</p>
                <p class="dash-kpi__hint">Promedio desde que se envía hasta el visto bueno</p>
                <p class="dash-kpi__value">{{ $horas($p['horas_firma'] ?? null) }}</p>
            </div>
        </div>
    </div>
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Hasta Neodata</p>
                <p class="dash-kpi__hint">Promedio desde la firma hasta marcarla aplicada</p>
                <p class="dash-kpi__value">{{ $horas($p['horas_neodata'] ?? null) }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    .dash-kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    @media (min-width: 900px) { .dash-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .prod-mov-tiempos { grid-template-columns: 1fr; }
    @media (min-width: 700px) { .prod-mov-tiempos { grid-template-columns: 1fr 1fr; } }
    .prod-mov-charts { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-top: 1rem; }
    @media (min-width: 900px) {
        .prod-mov-charts { grid-template-columns: 1fr 1fr; }
        .prod-mov-charts .dash-chart--wide { grid-column: 1 / -1; }
    }
    .prod-mov-charts .dash-chart { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; padding: 1rem 1rem 0.75rem; }
    .prod-mov-charts .dash-chart h3 { margin: 0 0 0.35rem; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
    html.dark .prod-mov-charts .dash-chart, .dark .prod-mov-charts .dash-chart { background: #1c1f26; border-color: #334155; }
    html.dark .prod-mov-charts .dash-chart h3, .dark .prod-mov-charts .dash-chart h3 { color: #94a3b8; }
</style>

<div class="prod-mov-charts dash-charts" id="prod-mov-raiz">
    <div class="dash-chart">
        <h3>Por estatus</h3>
        <canvas id="chartMovEstatus"></canvas>
    </div>
    <div class="dash-chart dash-chart--wide">
        <h3>Solicitudes por mes</h3>
        <canvas id="chartMovMes"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Por proyecto</h3>
        <canvas id="chartMovObras"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Quién marcó aplicada</h3>
        <canvas id="chartMovPersonas"></canvas>
    </div>
</div>

@push('third_party_scripts')
<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script src="{{ asset('js/ux-charts.js') }}?v=20261110a"></script>
<script>
(function () {
    var meses = @json(collect($p['meses'] ?? [])->values());
    var estatus = @json($serie($p['estatus'] ?? []));
    var obras = @json($serie($p['obras'] ?? []));
    var personas = @json($serie($p['personas'] ?? []));

    function bootMovProductividad() {
        var raiz = document.getElementById('prod-mov-raiz');
        if (!raiz || !window.Chart || !window.UxCharts) return;
        if (raiz.closest('[hidden]')) return;
        if (raiz.dataset.listo === '1') {
            raiz.querySelectorAll('canvas').forEach(function (canvas) {
                var chart = Chart.getChart ? Chart.getChart(canvas) : null;
                if (chart && chart.resize) chart.resize();
            });
            return;
        }
        UxCharts.aplicar(Chart);
        if (typeof UxCharts.preparar === 'function') UxCharts.preparar(raiz);
        var nueva = typeof UxCharts.crear === 'function'
            ? UxCharts.crear
            : function (canvas, config) { return new window.Chart(canvas, config); };

        var lienzoEstatus = document.getElementById('chartMovEstatus');
        if (lienzoEstatus) {
            var vacio = !estatus.some(function (fila) { return fila.total > 0; });
            nueva(lienzoEstatus, {
                type: 'doughnut',
                plugins: vacio ? [] : [UxCharts.centro],
                data: {
                    labels: vacio ? ['Sin solicitudes'] : estatus.map(function (fila) { return fila.nombre; }),
                    datasets: [{
                        data: vacio ? [1] : estatus.map(function (fila) { return fila.total; }),
                        backgroundColor: vacio ? ['#E2E8F0'] : ['#FFAB00', '#0052CC', '#36B37E', '#FF5630']
                    }]
                },
                options: UxCharts.anillo()
            });
        }

        var lienzoMes = document.getElementById('chartMovMes');
        if (lienzoMes) {
            var optsMes = UxCharts.barras(false);
            optsMes.plugins.legend.display = true;
            nueva(lienzoMes, {
                type: 'bar',
                data: {
                    labels: meses.map(function (fila) { return fila.nombre; }),
                    datasets: [
                        { label: 'Creadas', data: meses.map(function (fila) { return fila.total; }), backgroundColor: '#0052CC', maxBarThickness: 16 },
                        { label: 'Aplicadas', data: meses.map(function (fila) { return fila.aplicadas; }), backgroundColor: '#36B37E', maxBarThickness: 16 },
                        { label: 'Rechazadas', data: meses.map(function (fila) { return fila.rechazadas; }), backgroundColor: '#FF5630', maxBarThickness: 16 }
                    ]
                },
                options: optsMes
            });
        }

        function barras(id, filas) {
            var lienzo = document.getElementById(id);
            if (!lienzo) return;
            var datos = filas.length ? filas : [{ nombre: 'Sin datos', total: 0 }];
            nueva(lienzo, {
                type: 'bar',
                data: {
                    labels: datos.map(function (fila) { return fila.nombre; }),
                    datasets: [{ data: datos.map(function (fila) { return fila.total; }), backgroundColor: '#0052CC', maxBarThickness: 22 }]
                },
                options: UxCharts.barras(true)
            });
        }

        barras('chartMovObras', obras);
        barras('chartMovPersonas', personas);
        raiz.dataset.listo = '1';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootMovProductividad);
    } else {
        bootMovProductividad();
    }
    document.addEventListener('app-tab-change', function () {
        window.setTimeout(bootMovProductividad, 40);
    });
})();
</script>
@endpush
