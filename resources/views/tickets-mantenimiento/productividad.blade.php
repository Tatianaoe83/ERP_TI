<div class="prod-page" id="productividad-mantenimiento-container">
<style>
    #productividad-mantenimiento-container.prod-page {
        min-height: 0;
        padding: 0;
    }
    #productividad-mantenimiento-container.prod-page > .prod-page__toolbar {
        margin-bottom: 0.65rem;
    }
    #productividad-mantenimiento-container.prod-page > .grid,
    #productividad-mantenimiento-container.prod-page > .rounded-xl {
        margin-top: 0.7rem;
    }
    #productividad-mantenimiento-container h3.text-lg,
    #productividad-mantenimiento-container h3 {
        font-size: 0.95rem;
        line-height: 1.3;
    }
    #productividad-mantenimiento-container .gap-6 { gap: 0.7rem; }
    #productividad-mantenimiento-container .gap-4 { gap: 0.65rem; }
    #productividad-mantenimiento-container .mb-6 { margin-bottom: 0.65rem; }
    #productividad-mantenimiento-container .mb-4 { margin-bottom: 0.5rem; }
    #productividad-mantenimiento-container .mt-6 { margin-top: 0.7rem; }
    #productividad-mantenimiento-container .p-6 { padding: 0.8rem 0.95rem; }
    #productividad-mantenimiento-container .h-\[300px\],
    #productividad-mantenimiento-container .h-\[260px\] { height: 210px; }
    #productividad-mantenimiento-container table th,
    #productividad-mantenimiento-container table td {
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
    }
    #productividad-mantenimiento-container .text-3xl { font-size: 1.45rem; line-height: 1.2; }
