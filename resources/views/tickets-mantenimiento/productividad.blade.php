<div class="space-y-6 min-h-screen p-6" id="productividad-mantenimiento-container">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte de Productividad</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Métricas de mantenimientos de compras.</p>
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <p class="text-sm font-medium text-gray-600 dark:text-[#9CA3AF]">Total Solicitudes</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $metricasProductividad['total_tickets'] }}</p>
        </div>
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <p class="text-sm font-medium text-gray-600 dark:text-[#9CA3AF]">Atendidas / Canceladas</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $metricasProductividad['tickets_cerrados'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ $metricasProductividad['total_tickets'] > 0 ? round(($metricasProductividad['tickets_cerrados'] / $metricasProductividad['total_tickets']) * 100, 1) : 0 }}% del total
            </p>
        </div>
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <p class="text-sm font-medium text-gray-600 dark:text-[#9CA3AF]">Tiempo Promedio Resolución</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $metricasProductividad['tiempo_promedio_resolucion'] ?: '0' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">horas laborales</p>
        </div>
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <p class="text-sm font-medium text-gray-600 dark:text-[#9CA3AF]">Tiempo Promedio Respuesta</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $metricasProductividad['tiempo_promedio_respuesta'] ?: '0' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">horas laborales</p>
        </div>
    </div>

    @php $sla = $metricasProductividad['metricas_sla'] ?? []; $slaResumen = $sla['resumen'] ?? []; @endphp

    <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-stopwatch text-blue-500"></i>
                    Métricas SLA por Prioridad
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Metas de atención en días laborales ({{ $sla['horario'] ?? 'Lunes a Viernes, 9:00 - 18:00' }}, {{ $sla['horas_por_dia'] ?? 9 }} h/día).
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm px-3 py-1.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 font-semibold">
                    Cumplimiento: {{ $sla['pct_cumplimiento'] ?? 0 }}%
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ ($slaResumen['cumplidos'] ?? 0) + ($slaResumen['incumplidos'] ?? 0) }} atendidos evaluados
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @foreach($sla['por_prioridad'] ?? [] as $row)
            <div class="rounded-lg p-4 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#242933]" style="border-top: 4px solid {{ $row['color'] }}">
                <div class="flex justify-between items-start mb-3">
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
                @if($row['tiempo_promedio_dias'] > 0)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                    Promedio atendidos: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $row['tiempo_promedio_dias'] }} días laborales</span>
                </p>
                @endif
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-lg p-4 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#242933]">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Cumplimiento SLA (atendidos)</h4>
                <div class="h-[260px]"><canvas id="chartMantSlaCumplimiento"></canvas></div>
            </div>
            <div class="rounded-lg p-4 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#242933]">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Estado actual por prioridad (abiertos)</h4>
                <div class="h-[260px]"><canvas id="chartMantSlaAbiertos"></canvas></div>
            </div>
        </div>

        @if(!empty($sla['tickets_criticos']))
        <div class="mt-6 rounded-lg border border-orange-200 dark:border-orange-900/40 overflow-hidden bg-gray-50 dark:bg-[#1C1F26]">
            <div class="px-4 py-3 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-200 dark:border-orange-900/40">
                <h4 class="text-sm font-semibold text-orange-800 dark:text-orange-300">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Solicitudes en riesgo o vencidas ({{ count($sla['tickets_criticos']) }})
                </h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#242933] text-left text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2 px-4">ID</th>
                            <th class="py-2 px-4">Asunto</th>
                            <th class="py-2 px-4">Prioridad</th>
                            <th class="py-2 px-4">Estado</th>
                            <th class="py-2 px-4">SLA</th>
                            <th class="py-2 px-4">Transcurrido</th>
                            <th class="py-2 px-4">Uso meta</th>
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
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Vencido</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">En riesgo</span>
                                @endif
                            </td>
                            <td class="py-2 px-4 text-gray-600 dark:text-gray-400">{{ $critico['meta_texto'] }}</td>
                            <td class="py-2 px-4 dark:text-gray-300">{{ $critico['dias_laborales_transcurridos'] }} días</td>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Distribución por Estado</h3>
            <div class="h-[300px]"><canvas id="chartMantEstado"></canvas></div>
        </div>
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Por Prioridad</h3>
            <div class="h-[300px]"><canvas id="chartMantPrioridad"></canvas></div>
        </div>
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Por Categoría</h3>
            <div class="h-[300px]"><canvas id="chartMantCategoria"></canvas></div>
        </div>
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Tendencia del Periodo</h3>
            <div class="h-[300px]"><canvas id="chartMantTendencia"></canvas></div>
        </div>
    </div>

    @if(!empty($metricasProductividad['tickets_por_responsable']) && count($metricasProductividad['tickets_por_responsable']))
    <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Por Responsable</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4">Responsable</th>
                        <th class="py-2 px-4">Total</th>
                        <th class="py-2 px-4">Atendidos</th>
                        <th class="py-2 px-4">En proceso</th>
                        <th class="py-2 px-4">Pendientes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metricasProductividad['tickets_por_responsable'] as $row)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 pr-4 font-medium dark:text-gray-200">{{ $row['nombre'] }}</td>
                        <td class="py-2 px-4 dark:text-gray-300">{{ $row['total'] }}</td>
                        <td class="py-2 px-4 text-green-600 dark:text-green-400">{{ $row['atendidos'] }}</td>
                        <td class="py-2 px-4 text-blue-600 dark:text-blue-400">{{ $row['en_proceso'] }}</td>
                        <td class="py-2 px-4 text-yellow-600 dark:text-yellow-400">{{ $row['pendientes'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let chartMantEstado, chartMantPrioridad, chartMantCategoria, chartMantTendencia, chartMantSlaCumplimiento, chartMantSlaAbiertos;

function obtenerDatosMantenimiento() {
    const el = document.getElementById('productividad-mantenimiento-json');
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
}

function isDarkMant() {
    return document.documentElement.classList.contains('dark');
}

function destruirGraficasMantenimiento() {
    [chartMantEstado, chartMantPrioridad, chartMantCategoria, chartMantTendencia, chartMantSlaCumplimiento, chartMantSlaAbiertos].forEach(ch => {
        if (ch && typeof ch.destroy === 'function') ch.destroy();
    });
    chartMantEstado = chartMantPrioridad = chartMantCategoria = chartMantTendencia = chartMantSlaCumplimiento = chartMantSlaAbiertos = null;
}

function inicializarGraficasMantenimiento() {
    const data = obtenerDatosMantenimiento();
    if (!data || !document.getElementById('chartMantEstado') || typeof Chart === 'undefined') return;

    destruirGraficasMantenimiento();
    const dark = isDarkMant();
    const texto = dark ? '#F3F4F6' : '#111827';
    const grid = dark ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.07)';

    // Chart.js escribe type/axis/position dentro del objeto de opciones que recibe,
    // así que cada gráfica necesita el suyo: compartirlo contamina las escalas.
    // Todas las series son conteos de solicitudes: el eje de valores no debe fraccionarse.
    const ticksEnteros = { stepSize: 1, precision: 0 };

    const opts = () => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: texto } } },
        scales: {
            x: { ticks: { color: texto, maxRotation: 45, ...ticksEnteros }, grid: { color: grid }, beginAtZero: true },
            y: { ticks: { color: texto, ...ticksEnteros }, grid: { color: grid }, beginAtZero: true }
        }
    });

    const optsApiladas = () => {
        const base = opts();
        base.scales.x.stacked = true;
        base.scales.y.stacked = true;

        return base;
    };

    const estado = data.distribucion_estado || {};
    chartMantEstado = new Chart(document.getElementById('chartMantEstado'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(estado),
            datasets: [{ data: Object.values(estado), backgroundColor: ['#EAB308', '#3B82F6', '#8B5CF6', '#22C55E', '#EF4444'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: texto } } } }
    });

    const prioridad = data.tickets_por_prioridad || {};
    chartMantPrioridad = new Chart(document.getElementById('chartMantPrioridad'), {
        type: 'bar',
        data: {
            labels: Object.keys(prioridad),
            datasets: [{ label: 'Solicitudes', data: Object.values(prioridad), backgroundColor: '#3B82F6' }]
        },
        options: opts()
    });

    const categoria = data.tickets_por_categoria || {};
    chartMantCategoria = new Chart(document.getElementById('chartMantCategoria'), {
        type: 'bar',
        data: {
            labels: Object.keys(categoria),
            datasets: [{ label: 'Solicitudes', data: Object.values(categoria), backgroundColor: '#8B5CF6' }]
        },
        options: { ...opts(), indexAxis: 'y' }
    });

    const creados = data.creados_por_dia || {};
    const resueltos = data.resueltos_por_dia || {};
    const labels = Object.keys(creados);
    chartMantTendencia = new Chart(document.getElementById('chartMantTendencia'), {
        type: 'line',
        data: {
            labels: labels.map(d => d.slice(5)),
            datasets: [
                { label: 'Creadas', data: labels.map(k => creados[k] || 0), borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3 },
                { label: 'Atendidas', data: labels.map(k => resueltos[k] || 0), borderColor: '#22C55E', backgroundColor: 'rgba(34,197,94,0.1)', fill: true, tension: 0.3 }
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
                        backgroundColor: '#22C55E',
                    },
                    {
                        label: 'Incumplidos',
                        data: slaPrioridades.map(r => r.incumplidos),
                        backgroundColor: '#EF4444',
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
                    { label: 'En tiempo', data: slaPrioridades.map(r => r.en_tiempo), backgroundColor: '#3B82F6' },
                    { label: 'En riesgo', data: slaPrioridades.map(r => r.en_riesgo), backgroundColor: '#EAB308' },
                    { label: 'Vencidos', data: slaPrioridades.map(r => r.vencidos), backgroundColor: '#EF4444' },
                ],
            },
            options: optsApiladas(),
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const tab = document.querySelector('[x-show="tab === 2"]');
        if (tab && window.getComputedStyle(tab).display !== 'none') {
            inicializarGraficasMantenimiento();
        }
    }, 300);
});

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
