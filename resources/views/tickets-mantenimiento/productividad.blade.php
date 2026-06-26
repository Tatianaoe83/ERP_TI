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
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-sm font-medium dark:text-[#9CA3AF]">Total Solicitudes</p>
            <p class="text-3xl font-bold mt-2 dark:text-white">{{ $metricasProductividad['total_tickets'] }}</p>
        </div>
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-sm font-medium dark:text-[#9CA3AF]">Atendidas / Canceladas</p>
            <p class="text-3xl font-bold mt-2 dark:text-white">{{ $metricasProductividad['tickets_cerrados'] }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $metricasProductividad['total_tickets'] > 0 ? round(($metricasProductividad['tickets_cerrados'] / $metricasProductividad['total_tickets']) * 100, 1) : 0 }}% del total
            </p>
        </div>
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-sm font-medium dark:text-[#9CA3AF]">Tiempo Promedio Resolución</p>
            <p class="text-3xl font-bold mt-2 dark:text-white">{{ $metricasProductividad['tiempo_promedio_resolucion'] ?: '0' }}</p>
            <p class="text-xs text-gray-400 mt-1">horas laborales</p>
        </div>
        <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <p class="text-sm font-medium dark:text-[#9CA3AF]">Tiempo Promedio Respuesta</p>
            <p class="text-3xl font-bold mt-2 dark:text-white">{{ $metricasProductividad['tiempo_promedio_respuesta'] ?: '0' }}</p>
            <p class="text-xs text-gray-400 mt-1">horas laborales</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-lg font-semibold dark:text-white mb-3">Distribución por Estado</h3>
            <div class="h-[300px]"><canvas id="chartMantEstado"></canvas></div>
        </div>
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-lg font-semibold dark:text-white mb-3">Por Prioridad</h3>
            <div class="h-[300px]"><canvas id="chartMantPrioridad"></canvas></div>
        </div>
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-lg font-semibold dark:text-white mb-3">Por Categoría</h3>
            <div class="h-[300px]"><canvas id="chartMantCategoria"></canvas></div>
        </div>
        <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A]">
            <h3 class="text-lg font-semibold dark:text-white mb-3">Tendencia del Periodo</h3>
            <div class="h-[300px]"><canvas id="chartMantTendencia"></canvas></div>
        </div>
    </div>

    @if(!empty($metricasProductividad['tickets_por_responsable']) && count($metricasProductividad['tickets_por_responsable']))
    <div class="rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A]">
        <h3 class="text-lg font-semibold dark:text-white mb-4">Por Responsable</h3>
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
                        <td class="py-2 px-4 text-green-600">{{ $row['atendidos'] }}</td>
                        <td class="py-2 px-4 text-blue-600">{{ $row['en_proceso'] }}</td>
                        <td class="py-2 px-4 text-yellow-600">{{ $row['pendientes'] }}</td>
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
let chartMantEstado, chartMantPrioridad, chartMantCategoria, chartMantTendencia;

function obtenerDatosMantenimiento() {
    const el = document.getElementById('productividad-mantenimiento-json');
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
}

function isDarkMant() {
    return document.documentElement.classList.contains('dark');
}

function destruirGraficasMantenimiento() {
    [chartMantEstado, chartMantPrioridad, chartMantCategoria, chartMantTendencia].forEach(ch => {
        if (ch && typeof ch.destroy === 'function') ch.destroy();
    });
    chartMantEstado = chartMantPrioridad = chartMantCategoria = chartMantTendencia = null;
}

function inicializarGraficasMantenimiento() {
    const data = obtenerDatosMantenimiento();
    if (!data || !document.getElementById('chartMantEstado') || typeof Chart === 'undefined') return;

    destruirGraficasMantenimiento();
    const dark = isDarkMant();
    const texto = dark ? '#F3F4F6' : '#111827';
    const grid = dark ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.07)';

    const opts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: texto } } },
        scales: {
            x: { ticks: { color: texto, maxRotation: 45 }, grid: { color: grid } },
            y: { ticks: { color: texto }, grid: { color: grid }, beginAtZero: true }
        }
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
        options: opts
    });

    const categoria = data.tickets_por_categoria || {};
    chartMantCategoria = new Chart(document.getElementById('chartMantCategoria'), {
        type: 'bar',
        data: {
            labels: Object.keys(categoria),
            datasets: [{ label: 'Solicitudes', data: Object.values(categoria), backgroundColor: '#8B5CF6' }]
        },
        options: { ...opts, indexAxis: 'y' }
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
        options: opts
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const tab = document.querySelector('[x-show="tab === 2"]');
        if (tab && window.getComputedStyle(tab).display !== 'none') {
            inicializarGraficasMantenimiento();
        }
    }, 300);
});
</script>
