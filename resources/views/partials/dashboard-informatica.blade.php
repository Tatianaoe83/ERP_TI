@php
    $op = $stats['operacion'] ?? [];
    $pre = $stats['presupuestados'] ?? [];
    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 0);
@endphp
<style>
    .dash-top-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: stretch;
    }
    @media (min-width: 1024px) {
        .dash-top-grid { grid-template-columns: 1fr 1fr; }
    }
    .dash-top-col {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-height: 0;
    }
    .dash-kpi--fill {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
    }
    .dash-kpi--fill .dash-equipos-grid {
        flex: 1 1 auto;
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        align-items: stretch;
    }
    @media (min-width: 768px) {
        .dash-kpi--fill .dash-equipos-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .dash-kpi--fill .dash-stat {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.35rem;
        min-height: 5.5rem;
    }
    .dash-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 900px) {
        .dash-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .dash-kpi-grid--3 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    @media (min-width: 768px) {
        .dash-kpi-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .dash-kpi-grid.dash-kpi-grid--ops {
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    @media (min-width: 640px) {
        .dash-kpi-grid.dash-kpi-grid--ops { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1100px) {
        .dash-kpi-grid.dash-kpi-grid--ops { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    }
    .dash-kpi-grid--ops .dash-kpi__label {
        line-height: 1.25;
    }
</style>

@php
    $filtrosDash = $filtrosDash ?? ['gerencia_id' => 0, 'centro_id' => 0, 'presupuesto_id' => 0, 'anio' => (int) now()->year];
    $catalogoDash = $catalogoDash ?? ['gerencias' => collect(), 'centros' => collect(), 'presupuestos' => collect()];
    $chartPresupuestos = collect($stats['estadisticas_presupuesto'] ?? [])->map(fn ($g) => [
        'nombre' => $g->NombrePresupuesto,
        'total' => (int) $g->total_empleados,
    ])->values();
    $chartCategorias = collect($stats['equipos_por_categoria'] ?? [])->take(8)->map(fn ($e) => [
        'nombre' => $e->CategoriaEquipo ?: 'Sin categoría',
        'total' => (int) $e->total_inventario,
    ])->values();
    $decision = $stats['decision'] ?? [];
    $chartMeses = array_values($decision['meses'] ?? []);
    $chartTickets = collect(is_iterable($op['tickets_estatus'] ?? null) ? $op['tickets_estatus'] : [])->map(fn ($fila) => [
        'nombre' => $fila->Estatus ?: 'Sin estatus',
        'total' => (int) $fila->total,
    ])->values();
    $anioDash = (int) ($decision['anio'] ?? $filtrosDash['anio'] ?? now()->year);
    $chartPlantilla = [
        ['nombre' => 'Activos', 'total' => (int) ($stats['empleados']['activos'] ?? 0)],
        ['nombre' => 'Plazas extraordinarias', 'total' => (int) ($stats['empleados']['vacantes'] ?? 0)],
        ['nombre' => 'Sin equipo', 'total' => (int) ($decision['sin_equipo'] ?? 0)],
    ];
    $chartLineas = [
        ['nombre' => 'Disponibles', 'total' => (int) ($stats['inventario']['lineas']['disponibles'] ?? 0)],
        ['nombre' => 'Persona física', 'total' => (int) ($stats['inventario']['lineas']['fisicas'] ?? 0)],
        ['nombre' => 'Referenciado', 'total' => (int) ($stats['inventario']['lineas']['referenciados'] ?? 0)],
    ];
    $chartExtra = [
        ['nombre' => 'Equipos', 'total' => (int) ($pre['equipos'] ?? 0)],
        ['nombre' => 'Insumos', 'total' => (int) ($pre['insumos'] ?? 0)],
        ['nombre' => 'Líneas', 'total' => (int) ($pre['lineas'] ?? 0)],
        ['nombre' => 'Equipo propio', 'total' => (int) ($stats['inventario']['equipos']['propios'] ?? 0)],
    ];
    $chartOperacion = [
        ['nombre' => 'Tickets abiertos', 'total' => (int) ($op['tickets_abiertos'] ?? 0)],
        ['nombre' => 'Tareas atrasadas', 'total' => (int) ($op['tareas_atrasadas'] ?? 0)],
        ['nombre' => 'Tareas de hoy', 'total' => (int) ($op['tareas_hoy'] ?? 0)],
        ['nombre' => 'Solicitudes', 'total' => (int) ($op['solicitudes_activas'] ?? 0)],
        ['nombre' => 'Auditorías', 'total' => (int) ($op['auditorias_anio'] ?? 0)],
    ];
    $chartMantenimiento = [
        ['nombre' => 'Programados', 'total' => (int) ($stats['mantenimientos']['pendientes'] ?? 0)],
        ['nombre' => 'Concluidos', 'total' => (int) ($stats['mantenimientos']['realizados'] ?? 0)],
    ];
    $chartGerencias = collect($stats['estadisticas_gerencia'] ?? [])->map(fn ($g) => [
        'nombre' => $g->NombreGerencia,
        'total' => (int) $g->empleados_activos,
    ])->values();
    $licenciasDash = $stats['insumos_por_licencia'] ?? collect();
    $chartLicencias = collect(is_object($licenciasDash) && method_exists($licenciasDash, 'items') ? $licenciasDash->items() : $licenciasDash)
        ->take(8)
        ->map(fn ($i) => [
            'nombre' => is_object($i) ? ($i->NombreInsumo ?: 'Sin nombre') : 'Sin nombre',
            'total' => is_object($i) ? (int) $i->total_inventario : 0,
        ])->values();
    $chartOrganizacion = [
        ['nombre' => 'Obras', 'total' => (int) ($stats['organizacion']['obras'] ?? 0)],
        ['nombre' => 'Gerencias', 'total' => (int) ($stats['organizacion']['gerencias'] ?? 0)],
        ['nombre' => 'Unidades', 'total' => (int) ($stats['organizacion']['unidades_negocio'] ?? 0)],
    ];
@endphp

<form method="GET" action="{{ route('home') }}" id="dashFiltros" class="index-page__filters">
    <div class="form-group">
        <label for="dash-presupuesto">Presupuesto interno</label>
        <select id="dash-presupuesto" name="presupuesto_id" class="jz form-control">
            <option value="">Todos</option>
            @foreach($catalogoDash['presupuestos'] as $id => $nombre)
                <option value="{{ $id }}" @if((int) $filtrosDash['presupuesto_id'] === (int) $id) selected @endif>{{ $nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="dash-gerencia">Gerencia</label>
        <select id="dash-gerencia" name="gerencia_id" class="jz form-control">
            <option value="">Todas</option>
            @foreach($catalogoDash['gerencias'] as $id => $nombre)
                <option value="{{ $id }}" @if((int) $filtrosDash['gerencia_id'] === (int) $id) selected @endif>{{ $nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="dash-centro">Centro de costos</label>
        <select id="dash-centro" name="centro_id" class="jz form-control">
            <option value="">Todos</option>
            @foreach($catalogoDash['centros'] as $id => $nombre)
                <option value="{{ $id }}" @if((int) $filtrosDash['centro_id'] === (int) $id) selected @endif>{{ $nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="dash-anio">Año</label>
        <select id="dash-anio" name="anio" class="jz form-control">
            @for($anioOpcion = (int) now()->year; $anioOpcion >= (int) now()->year - 2; $anioOpcion--)
                <option value="{{ $anioOpcion }}" @if($anioDash === $anioOpcion) selected @endif>{{ $anioOpcion }}</option>
            @endfor
        </select>
    </div>
    <div class="form-group d-flex align-items-end">
        <a href="{{ route('home') }}" class="index-page__btn-primary">
            <i class="fa fa-times"></i> Limpiar filtros
        </a>
    </div>
</form>

<p class="dash-section-title">Para decidir</p>
<div class="dash-kpi-grid dash-kpi-grid--ops">
    <div class="dash-kpi {{ ((float) ($decision['desviacion'] ?? 0)) > 0 ? 'dash-kpi--alerta' : 'dash-kpi--ok' }}">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Desviación {{ $anioDash }}</p>
                <p class="dash-kpi__hint">Ahorro {{ $fmtMoney($decision['ahorro'] ?? 0) }} · Sin consumir {{ $fmtMoney($decision['no_consumido'] ?? 0) }}</p>
                <p class="dash-kpi__value">{{ $fmtMoney($decision['desviacion'] ?? 0) }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-exclamation-triangle"></i></span>
        </div>
    </div>
    <a href="{{ route('tickets.index') }}" class="dash-kpi" style="text-decoration:none;color:inherit;">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Tickets en atención</p>
                <p class="dash-kpi__hint">{{ $op['tickets_pendientes'] ?? 0 }} pendientes · {{ $op['tickets_progreso'] ?? 0 }} en progreso</p>
                <p class="dash-kpi__value">{{ $op['tickets_abiertos'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-headset"></i></span>
        </div>
    </a>
    <a href="{{ route('tickets.index', ['tab' => 'tareas', 'filtroEstatus' => 'atrasadas']) }}" class="dash-kpi" style="text-decoration:none;color:inherit;">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Tareas atrasadas</p>
                <p class="dash-kpi__hint">{{ $op['tareas_hoy'] ?? 0 }} de hoy · {{ $op['tareas_criticas'] ?? 0 }} críticas</p>
                <p class="dash-kpi__value">{{ $op['tareas_atrasadas'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-tasks"></i></span>
        </div>
    </a>
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Personal sin equipo</p>
                <p class="dash-kpi__hint">Activos del filtro sin equipo de la empresa</p>
                <p class="dash-kpi__value">{{ (int) ($decision['sin_equipo'] ?? 0) }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-laptop"></i></span>
        </div>
    </div>
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Mantenimiento pendiente</p>
                <p class="dash-kpi__hint">{{ $stats['mantenimientos']['realizados'] ?? 0 }} concluidos en {{ $anioDash }}</p>
                <p class="dash-kpi__value">{{ $stats['mantenimientos']['pendientes'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-tools"></i></span>
        </div>
    </div>
</div>

<div class="dash-charts">
    <div class="dash-chart dash-chart--wide">
        <h3>Presupuesto contra facturas {{ $anioDash }}</h3>
        <p class="dash-kpi__hint">Autorizado {{ $fmtMoney($decision['presupuesto'] ?? 0) }} · Facturado {{ $fmtMoney($decision['facturado'] ?? 0) }}. El corte oficial es el de presupuesto interno.</p>
        <canvas id="chartPresupuestoMes"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Inventario asignado</h3>
        <canvas id="chartInventarioDash"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Tickets abiertos</h3>
        <canvas id="chartTicketsDash"></canvas>
    </div>
    <div class="dash-chart dash-chart--wide">
        <h3>Personal por presupuesto interno</h3>
        <canvas id="chartPresupuestoDash"></canvas>
    </div>
    <div class="dash-chart dash-chart--wide">
        <h3>Equipos por categoría</h3>
        <canvas id="chartCategoriasDash"></canvas>
    </div>
</div>

<p class="dash-kpi__hint mb-4">
    Personal, inventario, tickets, tareas y presupuesto de este recuadro siguen el filtro de arriba.
</p>

@push('third_party_scripts')
<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script src="{{ asset('js/ux-charts.js') }}?v=20261022l"></script>
<script>
(function () {
    function bootDashInformatica() {
        if (!window.Chart || !window.UxCharts || document.getElementById('chartInventarioDash')?.dataset.listo === '1') return;
        UxCharts.aplicar(Chart);
        var inventario = document.getElementById('chartInventarioDash');
        var gerencias = document.getElementById('chartPresupuestoDash');
        var categorias = document.getElementById('chartCategoriasDash');
        var presupuestoMes = document.getElementById('chartPresupuestoMes');
        var ticketsChart = document.getElementById('chartTicketsDash');
        if (presupuestoMes) {
            var meses = @json($chartMeses);
            var optsMes = UxCharts.barras(false);
            optsMes.plugins.legend.display = true;
            new Chart(presupuestoMes, {
                type: 'bar',
                data: {
                    labels: meses.map(function (f) { return f.nombre; }),
                    datasets: [
                        { label: 'Presupuesto', data: meses.map(function (f) { return f.presupuesto; }), backgroundColor: '#0052CC', maxBarThickness: 18 },
                        { label: 'Facturado', data: meses.map(function (f) { return f.facturado; }), backgroundColor: '#FFAB00', maxBarThickness: 18 },
                        { label: 'Desviación', data: meses.map(function (f) { return f.desviacion; }), backgroundColor: '#FF5630', maxBarThickness: 18 }
                    ]
                },
                options: optsMes
            });
        }
        if (ticketsChart) {
            var filasT = @json($chartTickets);
            if (!filasT.length) {
                filasT = [{ nombre: 'Sin tickets abiertos', total: 1 }];
            }
            new Chart(ticketsChart, {
                type: 'doughnut',
                plugins: filasT[0].nombre === 'Sin tickets abiertos' ? [] : [UxCharts.centro],
                data: {
                    labels: filasT.map(function (f) { return f.nombre; }),
                    datasets: [{
                        data: filasT.map(function (f) { return f.total; }),
                        backgroundColor: filasT[0].nombre === 'Sin tickets abiertos' ? ['#E2E8F0'] : UxCharts.tonos(filasT.length)
                    }]
                },
                options: UxCharts.anillo()
            });
        }
        if (inventario) {
            inventario.dataset.listo = '1';
            new Chart(inventario, {
                type: 'doughnut',
                plugins: [UxCharts.centro],
                data: {
                    labels: ['Equipos', 'Insumos', 'Líneas'],
                    datasets: [{
                        data: [
                            {{ (int) ($stats['inventario']['equipos']['asignados'] ?? 0) }},
                            {{ (int) ($stats['inventario']['insumos']['asignados'] ?? 0) }},
                            {{ (int) ($stats['inventario']['lineas']['asignadas'] ?? 0) }}
                        ],
                        backgroundColor: ['#0052CC', '#6554C0', '#A3B3C9'],
                        hoverBackgroundColor: ['#0066FF', '#8777D9', '#CBD5E1']
                    }]
                },
                options: UxCharts.anillo()
            });
        }
        if (gerencias) {
            var filasG = @json($chartPresupuestos);
            new Chart(gerencias, {
                type: 'bar',
                data: {
                    labels: filasG.map(function (f) { return f.nombre; }),
                    datasets: [{
                        label: 'Personas',
                        data: filasG.map(function (f) { return f.total; }),
                        backgroundColor: UxCharts.degradado(gerencias),
                        hoverBackgroundColor: '#0066FF'
                    }]
                },
                options: UxCharts.barras(false)
            });
        }
        if (categorias) {
            var filasC = @json($chartCategorias);
            new Chart(categorias, {
                type: 'bar',
                data: {
                    labels: filasC.map(function (f) { return f.nombre; }),
                    datasets: [{
                        label: 'Equipos',
                        data: filasC.map(function (f) { return f.total; }),
                        backgroundColor: UxCharts.tonos(filasC.length),
                        borderRadius: 8
                    }]
                },
                options: UxCharts.barras(true)
            });
        }
        function dona(canvas, filas, vacio) {
            if (!canvas) return;
            var sinDatos = !filas.length || filas.every(function (f) { return !f.total; });
            if (sinDatos) filas = [{ nombre: vacio, total: 1 }];
            new Chart(canvas, {
                type: 'doughnut',
                plugins: sinDatos ? [] : [UxCharts.centro],
                data: {
                    labels: filas.map(function (f) { return f.nombre; }),
                    datasets: [{
                        data: filas.map(function (f) { return f.total; }),
                        backgroundColor: sinDatos ? ['#E2E8F0'] : UxCharts.tonos(filas.length)
                    }]
                },
                options: UxCharts.anillo()
            });
        }
        function barrasNom(canvas, filas, horizontal) {
            if (!canvas) return;
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: filas.map(function (f) { return f.nombre; }),
                    datasets: [{
                        label: 'Total',
                        data: filas.map(function (f) { return f.total; }),
                        backgroundColor: UxCharts.tonos(filas.length),
                        borderRadius: 8
                    }]
                },
                options: UxCharts.barras(horizontal)
            });
        }
        dona(document.getElementById('chartPlantilla'), @json($chartPlantilla), 'Sin personal');
        dona(document.getElementById('chartLineasDash'), @json($chartLineas), 'Sin líneas');
        dona(document.getElementById('chartMantenimientoDash'), @json($chartMantenimiento), 'Sin mantenimiento');
        barrasNom(document.getElementById('chartExtraDash'), @json($chartExtra), false);
        barrasNom(document.getElementById('chartOperacionDash'), @json($chartOperacion), true);
        barrasNom(document.getElementById('chartGerenciasDash'), @json($chartGerencias), true);
        barrasNom(document.getElementById('chartLicenciasDash'), @json($chartLicencias), true);
        barrasNom(document.getElementById('chartOrganizacionDash'), @json($chartOrganizacion), false);
    }
    var filtros = document.getElementById('dashFiltros');
    if (filtros) {
        var filtrosListos = false;
        setTimeout(function () { filtrosListos = true; }, 700);
        filtros.addEventListener('change', function (e) {
            if (!filtrosListos || !e.target || e.target.tagName !== 'SELECT') return;
            filtros.submit();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootDashInformatica);
    } else {
        bootDashInformatica();
    }
})();
</script>
@endpush

<style>
    .dash-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        align-items: flex-end;
        margin-bottom: 1rem;
        padding: 0.85rem 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.85rem;
    }
    html.dark .dash-filtros,
    .dark .dash-filtros {
        background: rgba(15, 23, 42, 0.72);
        border-color: rgba(255, 255, 255, 0.12);
    }
    .dash-filtro {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        flex: 1 1 190px;
        min-width: 0;
    }
    .dash-filtro--wide { flex: 2 1 280px; }
    .dash-filtro--acciones {
        flex: 0 0 auto;
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
    }
    .dash-filtro > span {
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }
    .dash-filtros .select2-container { width: 100% !important; }
    .dash-charts {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    @media (min-width: 900px) {
        .dash-charts { grid-template-columns: 1fr 1fr; }
        .dash-chart--wide { grid-column: 1 / -1; }
    }
    .dash-chart {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.85rem;
        padding: 1rem 1rem 0.75rem;
    }
    .dash-chart h3 {
        margin: 0 0 0.35rem;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }
    html.dark .dash-chart h3, .dark .dash-chart h3 {
        color: #94a3b8;
    }
    .dash-kpi--alerta { box-shadow: inset 3px 0 0 #ff5630; }
    .dash-kpi--ok { box-shadow: inset 3px 0 0 #36b37e; }
</style>

<div class="dash-charts">
    <div class="dash-chart">
        <h3>Plantilla</h3>
        <p class="dash-kpi__hint">Activos, plazas extraordinarias y personal sin equipo.</p>
        <canvas id="chartPlantilla"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Líneas telefónicas</h3>
        <p class="dash-kpi__hint">Disponibles del inventario y asignadas al filtro.</p>
        <canvas id="chartLineasDash"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Asignación extra y propia</h3>
        <p class="dash-kpi__hint">Extra, compartido y equipo del colaborador.</p>
        <canvas id="chartExtraDash"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Carga operativa</h3>
        <p class="dash-kpi__hint">Tickets, tareas, solicitudes y auditorías del filtro.</p>
        <canvas id="chartOperacionDash"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Mantenimiento {{ $anioDash }}</h3>
        <p class="dash-kpi__hint">Programados frente a concluidos.</p>
        <canvas id="chartMantenimientoDash"></canvas>
    </div>
    <div class="dash-chart">
        <h3>Personal por gerencia</h3>
        <canvas id="chartGerenciasDash"></canvas>
    </div>
    <div class="dash-chart dash-chart--wide">
        <h3>Licencias en inventario</h3>
        <canvas id="chartLicenciasDash"></canvas>
    </div>
    <div class="dash-chart dash-chart--wide">
        <h3>Estructura</h3>
        <canvas id="chartOrganizacionDash"></canvas>
    </div>
</div>

<p class="dash-section-title">Accesos</p>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
    @if(auth()->user()->can('ver-inventario') or auth()->user()->can('transferir-inventario') or auth()->user()->can('cartas-inventario') or auth()->user()->can('asignar-inventario'))
    <a href="/inventarios" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-clipboard-list"></i></span>
        <span class="dash-quick__name">Inventario</span>
        <span class="dash-quick__hint">Asignaciones</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-empleados') or auth()->user()->can('crear-empleados') or auth()->user()->can('editar-empleados') or auth()->user()->can('borrar-empleados'))
    <a href="/empleados" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-users"></i></span>
        <span class="dash-quick__name">Personal</span>
        <span class="dash-quick__hint">Directorio</span>
    </a>
    @endif

    @can('ver-soporte')
    <a href="{{ route('tickets.index') }}" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-headset"></i></span>
        <span class="dash-quick__name">Tickets</span>
        <span class="dash-quick__hint">Mesa de ayuda</span>
    </a>
    <a href="{{ route('tickets.index', ['tab' => 'solicitudes']) }}" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-file-alt"></i></span>
        <span class="dash-quick__name">Solicitudes</span>
        <span class="dash-quick__hint">Seguimiento</span>
    </a>
    <a href="{{ route('tickets.index', ['tab' => 'tareas']) }}" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-tasks"></i></span>
        <span class="dash-quick__name">Tareas</span>
        <span class="dash-quick__hint">Pendientes</span>
    </a>
    @endcan

    @can('ver-auditorias')
    <a href="{{ route('auditorias.index') }}" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-clipboard-check"></i></span>
        <span class="dash-quick__name">Auditorías</span>
        <span class="dash-quick__hint">Control</span>
    </a>
    @endcan

    @can('ver-facturas')
    <a href="{{ route('facturas.index') }}" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-file-invoice-dollar"></i></span>
        <span class="dash-quick__name">Facturación</span>
        <span class="dash-quick__hint">Periodo actual</span>
    </a>
    @endcan

    @if(auth()->user()->can('ver-equipos') or auth()->user()->can('crear-equipos') or auth()->user()->can('editar-equipos') or auth()->user()->can('borrar-equipes'))
    <a href="/equipos" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-laptop"></i></span>
        <span class="dash-quick__name">Equipos</span>
        <span class="dash-quick__hint">Catálogo</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-reportes') or auth()->user()->can('crear-reportes') or auth()->user()->can('editar-reportes') or auth()->user()->can('borrar-reportes') or auth()->user()->can('exportar-reportes') or auth()->user()->can('ver-reportes-especificos'))
    <a href="/reportes" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-chart-bar"></i></span>
        <span class="dash-quick__name">Reportes</span>
        <span class="dash-quick__hint">Consultas</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-reportes-especificos') or auth()->user()->can('exportar-reportes-especificos'))
    <a href="/reportes-especificos" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-download"></i></span>
        <span class="dash-quick__name">Reportes específicos</span>
        <span class="dash-quick__hint">Descargar</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-usuarios') or auth()->user()->can('crear-usuarios') or auth()->user()->can('editar-usuarios') or auth()->user()->can('borrar-usuarios'))
    <a href="/usuarios" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-user-cog"></i></span>
        <span class="dash-quick__name">Usuarios</span>
        <span class="dash-quick__hint">Administración</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-rol') or auth()->user()->can('crear-rol') or auth()->user()->can('editar-rol') or auth()->user()->can('borrar-rol'))
    <a href="/roles" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-shield-alt"></i></span>
        <span class="dash-quick__name">Roles</span>
        <span class="dash-quick__hint">Permisos</span>
    </a>
    @endif

    @can('ver-presupuesto')
    <a href="/presupuesto" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-file-invoice-dollar"></i></span>
        <span class="dash-quick__name">Presupuesto</span>
        <span class="dash-quick__hint">Control</span>
    </a>
    @endcan

    @if(auth()->user()->can('ver-informe') or auth()->user()->can('buscar-informe'))
    <a href="/informe" class="dash-quick">
        <span class="dash-kpi__icon"><i class="fas fa-history"></i></span>
        <span class="dash-quick__name">Informes</span>
        <span class="dash-quick__hint">Registro</span>
    </a>
    @endif
</div>