</style>
    <div class="prod-page__toolbar flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h2 class="text-base font-semibold text-gray-800 dark:text-white leading-tight">Reporte de productividad</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Métricas de mantenimientos de compras.</p>
        </div>

        @php
            $mesInicioInit = $mesInicio ?? ($mes ?? now()->month);
            $anioInicioInit = $anioInicio ?? ($anio ?? now()->year);
            $mesFinInit = $mesFin ?? ($mes ?? now()->month);
            $anioFinInit = $anioFin ?? ($anio ?? now()->year);
            $mesActual = now()->month;
            $anioActual = now()->year;
        @endphp

        <div class="flex items-center gap-4 flex-wrap" x-data="{
            mesInicio: {{ $mesInicioInit }},
            anioInicio: {{ $anioInicioInit }},
            mesFin: {{ $mesFinInit }},
            anioFin: {{ $anioFinInit }},
            cargando: false,
            validarRango() {
                this.mesInicio = parseInt(this.mesInicio);
                this.anioInicio = parseInt(this.anioInicio);
                this.mesFin = parseInt(this.mesFin);
                this.anioFin = parseInt(this.anioFin);
                if (this.anioFin < this.anioInicio) { this.anioFin = this.anioInicio; this.mesFin = this.mesInicio; }
                else if (this.anioFin === this.anioInicio && this.mesFin < this.mesInicio) { this.mesFin = this.mesInicio; }
            },
            cargarProductividad() {
                this.validarRango();
                this.cargando = true;
                const params = new URLSearchParams();
                params.append('mes_inicio', this.mesInicio);
                params.append('anio_inicio', this.anioInicio);
                params.append('mes_fin', this.mesFin);
                params.append('anio_fin', this.anioFin);
                fetch(`{{ route('tickets-mantenimiento.productividad-ajax') }}?${params.toString()}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof destruirGraficasMantenimiento === 'function') destruirGraficasMantenimiento();
                            const container = document.getElementById('productividad-mantenimiento-container');
                            if (container) container.outerHTML = data.html;
                            setTimeout(() => { if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento(); }, 80);
                        } else {
                            this.cargando = false;
                        }
                    })
                    .catch(() => { this.cargando = false; });
            }
        }">
            <div class="flex items-center gap-1 bg-gray-50 dark:bg-[#1F2937] p-1.5 rounded-xl border border-gray-200 dark:border-[#2A2F3A] shadow-sm flex-wrap">
                <span class="text-xs text-gray-500 dark:text-gray-400 px-1 font-medium">Desde</span>
                <select x-model="mesInicio" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">{{ \Carbon\Carbon::create($anioActual, $i, 1)->locale('es')->isoFormat('MMM') }}</option>
                    @endfor
                </select>
                <select x-model="anioInicio" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = $anioActual; $i >= $anioActual - 5; $i--)
                    <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                <span class="text-xs text-gray-500 dark:text-gray-400 px-1 font-medium">Hasta</span>
                <select x-model="mesFin" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">{{ \Carbon\Carbon::create($anioActual, $i, 1)->locale('es')->isoFormat('MMM') }}</option>
                    @endfor
                </select>
                <select x-model="anioFin" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = $anioActual; $i >= $anioActual - 5; $i--)
                    <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                <div x-show="cargando" class="pr-3"><i class="fas fa-spinner fa-spin text-[#2563EB]"></i></div>
            </div>
        </div>
    </div>

    <script id="productividad-mantenimiento-json" type="application/json">{!! json_encode($metricasProductividad) !!}</script>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2.5">
        <div class="rounded-lg p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-[#9CA3AF]">Total solicitudes</p>
            <p class="text-2xl font-bold mt-1 text-gray-900 dark:text-white leading-none">{{ $metricasProductividad['total_tickets'] }}</p>
        </div>
        <div class="rounded-lg p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-[#9CA3AF]">Atendidas / canceladas</p>
            <p class="text-2xl font-bold mt-1 text-gray-900 dark:text-white leading-none">{{ $metricasProductividad['tickets_cerrados'] }}</p>
            <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">
                {{ $metricasProductividad['total_tickets'] > 0 ? round(($metricasProductividad['recibidas_ya_cerradas'] / $metricasProductividad['total_tickets']) * 100, 1) : 0 }}% del total
            </p>
        </div>
        <div class="rounded-lg p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-[#9CA3AF]">Promedio resolución</p>
            <p class="text-xl font-bold mt-1 text-gray-900 dark:text-white leading-snug">{{ \App\Models\TicketMantenimiento::formatearDuracionLaboral($metricasProductividad['tiempo_promedio_resolucion'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">Lunes a viernes, 9:00–18:00</p>
        </div>
        <div class="rounded-lg p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-[#9CA3AF]">Promedio respuesta</p>
            <p class="text-xl font-bold mt-1 text-gray-900 dark:text-white leading-snug">{{ \App\Models\TicketMantenimiento::formatearDuracionLaboral($metricasProductividad['tiempo_promedio_respuesta'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">Lunes a viernes, 9:00–18:00</p>
        </div>
    </div>

    @php $sla = $metricasProductividad['metricas_sla'] ?? []; $slaResumen = $sla['resumen'] ?? []; @endphp

    <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-stopwatch text-blue-500"></i>
                    Métricas SLA por prioridad
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Metas de atención en días laborales ({{ $sla['horario'] ?? 'Lunes a Viernes, 9:00 - 18:00' }}, {{ $sla['horas_por_dia'] ?? 9 }} h/día).
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="erp-chip erp-chip--ok">
                    Cumplimiento: {{ $sla['pct_cumplimiento'] ?? 0 }}%
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ ($slaResumen['cumplidos'] ?? 0) + ($slaResumen['incumplidos'] ?? 0) + ($slaResumen['cancelados'] ?? 0) }} atendidos evaluados
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2.5 mb-3">
            @foreach($sla['por_prioridad'] ?? [] as $row)
            <div class="rounded-lg p-3 border border-gray-200 dark:border-[#2A2F3A]" style="border-top: 4px solid {{ $row['color'] }}">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $row['prioridad'] }}</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5">Meta: {{ $row['meta'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['meta_horas'] }}</p>
                    </div>
                    <span class="text-lg font-bold" style="color: {{ $row['color'] }}">{{ $row['pct_cumplimiento'] }}%</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-md px-2 py-1.5 bg-gray-100 border border-gray-200 dark:bg-[#1C1F26] dark:border-[#2A2F3A]">
                        <span class="text-gray-600 dark:text-gray-400">Total</span>
                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ $row['total'] }}</p>
                    </div>
                    <div class="rounded-md px-2 py-1.5 bg-green-50 border border-green-200 dark:bg-green-900/40 dark:border-green-800">
                        <span class="text-green-800 dark:text-green-300">Cumplidos</span>
                        <p class="font-bold text-green-800 dark:text-green-200">{{ $row['cumplidos'] }}</p>
                    </div>
                    <div class="rounded-md px-2 py-1.5 bg-red-50 border border-red-200 dark:bg-red-900/40 dark:border-red-800">
                        <span class="text-red-800 dark:text-red-300">Incumplidos</span>
                        <p class="font-bold text-red-800 dark:text-red-200">{{ $row['incumplidos'] }}</p>
                    </div>
                    <div class="rounded-md px-2 py-1.5 bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/30 dark:border-yellow-800">
                        <span class="text-yellow-800 dark:text-yellow-300">En riesgo</span>
                        <p class="font-bold text-yellow-800 dark:text-yellow-200">{{ $row['en_riesgo'] }}</p>
                    </div>
                    <div class="rounded-md px-2 py-1.5 bg-orange-50 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-800">
                        <span class="text-orange-800 dark:text-orange-300">Vencidos</span>
                        <p class="font-bold text-orange-800 dark:text-orange-200">{{ $row['vencidos'] }}</p>
                    </div>
                    <div class="rounded-md px-2 py-1.5 bg-blue-50 border border-blue-200 dark:bg-blue-900/30 dark:border-blue-800">
                        <span class="text-blue-800 dark:text-blue-300">En tiempo</span>
                        <p class="font-bold text-blue-800 dark:text-blue-200">{{ $row['en_tiempo'] }}</p>
                    </div>
                </div>
                @if(!empty($row['tiempo_promedio_texto']))
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Promedio atendidos: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $row['tiempo_promedio_texto'] }}</span>
                </p>
                @endif
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <div class="rounded-lg p-3 border border-gray-200 dark:border-[#2A2F3A]">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Cumplimiento SLA (atendidos)</h4>
                <div class="h-[210px]"><canvas id="chartMantSlaCumplimiento"></canvas></div>
            </div>
            <div class="rounded-lg p-3 border border-gray-200 dark:border-[#2A2F3A]">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Estado actual por prioridad (abiertos)</h4>
                <div class="h-[210px]"><canvas id="chartMantSlaAbiertos"></canvas></div>
            </div>
        </div>

        @if(!empty($sla['tickets_criticos']))
        <div class="mt-3 rounded-lg border border-orange-200 dark:border-orange-900/40 overflow-hidden">
            <div class="px-3 py-2 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-200 dark:border-orange-900/40">
                <h4 class="text-sm font-semibold text-orange-800 dark:text-orange-300">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Solicitudes en riesgo o vencidas ({{ count($sla['tickets_criticos']) }})
                </h4>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table class="table index-table index-table--sin-accion w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asunto</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>SLA</th>
                            <th>Transcurrido</th>
                            <th>Uso meta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sla['tickets_criticos'] as $critico)
                        <tr class="border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-[#1C1F26]">
                            <td class="py-2 px-4 font-mono font-semibold dark:text-gray-200">#{{ $critico['id'] }}</td>
                            <td class="py-2 px-4 max-w-xs truncate dark:text-gray-300">{{ $critico['asunto'] }}</td>
                            <td class="py-2 px-4 dark:text-gray-300">{{ $critico['prioridad'] }}</td>
                            <td class="py-2 px-4 dark:text-gray-300">{{ $critico['estatus'] }}</td>
                            <td class="py-2 px-4">
                                @if($critico['estado_sla'] === 'vencido')
                                    <span class="erp-chip erp-chip--alerta">Vencido</span>
                                @else
                                    <span class="erp-chip erp-chip--aviso">En riesgo</span>
                                @endif
                            </td>
                            <td class="py-2 px-4 dark:text-gray-300">{{ \App\Models\TicketMantenimiento::formatearDuracionLaboral($critico['horas_transcurridas'] ?? 0) }}</td>
                            <td class="py-2 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                        <div class="h-full rounded-full {{ $critico['porcentaje_uso'] >= 100 ? 'bg-red-500' : 'bg-yellow-500' }}"
                                            style="width: {{ min(100, $critico['porcentaje_uso']) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold dark:text-gray-300">{{ $critico['porcentaje_uso'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Distribución por estado</h3>
            <div class="h-[210px]"><canvas id="chartMantEstado"></canvas></div>
        </div>
        <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Por prioridad</h3>
            <div class="h-[210px]"><canvas id="chartMantPrioridad"></canvas></div>
        </div>
        <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Por categoría</h3>
            <div class="h-[210px]"><canvas id="chartMantCategoria"></canvas></div>
        </div>
        <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Tendencia del periodo</h3>
            <div class="h-[210px]"><canvas id="chartMantTendencia"></canvas></div>
        </div>
        <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Por área</h3>
            <div class="h-[210px]"><canvas id="chartMantArea"></canvas></div>
        </div>
        <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Carga por responsable</h3>
            <div class="h-[210px]"><canvas id="chartMantResponsable"></canvas></div>
        </div>
    </div>

    @if(!empty($metricasProductividad['tickets_por_responsable']) && count($metricasProductividad['tickets_por_responsable']))
    <div class="rounded-xl p-3.5 border border-gray-200 dark:border-[#2A2F3A]">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Por responsable</h3>
        <div class="index-page__table-wrap table-responsive">
            <table class="table index-table index-table--sin-accion w-full">
                <thead>
                    <tr>
                        <th>Responsable</th>
                        <th>Total</th>
                        <th>Atendidos</th>
                        <th>En proceso</th>
                        <th>Pendientes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metricasProductividad['tickets_por_responsable'] as $row)
                    <tr>
                        <td>{{ $row['nombre'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td><span class="erp-chip erp-chip--ok">{{ $row['atendidos'] }}</span></td>
                        <td><span class="erp-chip erp-chip--info">{{ $row['en_proceso'] }}</span></td>
                        <td><span class="erp-chip erp-chip--aviso">{{ $row['pendientes'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script src="{{ asset('js/ux-charts.js') }}?v=20261022l"></script>
<script>
let chartMantEstado, chartMantPrioridad, chartMantCategoria, chartMantTendencia, chartMantSlaCumplimiento, chartMantSlaAbiertos, chartMantArea, chartMantResponsable;

function obtenerDatosMantenimiento() {
    const el = document.getElementById('productividad-mantenimiento-json');
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
}

function isDarkMant() {
    return document.documentElement.classList.contains('dark');
}

function destruirGraficasMantenimiento() {
    [chartMantEstado, chartMantPrioridad, chartMantCategoria, chartMantTendencia, chartMantSlaCumplimiento, chartMantSlaAbiertos, chartMantArea, chartMantResponsable].forEach(ch => {
        if (ch && typeof ch.destroy === 'function') ch.destroy();
    });
    chartMantEstado = chartMantPrioridad = chartMantCategoria = chartMantTendencia = chartMantSlaCumplimiento = chartMantSlaAbiertos = chartMantArea = chartMantResponsable = null;
}

function inicializarGraficasMantenimiento(intento) {
    intento = intento || 0;
    if (typeof Chart === 'undefined') {
        if (intento < 25) {
            setTimeout(function () { inicializarGraficasMantenimiento(intento + 1); }, 80);
        }
        return;
    }
    const data = obtenerDatosMantenimiento();
    if (!data || !document.getElementById('chartMantEstado')) return;

    destruirGraficasMantenimiento();
    const dark = isDarkMant();
    const texto = dark ? '#CBD5E1' : '#334155';
    if (window.UxCharts) {
        UxCharts.aplicar(Chart);
    }

    function colorPorNombre(nombre, indice) {
        const n = String(nombre || '').toLowerCase();
        if (n.includes('cancel')) return '#A3B3C9';
        if (n.includes('atend') || n.includes('cerr') || n.includes('resuel')) return '#36B37E';
        if (n.includes('paus')) return '#FFAB00';
        if (n.includes('proces') || n.includes('progreso')) return '#4C9AFF';
        if (n.includes('pend')) return '#FF5630';
        if (n.includes('alta')) return '#FF5630';
        if (n.includes('media')) return '#FFAB00';
        if (n.includes('baja')) return '#36B37E';
        const paleta = (window.UxCharts && UxCharts.paleta) || ['#0052CC', '#6554C0', '#A3B3C9'];
        return paleta[indice % paleta.length];
    }

    // Chart.js escribe type/axis/position dentro del objeto de opciones que recibe,
    // así que cada gráfica necesita el suyo: compartirlo contamina las escalas.
    // Todas las series son conteos de solicitudes: el eje de valores no debe fraccionarse.
    const opts = (horizontal) => {
        const scales = window.UxCharts ? UxCharts.escala(!!horizontal) : {
            x: { beginAtZero: true },
            y: { beginAtZero: true }
        };
        ['x', 'y'].forEach((eje) => {
            if (scales[eje] && scales[eje].ticks) {
                scales[eje].ticks.stepSize = 1;
                scales[eje].ticks.precision = 0;
            }
        });
        return {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            plugins: { legend: { labels: { color: texto, usePointStyle: true, boxWidth: 8 } } },
            scales: scales
        };
    };

    const optsApiladas = () => {
        const base = opts();
        base.scales.x.stacked = true;
        base.scales.y.stacked = true;

        return base;
    };

    const estado = data.distribucion_estado || {};
    const etiquetasEstado = Object.keys(estado);
    const canvasEstado = document.getElementById('chartMantEstado');
    chartMantEstado = new Chart(canvasEstado, {
        type: 'doughnut',
        plugins: window.UxCharts ? [UxCharts.centro] : [],
        data: {
            labels: etiquetasEstado,
            datasets: [{
                data: Object.values(estado),
                backgroundColor: etiquetasEstado.map(colorPorNombre),
                hoverOffset: 8
            }]
        },
        options: window.UxCharts ? UxCharts.anillo() : { responsive: true, maintainAspectRatio: false }
    });

    const prioridad = data.tickets_por_prioridad || {};
    const etiquetasPrioridad = Object.keys(prioridad);
    const canvasPrioridad = document.getElementById('chartMantPrioridad');
    chartMantPrioridad = new Chart(canvasPrioridad, {
        type: 'bar',
        data: {
            labels: etiquetasPrioridad,
            datasets: [{
                label: 'Solicitudes',
                data: Object.values(prioridad),
                backgroundColor: etiquetasPrioridad.map(colorPorNombre),
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 28,
                categoryPercentage: 0.72,
                barPercentage: 0.86
            }]
        },
        options: opts(false)
    });

    const categoria = data.tickets_por_categoria || {};
    const etiquetasCategoria = Object.keys(categoria);
    const canvasCategoria = document.getElementById('chartMantCategoria');
    chartMantCategoria = new Chart(canvasCategoria, {
        type: 'bar',
        data: {
            labels: etiquetasCategoria,
            datasets: [{
                label: 'Solicitudes',
                data: Object.values(categoria),
                backgroundColor: window.UxCharts ? UxCharts.tonos(etiquetasCategoria.length) : '#0052CC',
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 22,
                categoryPercentage: 0.72,
                barPercentage: 0.86
            }]
        },
        options: opts(true)
    });

    const creados = data.creados_por_dia || {};
    const resueltos = data.resueltos_por_dia || {};
    const labels = Object.keys(creados);
    const lienzoTendencia = document.getElementById('chartMantTendencia');
    const serieCreada = window.UxCharts ? UxCharts.linea(lienzoTendencia, '#6554C0') : { borderColor: '#6554C0', fill: true, tension: 0.35 };
    const serieAtendida = window.UxCharts ? UxCharts.linea(lienzoTendencia, '#0052CC') : { borderColor: '#0052CC', fill: true, tension: 0.35 };
    chartMantTendencia = new Chart(lienzoTendencia, {
        type: 'line',
        data: {
            labels: labels.map(d => d.slice(5)),
            datasets: [
                Object.assign({ label: 'Creadas', data: labels.map(k => creados[k] || 0) }, serieCreada),
                Object.assign({ label: 'Atendidas', data: labels.map(k => resueltos[k] || 0) }, serieAtendida)
            ]
        },
        options: opts()
    });

    const slaData = data.metricas_sla || {};
    const slaPrioridades = slaData.por_prioridad || [];

    if (document.getElementById('chartMantSlaCumplimiento') && slaPrioridades.length) {
        chartMantSlaCumplimiento = new Chart(document.getElementById('chartMantSlaCumplimiento'), {
            type: 'bar',
            data: {
                labels: slaPrioridades.map(r => r.prioridad),
                datasets: [
                    {
                        label: 'Cumplidos',
                        data: slaPrioridades.map(r => r.cumplidos),
                        backgroundColor: '#36B37E',
                        borderRadius: 6,
                        maxBarThickness: 28
                    },
                    {
                        label: 'Incumplidos',
                        data: slaPrioridades.map(r => r.incumplidos),
                        backgroundColor: '#FF5630',
                        borderRadius: 6,
                        maxBarThickness: 28
                    },
                ],
            },
            options: optsApiladas(),
        });
    }

    if (document.getElementById('chartMantSlaAbiertos') && slaPrioridades.length) {
        chartMantSlaAbiertos = new Chart(document.getElementById('chartMantSlaAbiertos'), {
            type: 'bar',
            data: {
                labels: slaPrioridades.map(r => r.prioridad),
                datasets: [
                    { label: 'En tiempo', data: slaPrioridades.map(r => r.en_tiempo), backgroundColor: '#4C9AFF', borderRadius: 6, maxBarThickness: 28 },
                    { label: 'En riesgo', data: slaPrioridades.map(r => r.en_riesgo), backgroundColor: '#FFAB00', borderRadius: 6, maxBarThickness: 28 },
                    { label: 'Vencidos', data: slaPrioridades.map(r => r.vencidos), backgroundColor: '#FF5630', borderRadius: 6, maxBarThickness: 28 },
                ],
            },
            options: optsApiladas(),
        });
    }

    const areas = data.tickets_por_area || {};
    const etiquetasArea = Object.keys(areas);
    const canvasArea = document.getElementById('chartMantArea');
    if (canvasArea) {
        const filasArea = etiquetasArea.length ? etiquetasArea : ['Sin área'];
        chartMantArea = new Chart(canvasArea, {
            type: 'bar',
            data: {
                labels: filasArea,
                datasets: [{
                    label: 'Solicitudes',
                    data: etiquetasArea.length ? filasArea.map(k => areas[k] || 0) : [0],
                    backgroundColor: window.UxCharts ? UxCharts.tonos(filasArea.length) : '#0052CC',
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 22
                }]
            },
            options: opts(true)
        });
    }

    const responsables = data.tickets_por_responsable || [];
    const canvasResponsable = document.getElementById('chartMantResponsable');
    if (canvasResponsable) {
        const filasResp = responsables.length ? responsables : [{ nombre: 'Sin responsable', atendidos: 0, en_proceso: 0, pendientes: 0 }];
        chartMantResponsable = new Chart(canvasResponsable, {
            type: 'bar',
            data: {
                labels: filasResp.map(r => r.nombre),
                datasets: [
                    { label: 'Atendidos', data: filasResp.map(r => r.atendidos || 0), backgroundColor: '#36B37E', borderRadius: 6, maxBarThickness: 18 },
                    { label: 'En proceso', data: filasResp.map(r => r.en_proceso || 0), backgroundColor: '#4C9AFF', borderRadius: 6, maxBarThickness: 18 },
                    { label: 'Pendientes', data: filasResp.map(r => r.pendientes || 0), backgroundColor: '#FF5630', borderRadius: 6, maxBarThickness: 18 }
                ]
            },
            options: opts(true)
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            const tab = document.getElementById('productividad-mantenimiento-tab');
            if (tab && !tab.hidden) {
                inicializarGraficasMantenimiento();
            }
        }, 300);
    });
} else {
    setTimeout(() => {
        const tab = document.getElementById('productividad-mantenimiento-tab');
        if (tab && !tab.hidden) {
            inicializarGraficasMantenimiento();
        }
    }, 80);
}

const observerDarkModeMant = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            setTimeout(() => {
                const container = document.getElementById('productividad-mantenimiento-container');
                if (container && typeof inicializarGraficasMantenimiento === 'function') {
                    inicializarGraficasMantenimiento();
                }
            }, 50);
        }
    });
});
observerDarkModeMant.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
</script>
