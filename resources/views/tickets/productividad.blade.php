<div class="space-y-6 min-h-screen p-6" id="productividad-container" x-data="{ activeTab: sessionStorage.getItem('prodTab') || 'general' }" x-init="$watch('activeTab', val => sessionStorage.setItem('prodTab', val))">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte de Productividad</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Análisis de rendimiento y métricas de tiempos.</p>
        </div>

        @php
            $mesInicioInit  = $mesInicio  ?? ($mes  ?? now()->month);
            $anioInicioInit = $anioInicio ?? ($anio ?? now()->year);
            $mesFinInit     = $mesFin     ?? ($mes  ?? now()->month);
            $anioFinInit    = $anioFin    ?? ($anio ?? now()->year);
            $mesActual      = now()->month;
            $anioActual     = now()->year;
        @endphp
        <div class="flex items-center gap-4 flex-wrap"
            x-data="{
                mesInicio:  {{ $mesInicioInit }},
                anioInicio: {{ $anioInicioInit }},
                mesFin:     {{ $mesFinInit }},
                anioFin:    {{ $anioFinInit }},
                cargando:   false,
                validarRango() {
                    this.mesInicio = parseInt(this.mesInicio);
                    this.anioInicio = parseInt(this.anioInicio);
                    this.mesFin = parseInt(this.mesFin);
                    this.anioFin = parseInt(this.anioFin);

                    const mesActual = {{ $mesActual }};
                    const anioActual = {{ $anioActual }};

                    if (
                        this.anioInicio > anioActual ||
                        (this.anioInicio === anioActual && this.mesInicio > mesActual)
                    ) {
                        this.anioInicio = anioActual;
                        this.mesInicio = mesActual;
                    }

                    if (
                        this.anioFin > anioActual ||
                        (this.anioFin === anioActual && this.mesFin > mesActual)
                    ) {
                        this.anioFin = anioActual;
                        this.mesFin = mesActual;
                    }

                    if (this.anioFin < this.anioInicio) {
                        this.anioFin = this.anioInicio;
                        this.mesFin = this.mesInicio;
                    } else if (
                        this.anioFin === this.anioInicio &&
                        this.mesFin < this.mesInicio
                    ) {
                        this.mesFin = this.mesInicio;
                    }
                },
                cargarProductividad() {
                    this.validarRango();
                    this.cargando = true;
                    const params = new URLSearchParams();
                    params.append('mes_inicio',  this.mesInicio);
                    params.append('anio_inicio', this.anioInicio);
                    params.append('mes_fin',     this.mesFin);
                    params.append('anio_fin',    this.anioFin);
                    fetch(`{{ route('tickets.productividad-ajax') }}?${params.toString()}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (typeof destruirGraficasProductividad === 'function') {
                                    destruirGraficasProductividad();
                                }
                                const container = document.getElementById('productividad-container');
                                if (container) {
                                    container.outerHTML = data.html;
                                }
                                const nuevo = document.getElementById('productividad-container');
                                if (nuevo && window.Alpine && typeof Alpine.initTree === 'function') {
                                    try {
                                        Alpine.initTree(nuevo);
                                    } catch (e) {
                                        console.warn('Alpine.initTree productividad:', e);
                                    }
                                }
                                setTimeout(function() {
                                    const filtro = document.querySelector('#productividad-container [x-data*=mesInicio]');
                                    if (filtro && window.Alpine && typeof Alpine.$data === 'function') {
                                        try {
                                            Alpine.$data(filtro).cargando = false;
                                        } catch (e) {}
                                    }
                                    if (sessionStorage.getItem('prodTab') === 'general' || !sessionStorage.getItem('prodTab')) {
                                        if (typeof inicializarGraficas === 'function') {
                                            inicializarGraficas();
                                        }
                                        if (typeof inicializarGraficasEmpleados === 'function') {
                                            inicializarGraficasEmpleados();
                                        }
                                    }
                                }, 80);
                            } else {
                                this.cargando = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.cargando = false;
                        });
                },
                getExportUrl() {
                    this.validarRango();
                    const base = '{{ route('tickets.exportar-reporte-mensual-excel') }}';
                    return `${base}?mes_inicio=${this.mesInicio}&anio_inicio=${this.anioInicio}&mes_fin=${this.mesFin}&anio_fin=${this.anioFin}`;
                }
            }">

            {{-- Filtro rango de meses (siempre visible) --}}
            <div class="flex items-center gap-1 bg-gray-50 dark:bg-[#1F2937] p-1.5 rounded-xl border border-gray-200 dark:border-[#2A2F3A] shadow-sm flex-wrap">
                <span class="text-xs text-gray-500 dark:text-gray-400 px-1 font-medium">Desde</span>
                <select x-model="mesInicio" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @php
                        $mesesOrdenados = [];
                        for ($i = $mesActual; $i <= 12; $i++) {
                            $mesesOrdenados[] = $i;
                        }
                        for ($i = 1; $i < $mesActual; $i++) {
                            $mesesOrdenados[] = $i;
                        }
                    @endphp
                    @foreach($mesesOrdenados as $i)
                        <option value="{{ $i }}">{{ \Carbon\Carbon::create($anioActual, $i, 1)->locale('es')->isoFormat('MMM') }}</option>
                    @endforeach
                </select>
                <select x-model="anioInicio" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = $anioActual; $i >= $anioActual - 5; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                <span class="text-xs text-gray-500 dark:text-gray-400 px-1 font-medium">Hasta</span>
                <select x-model="mesFin" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @foreach($mesesOrdenados as $i)
                        <option value="{{ $i }}">{{ \Carbon\Carbon::create($anioActual, $i, 1)->locale('es')->isoFormat('MMM') }}</option>
                    @endforeach
                </select>
                <select x-model="anioFin" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-2 pr-6 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = $anioActual; $i >= $anioActual - 5; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                <div x-show="cargando" class="pr-3">
                    <i class="fas fa-spinner fa-spin text-[#2563EB]"></i>
                </div>
            </div>

            <a :href="getExportUrl()"
                class="px-4 py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm hover:shadow flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white">
                <i class="fas fa-file-excel"></i> Exportar
            </a>
        </div>
    </div>

    <div class="border-b border-gray-200 dark:border-[#2A2F3A] mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'general'; setTimeout(() => { inicializarGraficas(); inicializarGraficasEmpleados(); }, 100);"
                :class="activeTab === 'general' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <i class="fas fa-chart-pie"></i> Resumen de Tickets
            </button>

            <button @click="activeTab = 'solicitudes'"
                :class="activeTab === 'solicitudes' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                <i class="fas fa-stopwatch"></i> Métricas de Solicitudes (Tiempos)
            </button>
        </nav>
    </div>

    <div class="relative" x-data="{ modalAbierto: false, infoModal: {} }">

        <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6">

            <script id="productividad-json-data" type="application/json">
                {!! json_encode($metricasProductividad) !!}
            </script>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium dark:text-[#9CA3AF]">Total de Tickets</p>
                            <p class="text-3xl font-bold mt-2 dark:text-white">{{ $metricasProductividad['total_tickets'] }}</p>
                        </div>
                        <div class="rounded-full p-4 bg-blue-500/15">
                            <i class="fas fa-ticket-alt text-blue-500 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium dark:text-[#9CA3AF]">Tickets Cerrados</p>
                            <p class="text-3xl font-bold mt-2 dark:text-white">{{ $metricasProductividad['tickets_cerrados'] }}</p>
                            <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">
                                {{ $metricasProductividad['total_tickets'] > 0
                                    ? round(($metricasProductividad['tickets_cerrados'] / $metricasProductividad['total_tickets']) * 100, 1)
                                    : 0 }}% del total
                            </p>
                        </div>
                        <div class="rounded-full p-4 bg-green-500/15">
                            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium dark:text-[#9CA3AF]">Tiempo Promedio Resolución</p>
                            <p class="text-3xl font-bold mt-2 dark:text-white">
                                {{ $metricasProductividad['tiempo_promedio_resolucion'] > 0
                                    ? number_format($metricasProductividad['tiempo_promedio_resolucion'], 1)
                                    : '0' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">horas laborales</p>
                        </div>
                        <div class="rounded-full p-4 bg-purple-500/15">
                            <i class="fas fa-clock text-purple-500 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium dark:text-[#9CA3AF]">Tiempo Promedio Respuesta</p>
                            <p class="text-3xl font-bold mt-2 dark:text-white">
                                {{ $metricasProductividad['tiempo_promedio_respuesta'] > 0
                                    ? number_format($metricasProductividad['tiempo_promedio_respuesta'], 1)
                                    : '0' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">horas laborales</p>
                        </div>
                        <div class="rounded-full p-4 bg-orange-500/15">
                            <i class="fas fa-hourglass-half text-orange-500 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-8 border-gray-200 dark:border-[#2A2F3A]">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="group rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white via-gray-50/50 to-white dark:from-[#111827]/50 dark:via-[#0d1117]/40 dark:to-[#0B0F14]/70 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/45 hover:border-blue-200/90 dark:hover:border-blue-500/35 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-blue-500/20 dark:focus-within:ring-blue-400/25 focus-within:border-blue-300/80 dark:focus-within:border-blue-500/40">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-lg font-semibold dark:text-white">Distribución por Estado</h3>
                        <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                    </div>
                    <div class="relative h-[300px] rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/40 dark:bg-black/25 group-hover:bg-gray-50/70 dark:group-hover:bg-black/35">
                        <canvas id="chartEstado" role="img" aria-label="Distribución de tickets por estado"></canvas>
                    </div>
                </div>

                <div class="group rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white via-gray-50/50 to-white dark:from-[#111827]/50 dark:via-[#0d1117]/40 dark:to-[#0B0F14]/70 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/45 hover:border-emerald-200/90 dark:hover:border-emerald-500/35 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-emerald-500/20 dark:focus-within:ring-emerald-400/25 focus-within:border-emerald-300/80 dark:focus-within:border-emerald-500/40">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-lg font-semibold dark:text-white">Tickets Creados vs Resueltos</h3>
                        <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                    </div>
                    <div class="relative h-[300px] rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/40 dark:bg-black/25 group-hover:bg-gray-50/70 dark:group-hover:bg-black/35">
                        <canvas id="chartResueltosPorDia" role="img" aria-label="Tickets creados y resueltos por día"></canvas>
                    </div>
                </div>
            </div>

            <!-- Comparación de Tiempos-->
            <div class="group rounded-xl shadow-md p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white to-gray-50/60 dark:from-[#111827]/45 dark:to-[#0B0F14]/70 transition-all duration-300 ease-out hover:shadow-xl hover:shadow-amber-100/30 dark:hover:shadow-black/50 hover:border-amber-200/70 dark:hover:border-amber-500/30 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-amber-500/20 dark:focus-within:ring-amber-400/25">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Comparación de Tiempos</h3>
                        <p class="text-sm mt-1 text-gray-500 dark:text-gray-400">Evolución de tiempos promedio de respuesta, resolución y total</p>
                    </div>
                    <span class="shrink-0 self-start text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                </div>
                <div class="h-80 rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/30 dark:bg-black/20 group-hover:bg-gray-50/60 dark:group-hover:bg-black/30">
                    <canvas id="chartComparacionTiempos6Meses" role="img" aria-label="Comparación de tiempos en los últimos meses"></canvas>
                </div>
            </div>

            <div class="group rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white via-gray-50/50 to-white dark:from-[#111827]/50 dark:via-[#0d1117]/40 dark:to-[#0B0F14]/70 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/45 hover:border-indigo-200/90 dark:hover:border-indigo-500/35 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-indigo-500/20 dark:focus-within:ring-indigo-400/25">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h3 class="text-lg font-semibold dark:text-white">Distribución por Clasificación (En Progreso y Cerrados)</h3>
                    <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                </div>
                <div class="relative h-[300px] rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/40 dark:bg-black/25 group-hover:bg-gray-50/70 dark:group-hover:bg-black/35">
                    <canvas id="chartClasificacion" role="img" aria-label="Distribución por clasificación de ticket"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="group rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white via-gray-50/50 to-white dark:from-[#111827]/50 dark:via-[#0d1117]/40 dark:to-[#0B0F14]/70 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/45 hover:border-rose-200/80 dark:hover:border-rose-500/35 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-rose-500/20 dark:focus-within:ring-rose-400/25">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-lg font-semibold dark:text-white">Tickets por Prioridad</h3>
                        <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                    </div>
                    <div class="relative h-[300px] rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/40 dark:bg-black/25 group-hover:bg-gray-50/70 dark:group-hover:bg-black/35">
                        <canvas id="chartPrioridad" role="img" aria-label="Tickets por prioridad"></canvas>
                    </div>
                </div>

                <div class="group rounded-xl p-6 border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white via-gray-50/50 to-white dark:from-[#111827]/50 dark:via-[#0d1117]/40 dark:to-[#0B0F14]/70 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/45 hover:border-violet-200/90 dark:hover:border-violet-500/35 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-violet-500/20 dark:focus-within:ring-violet-400/25">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-lg font-semibold dark:text-white">Distribución por Tipo de Ticket (Top 12)</h3>
                        <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                    </div>
                    <div class="relative h-[300px] rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/40 dark:bg-black/25 group-hover:bg-gray-50/70 dark:group-hover:bg-black/35">
                        <canvas id="chartTipoTicket" role="img" aria-label="Distribución por tipo de ticket"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-lg shadow-md p-6 border border-gray-200 dark:border-[#2A2F3A]">
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Rendimiento por Responsable TI</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#2A2F3A]">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Responsable</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Cerrados</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">En Progreso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Pendientes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Problemas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Servicios</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tasa de Cierre</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                            @forelse($metricasProductividad['tickets_por_responsable'] as $responsable)
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#1F2937]/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium dark:text-gray-200">{{ $responsable['nombre'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm dark:text-gray-300">{{ $responsable['total'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        {{ $responsable['cerrados'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        {{ $responsable['en_progreso'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        {{ $responsable['pendientes'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                        {{ $responsable['problemas'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $responsable['servicios'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($responsable['total'] > 0)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ round(($responsable['cerrados'] / $responsable['total']) * 100, 1) }}%
                                    </span>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Incidencias por Gerencia del Solicitante -->
            <div class="rounded-lg shadow-md p-6 border border-gray-200 dark:border-[#2A2F3A]">
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Incidencias por Gerencia del Solicitante</h3>
                <p class="text-sm mb-4 text-gray-500 dark:text-gray-400">Distribución de tickets según la gerencia del empleado solicitante</p>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#2A2F3A]">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Gerencia</th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipo de Incidencia (Top 5)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                            @forelse($metricasProductividad['tickets_por_gerencia_solicitante'] ?? [] as $gerencia)
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#1F2937]/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium dark:text-gray-200">{{ $gerencia['gerencia'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $gerencia['total'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if(!empty($gerencia['tertipos']))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($gerencia['tertipos'] as $tertipo => $cantidad)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                            {{ $tertipo }}
                                            <span class="ml-1 px-1.5 py-0.5 bg-purple-200 dark:bg-purple-800 rounded-full text-purple-900 dark:text-purple-100">{{ $cantidad }}</span>
                                        </span>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs italic">Sin clasificar</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Incidencias por Responsable TI Asignado (Tabla Pivotizada) -->
            <div class="rounded-lg shadow-md p-6 border border-gray-200 dark:border-[#2A2F3A]">
                <h3 class="text-lg font-semibold mb-2 dark:text-white">Incidencias por Responsable TI Asignado</h3>
                <p class="text-sm mb-6 text-gray-500 dark:text-gray-400">Matriz de distribución de tickets: Tipos → Subtipos vs Responsables TI Asignados</p>
                
                <!-- Tabla Pivotizada -->
                <div class="overflow-x-auto mb-8">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-300 dark:border-[#2A2F3A]">
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#1F2937]">Etiquetas de Fila</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#1F2937]">Total General</th>
                                @foreach($metricasProductividad['responsables_ti_list'] as $id => $nombre)
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#1F2937]">{{ $nombre }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalGeneral = 0; @endphp
                            @forelse($metricasProductividad['matriz_incidencias_responsable'] as $tipo => $tipoData)
                                @php $totalGeneral += $tipoData['total']; @endphp
                                
                                <!-- Fila del TIPO (1- Hardware, 1- Comunicación, etc.) -->
                                <tr class="bg-blue-100 dark:bg-blue-900/30 border-t-2 border-blue-300 dark:border-blue-700 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-bold text-blue-900 dark:text-blue-100">{{ $tipo }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-600 text-white dark:bg-blue-700">{{ $tipoData['total'] }}</span>
                                    </td>
                                    @foreach($metricasProductividad['responsables_ti_list'] as $id => $nombre)
                                        <td class="px-4 py-3 text-center">
                                            @if(isset($tipoData['responsables'][$id]) && $tipoData['responsables'][$id] > 0)
                                                <span class="px-2 py-1 text-xs font-bold rounded-full bg-blue-200 text-blue-900 dark:bg-blue-800 dark:text-blue-100">{{ $tipoData['responsables'][$id] }}</span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-600">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                
                                <!-- Filas de SUBTIPOS (2- Computadoras, 2- Impresoras, etc.) -->
                                @foreach($tipoData['subtipos'] as $subtipo => $subtipoData)
                                    <tr class="dark:bg-transparent hover:bg-gray-50 dark:hover:bg-[#1F2937]/30 transition-colors border-b border-gray-100 dark:border-[#2A2F3A]">
                                        <td class="px-4 py-2 pl-8 text-sm text-gray-700 dark:text-gray-300">
                                            <i class="fas fa-level-up-alt fa-rotate-90 text-gray-400 mr-2"></i>{{ $subtipo }}
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $subtipoData['total'] }}</span>
                                        </td>
                                        @foreach($metricasProductividad['responsables_ti_list'] as $id => $nombre)
                                            <td class="px-4 py-2 text-center">
                                                @if(isset($subtipoData['responsables'][$id]) && $subtipoData['responsables'][$id] > 0)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">{{ $subtipoData['responsables'][$id] }}</span>
                                                @else
                                                    <span class="text-gray-300 dark:text-gray-600">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ count($metricasProductividad['responsables_ti_list']) + 2 }}" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No hay datos disponibles</td>
                                </tr>
                            @endforelse
                            
                            <!-- Fila de Totales -->
                            <tr class="bg-gray-200 dark:bg-[#1F2937] font-bold border-t-2 border-gray-400 dark:border-[#2A2F3A]">
                                <td class="px-4 py-3 text-sm dark:text-white">TOTAL GENERAL</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-600 text-white">{{ $totalGeneral }}</span>
                                </td>
                                @foreach($metricasProductividad['responsables_ti_list'] as $id => $nombre)
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs font-bold rounded-full bg-blue-600 text-white">{{ $metricasProductividad['totales_por_responsable'][$id] ?? 0 }}</span>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Gráfica de Barras Apiladas -->
                <div class="group rounded-xl p-4 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1F2937]/50 shadow-inner transition-all duration-300 ease-out hover:shadow-md hover:border-cyan-200/70 dark:hover:border-cyan-500/30 hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-cyan-500/20 dark:focus-within:ring-cyan-400/25">
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <h4 class="text-sm font-semibold dark:text-white">Visualización: Incidencias por Tipo y Responsable</h4>
                        <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                    </div>
                    <div class="h-[700px] rounded-lg overflow-hidden transition-[background] duration-300 bg-white/40 dark:bg-black/20 group-hover:bg-white/60 dark:group-hover:bg-black/30">
                        <canvas id="chartIncidenciasMatriz" role="img" aria-label="Incidencias por tipo y responsable"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 dark:border-[#2A2F3A] p-3 sm:p-6 shadow-md"
                x-data="{ 
                     totalEmpleados: {{ count($metricasProductividad['metricas_por_empleado']) }},
                     currentEmpleado: (typeof prodEmpleadoIdxInicial === 'function' ? prodEmpleadoIdxInicial({{ count($metricasProductividad['metricas_por_empleado']) }}) : 0),
                     persistirEmpleado() {
                         if (this.totalEmpleados > 0) {
                             sessionStorage.setItem('prodEmpleadoIdx', String(this.currentEmpleado));
                         }
                     },
                     siguiente() {
                         if (this.currentEmpleado < this.totalEmpleados - 1) {
                             this.currentEmpleado++;
                             this.persistirEmpleado();
                             setTimeout(function() {
                                 if (typeof resizeChartsProductividadEmpleados === 'function') {
                                     resizeChartsProductividadEmpleados();
                                 }
                             }, 320);
                         }
                     },
                     anterior() {
                         if (this.currentEmpleado > 0) {
                             this.currentEmpleado--;
                             this.persistirEmpleado();
                             setTimeout(function() {
                                 if (typeof resizeChartsProductividadEmpleados === 'function') {
                                     resizeChartsProductividadEmpleados();
                                 }
                             }, 320);
                         }
                     }
                 }"
                @keydown.arrow-left.window="anterior()"
                @keydown.arrow-right.window="siguiente()">

                <!-- Header con navegación -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 mb-3 sm:mb-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-semibold dark:text-white">Desempeño por Empleado TI</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Análisis mensual del rendimiento (Últimos 6 meses)</p>
                    </div>

                    @if(count($metricasProductividad['metricas_por_empleado']) > 0)
                    <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                        <span class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="`${currentEmpleado + 1}/${totalEmpleados}`"></span>
                        <button
                            @click="anterior()"
                            :disabled="currentEmpleado === 0"
                            :class="currentEmpleado === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'"
                            class="px-2 sm:px-3 py-1.5 sm:py-2 bg-blue-500 text-white rounded-lg transition-all duration-200 flex items-center gap-1 sm:gap-2 disabled:pointer-events-none text-xs sm:text-sm">
                            <i class="fas fa-chevron-left"></i>
                            <span class="hidden sm:inline">Anterior</span>
                        </button>
                        <button
                            @click="siguiente()"
                            :disabled="currentEmpleado === totalEmpleados - 1"
                            :class="currentEmpleado === totalEmpleados - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'"
                            class="px-2 sm:px-3 py-1.5 sm:py-2 bg-blue-500 text-white rounded-lg transition-all duration-200 flex items-center gap-1 sm:gap-2 disabled:pointer-events-none text-xs sm:text-sm">
                            <span class="hidden sm:inline">Siguiente</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Indicadores de navegación (dots) -->
                @if(count($metricasProductividad['metricas_por_empleado']) > 1)
                <div class="flex justify-center gap-1 sm:gap-2 mb-3 sm:mb-6 overflow-x-auto px-2">
                    @foreach($metricasProductividad['metricas_por_empleado'] as $index => $emp)
                    <button
                        @click="currentEmpleado = {{ $index }}; persistirEmpleado(); setTimeout(function() { if (typeof resizeChartsProductividadEmpleados === 'function') resizeChartsProductividadEmpleados(); }, 320)"
                        :class="currentEmpleado === {{ $index }} ? 'bg-blue-500 w-8' : 'bg-gray-300 dark:bg-gray-600 w-3'"
                        class="h-3 rounded-full transition-all duration-300 hover:bg-blue-400"
                        title="{{ $emp['nombre'] ?? 'Empleado ' . ($index + 1) }}">
                    </button>
                    @endforeach
                </div>
                @endif

                <div class="relative">
                    @forelse($metricasProductividad['metricas_por_empleado'] as $index => $empleado)
                    <div x-show="currentEmpleado === {{ $index }}"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform translate-x-8"
                        x-transition:enter-end="opacity-100 transform translate-x-0"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 transform translate-x-0"
                        x-transition:leave-end="opacity-0 transform -translate-x-8"
                        class="rounded-lg p-3 sm:p-6 border border-gray-100 dark:border-[#2A2F3A] bg-gray-50/30 dark:bg-transparent transition-colors">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-4 mb-3 sm:mb-6 pb-3 sm:pb-4 border-b-2 border-gray-200 dark:border-[#2A2F3A]">
                            <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                                <div class="rounded-full p-2 sm:p-3 flex-shrink-0" style="background-color: rgba(59, 130, 246, 0.15);">
                                    <i class="fas fa-user-tie text-lg sm:text-xl text-blue-500"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-lg sm:text-xl font-bold dark:text-white truncate">{{ $empleado['nombre'] ?? 'Sin nombre' }}</h4>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-[#9CA3AF]">
                                        Total: <span class="font-semibold">{{ $empleado['total'] ?? 0 }} tks</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:gap-6 flex-shrink-0">
                                @php
                                $tasa = $empleado['tasa_cierre'] ?? 0;
                                $colorTasa = $tasa >= 70 ? 'text-[#4ADE80]' : ($tasa >= 50 ? 'text-[#FBBF24]' : 'text-[#F87171]');
                                @endphp
                                <div class="text-center rounded-lg px-2 sm:px-4 py-1 sm:py-2">
                                    <p class="text-xs mb-0.5 sm:mb-1 text-gray-500 dark:text-gray-400">Tasa</p>
                                    <p class="text-xl sm:text-2xl font-bold {{ $colorTasa }}">{{ $tasa }}%</p>
                                </div>

                                <div class="text-center rounded-lg px-2 sm:px-4 py-1 sm:py-2">
                                    <p class="text-xs mb-0.5 sm:mb-1 text-gray-500 dark:text-gray-400">T. Prom.</p>
                                    <p class="text-xl sm:text-2xl font-bold text-[#3B82F6]">
                                        {{ isset($empleado['tiempo_promedio_resolucion']) && $empleado['tiempo_promedio_resolucion'] > 0
                                            ? number_format($empleado['tiempo_promedio_resolucion'], 1)
                                            : '0' }}h
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-3 sm:mb-6">
                            <div class="rounded-lg p-2 sm:p-4 text-center border" style="background-color: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                                <i class="fas fa-check-circle text-[#4ADE80] text-lg sm:text-2xl mb-1 sm:mb-2"></i>
                                <p class="text-xs sm:text-sm mb-1 text-gray-600 dark:text-gray-300">Cerrados</p>
                                <p class="text-2xl sm:text-3xl font-bold text-[#4ADE80]">{{ $empleado['cerrados'] }}</p>
                            </div>
                            <div class="rounded-lg p-2 sm:p-4 text-center border" style="background-color: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.3);">
                                <i class="fas fa-clock text-[#FBBF24] text-lg sm:text-2xl mb-1 sm:mb-2"></i>
                                <p class="text-xs sm:text-sm mb-1 text-gray-600 dark:text-gray-300">En Prog.</p>
                                <p class="text-2xl sm:text-3xl font-bold text-[#FBBF24]">{{ $empleado['en_progreso'] }}</p>
                            </div>
                            <div class="rounded-lg p-2 sm:p-4 text-center border" style="background-color: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.3);">
                                <i class="fas fa-exclamation-circle text-[#F87171] text-lg sm:text-2xl mb-1 sm:mb-2"></i>
                                <p class="text-xs sm:text-sm mb-1 text-gray-600 dark:text-gray-300">Pendientes</p>
                                <p class="text-2xl sm:text-3xl font-bold text-[#F87171]">{{ $empleado['pendientes'] }}</p>
                            </div>
                        </div>

                        <div class="mb-3 sm:mb-6">
                            <h5 class="text-xs sm:text-sm font-semibold mb-2 sm:mb-4 flex items-center gap-2 dark:text-white">
                                <i class="fas fa-calendar-alt text-[#3B82F6]"></i> Últimos 6 meses
                            </h5>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-1.5 sm:gap-3">
                                @php
                                $meses = array_reverse(array_keys($empleado['tickets_por_mes']), true);
                                $mesesEspanol = [
                                'Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr',
                                'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
                                'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic'
                                ];
                                @endphp
                                @foreach($meses as $mes)
                                @php
                                $datosMes = $empleado['tickets_por_mes'][$mes];
                                $totalMes = $datosMes['total'];
                                $cerradosMes = $datosMes['cerrados'];
                                $tasaCierreMes = $totalMes > 0 ? round(($cerradosMes / $totalMes) * 100, 1) : 0;
                                $mesFormateado = $mes;
                                foreach($mesesEspanol as $en => $es) {
                                $mesFormateado = str_replace($en, $es, $mesFormateado);
                                }
                                @endphp
                                <div class="border-2 {{ $totalMes > 0 ? 'border-[#3B82F6]' : 'border-gray-200 dark:border-[#2A2F3A]' }} bg-gray-50 dark:bg-transparent rounded-lg p-2 sm:p-3 hover:border-[#4A8FF6] transition-all">
                                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                                        <h6 class="font-bold text-xs sm:text-sm dark:text-gray-200">{{ $mesFormateado }}</h6>
                                        @if($totalMes > 0)
                                        <span class="px-1.5 py-0.5 text-xs font-semibold rounded text-[12px] {{ $tasaCierreMes >= 70 ? 'bg-[#4ADE80]/20 text-[#4ADE80]' : ($tasaCierreMes >= 50 ? 'bg-[#FBBF24]/20 text-[#FBBF24]' : 'bg-[#F87171]/20 text-[#F87171]') }}">
                                            {{ $tasaCierreMes }}%
                                        </span>
                                        @else
                                        <span class="text-gray-400 dark:text-[#6B7280] text-xs">-</span>
                                        @endif
                                    </div>

                                    @if($totalMes > 0)
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500 dark:text-[#9CA3AF]">Total</span>
                                            <span class="font-bold dark:text-white">{{ $totalMes }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-[#2A2F3A] rounded-full h-1 sm:h-2">
                                            <div class="bg-[#3B82F6] h-1 sm:h-2 rounded-full" style="width: 100%"></div>
                                        </div>

                                        <div class="flex items-center justify-between mt-1 text-xs">
                                            <span class="flex items-center gap-0.5">
                                                <i class="fas fa-check text-[#4ADE80] text-xs"></i> <span class="dark:text-gray-300">Ok</span>
                                            </span>
                                            <span class="text-sm font-bold text-[#4ADE80]">{{ $cerradosMes }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-[#2A2F3A] rounded-full h-1 sm:h-2">
                                            <div class="bg-[#4ADE80] h-1 sm:h-2 rounded-full" style="width: {{ min($tasaCierreMes, 100) }}%"></div>
                                        </div>
                                    </div>
                                    @else
                                    <div class="text-center py-2 text-gray-400 dark:text-[#6B7280]">
                                        <i class="fas fa-inbox text-lg mb-0.5"></i>
                                        <p class="text-xs">Sin tks</p>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h5 class="text-xs sm:text-sm font-semibold mb-2 sm:mb-3 flex items-center gap-2 dark:text-white">
                                <i class="fas fa-signal text-[#3B82F6]"></i> Prioridades
                            </h5>
                            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                                @php
                                $prioridades = [
                                'Alta' => ['color' => '#F87171', 'bg' => '#F87171', 'icon' => 'fa-exclamation-triangle'],
                                'Media' => ['color' => '#FBBF24', 'bg' => '#FBBF24', 'icon' => 'fa-exclamation-circle'],
                                'Baja' => ['color' => '#4ADE80', 'bg' => '#4ADE80', 'icon' => 'fa-info-circle']
                                ];
                                @endphp
                                @foreach($prioridades as $prioridad => $config)
                                <div class="rounded-lg p-2 sm:p-3 bg-gray-50 dark:bg-[#1F2937] border border-transparent dark:border-[#2A2F3A]">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-1">
                                            <i class="fas {{ $config['icon'] }} text-xs sm:text-sm" style="color: {{ $config['color'] }};"></i>
                                            <span class="text-xs sm:text-sm font-semibold dark:text-gray-200">{{ $prioridad }}</span>
                                        </div>
                                        <span class="text-lg sm:text-xl font-bold dark:text-white">
                                            {{ $empleado['tickets_por_prioridad'][$prioridad] ?? 0 }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-[#2A2F3A] rounded-full h-1 sm:h-2">
                                        <div class="h-1 sm:h-2 rounded-full transition-all duration-300" style="background-color: {{ $config['bg'] }}; width: {{ $empleado['total'] > 0 ? min((($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100, 100) : 0 }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="group mt-8 rounded-xl border border-gray-200 dark:border-[#2A2F3A] bg-gradient-to-br from-white/80 to-gray-50/60 dark:from-[#111827]/40 dark:to-[#0B0F14]/50 p-4 shadow-sm transition-all duration-300 ease-out hover:shadow-md hover:border-blue-200/80 dark:hover:border-blue-500/35 focus-within:ring-2 focus-within:ring-blue-500/15 dark:focus-within:ring-blue-400/20">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <h5 class="text-sm font-semibold flex items-center gap-2 dark:text-white">
                                    <i class="fas fa-chart-bar text-[#3B82F6]"></i> Total vs cerrados por mes
                                </h5>
                                <span class="shrink-0 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100 pointer-events-none" aria-hidden="true">Hover</span>
                            </div>
                            <div class="relative h-64 rounded-lg overflow-hidden transition-[background] duration-300 bg-gray-50/50 dark:bg-black/25 group-hover:bg-gray-50/80 dark:group-hover:bg-black/35">
                                <canvas id="chartEmpleado{{ $empleado['empleado_id'] }}" role="img" aria-label="Gráfico de tickets por mes para {{ $empleado['nombre'] ?? 'empleado' }}"></canvas>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 sm:py-12 border-2 border-dashed border-gray-200 dark:border-[#2A2F3A] rounded-lg">
                        <i class="fas fa-users text-3xl sm:text-5xl mb-2 sm:mb-4 text-gray-400 dark:text-[#6B7280]"></i>
                        <p class="text-base sm:text-lg font-semibold dark:text-gray-300">No hay métricas disponibles</p>
                        <p class="text-xs sm:text-sm mt-1 sm:mt-2 text-gray-500 dark:text-[#9CA3AF]">Los empleados aparecerán aquí con tickets asignados</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'solicitudes'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="rounded-2xl p-6 border border-blue-100 bg-blue-50/50 dark:bg-[#1F2937] dark:border-[#2A2F3A]">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500 text-white rounded-xl shadow-sm"><i class="fas fa-file-invoice-dollar text-xl"></i></div>
                        <div>
                            <p class="text-sm font-medium text-blue-900/70 dark:text-gray-400">Promedio Cotización</p>
                            <h4 class="text-2xl font-bold text-blue-900 dark:text-white">
                                {{ number_format($metricasSolicitudes['promedio_cotizacion_horas'] ?? 0, 1) }} <span class="text-sm font-normal text-blue-900/70 dark:text-gray-400">hrs</span>
                            </h4>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl p-6 border border-emerald-100 bg-emerald-50/50 dark:bg-[#1F2937] dark:border-[#2A2F3A]">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-500 text-white rounded-xl shadow-sm"><i class="fas fa-cogs text-xl"></i></div>
                        <div>
                            <p class="text-sm font-medium text-emerald-900/70 dark:text-gray-400">Promedio Configuración</p>
                            <h4 class="text-2xl font-bold text-emerald-900 dark:text-white">
                                {{ number_format($metricasSolicitudes['promedio_configuracion_dias'] ?? 0, 1) }} <span class="text-sm font-normal text-emerald-900/70 dark:text-gray-400">hrs</span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-[#2A2F3A] shadow-sm overflow-hidden bg-gray-50 dark:bg-transparent">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-transparent">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white"><i class="fas fa-list-ul mr-2 text-gray-400"></i> Desglose de Tiempos por Solicitud</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="uppercase tracking-wider border-b-2 border-gray-200 dark:border-[#2A2F3A] bg-gray-100 dark:bg-[#1F2937]/30 text-gray-500 dark:text-[#9CA3AF]">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">ID Sol.</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Creación</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">T. Cotización</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">T. Config.</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-right">Tiempo Total</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">Facturas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A] bg-gray-50 dark:bg-transparent">
                            @forelse($metricasSolicitudes['desglose'] ?? [] as $sol)
                            <tr @click="infoModal = { id: '{{ $sol['id'] }}', empleado: '{{ addslashes($sol['empleado'] ?? '') }}', proyecto: '{{ addslashes($sol['proyecto'] ?? '') }}', motivo: '{{ addslashes($sol['motivo'] ?? '') }}', descripcion: '{{ addslashes($sol['descripcion_motivo'] ?? '') }}', estatus: '{{ $sol['estatus'] ?? '' }}', creacion: '{{ $sol['fecha_creacion'] }}', actualizacion: '{{ $sol['fecha_actualizacion'] ?? '' }}' }; modalAbierto = true;"
                                class="hover:bg-gray-100 dark:hover:bg-[#1F2937]/50 transition-colors cursor-pointer">

                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">#{{ $sol['id'] }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-[#9CA3AF]">{{ $sol['fecha_creacion'] }}</td>

                                <td class="px-6 py-4 text-center">
                                    @if($sol['tiempo_cotizacion_horas'] !== null)
                                    <span class="inline-flex px-2 py-1 rounded-md bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 font-medium text-xs">
                                        {{ $sol['tiempo_cotizacion_horas'] }} h
                                    </span>
                                    @else
                                    <span class="text-gray-400 dark:text-gray-600">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if($sol['tiempo_configuracion_dias'] !== null)
                                    <span class="inline-flex px-2 py-1 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium text-xs">
                                        {{ $sol['tiempo_configuracion_dias'] }} h
                                    </span>
                                    @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs italic">Pendiente</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-300">
                                    {{ $sol['tiempo_total_dias'] !== null ? $sol['tiempo_total_dias'] . ' h' : '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @php
                                    $fSub = $sol['facturas_subidas'] ?? 0;
                                    $fNec = $sol['facturas_necesarias'] ?? 0;
                                    @endphp
                                    @if($fNec > 0)
                                    @if($fSub >= $fNec)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800">
                                        <i class="fas fa-check-circle"></i> {{ $fSub }}/{{ $fNec }} Completas
                                    </span>
                                    @elseif($fSub > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $fSub }}/{{ $fNec }} Parcial
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800">
                                        <i class="fas fa-times-circle"></i> Faltan (0/{{ $fNec }})
                                    </span>
                                    @endif
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium text-slate-500 bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                                        N/A
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-[#9CA3AF]">No hay solicitudes procesadas en este periodo.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Modal de Información Rápida SLA (Alpine.js) --}}
        <div x-show="modalAbierto"
            style="display: none;"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm"
            @click.self="modalAbierto = false"
            @keydown.escape.window="modalAbierto = false">

            <div class="relative w-full max-w-lg mx-4 bg-slate-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                {{-- Encabezado --}}
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                            Detalles Solicitud #<span x-text="infoModal.id"></span>
                        </h3>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 mt-1 inline-block" x-text="infoModal.estatus || 'Pendiente'">
                        </span>
                    </div>
                    <button @click="modalAbierto = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Cuerpo del Modal --}}
                <div class="px-6 py-5 space-y-4">

                    {{-- Info del solicitante --}}
                    <div class="flex items-start gap-3">
                        <div class="mt-1 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Solicitante</label>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200" x-text="infoModal.empleado"></p>
                            <p class="text-xs text-slate-500" x-text="infoModal.proyecto"></p>
                        </div>
                    </div>

                    {{-- Motivo --}}
                    <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700 mt-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Motivo Principal</label>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="infoModal.motivo"></p>

                        <p class="text-xs mt-2 text-slate-500 italic" x-show="infoModal.descripcion">"<span x-text="infoModal.descripcion"></span>"</p>
                    </div>

                    {{-- Fechas Clave --}}
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <div class="border border-slate-100 dark:border-slate-700 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                            <label class="text-xs font-bold text-slate-400 block"><i class="fas fa-calendar-plus mr-1"></i> Creada el</label>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-1" x-text="infoModal.creacion"></p>
                        </div>
                        <div class="border border-slate-100 dark:border-slate-700 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                            <label class="text-xs font-bold text-slate-400 block"><i class="fas fa-clock mr-1"></i> Última act.</label>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-1" x-text="infoModal.actualizacion || 'N/A'"></p>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button @click="modalAbierto = false" class="px-4 py-2 bg-red-500 text-white dark:bg-red-500 rounded-lg text-sm font-medium hover:bg-red-600 dark:hover:bg-red-600 transition-colors">
                        Cerrar Detalles
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
    Chart.register(ChartDataLabels);

    /** Índice del carrusel de empleados TI (persiste al recargar productividad por AJAX). */
    function prodEmpleadoIdxInicial(total) {
        var t = parseInt(total, 10);
        if (!t || t < 1) {
            return 0;
        }
        var idx = parseInt(sessionStorage.getItem('prodEmpleadoIdx') || '0', 10);
        if (isNaN(idx)) {
            idx = 0;
        }
        return Math.min(Math.max(0, idx), t - 1);
    }

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    /** Colores y bordes por tema (solo presentación; los datos no cambian). */
    function crearPaletaProductividad(esOscuro) {
        if (esOscuro) {
            return {
                donaBorde: 'rgba(15,23,42,0.94)',
                estado: ['#FB7185', '#FACC15', '#4ADE80'],
                clasificacion: ['#FB7185', '#93C5FD'],
                clasBorde: ['#FB7185', '#93C5FD'],
                prioridad: ['#FB7185', '#FACC15', '#4ADE80'],
                prioridadHover: ['#FDA4AF', '#FDE047', '#86EFAC'],
                prioridadBorde: 'rgba(255,255,255,0.12)',
                tipoBar: 'rgba(167,139,250,0.92)',
                tipoHover: '#DDD6FE',
                tipoBorde: '#A78BFA',
                lineCreadosStroke: '#93C5FD',
                lineCreadosFill: 'rgba(96,165,250,0.22)',
                lineResStroke: '#6EE7B7',
                lineResFill: 'rgba(52,211,153,0.22)',
                puntoCreados: { fill: '#0f172a', stroke: '#93C5FD', hFill: '#F8FAFC', hStroke: '#60A5FA' },
                puntoRes: { fill: '#0f172a', stroke: '#6EE7B7', hFill: '#F8FAFC', hStroke: '#34D399' },
                comp: [
                    { bg: 'rgba(59,130,246,0.9)', h: '#BFDBFE', b: '#60A5FA' },
                    { bg: 'rgba(16,185,129,0.88)', h: '#A7F3D0', b: '#34D399' },
                    { bg: 'rgba(245,158,11,0.85)', h: '#FDE68A', b: '#FBBF24' }
                ],
                matriz: ['#60A5FA', '#34D399', '#FBBF24', '#FB7185', '#C4B5FD', '#F472B6', '#22D3EE', '#BEF264', '#FB923C', '#A5B4FC'],
                matrizH: ['#93C5FD', '#6EE7B7', '#FDE68A', '#FDA4AF', '#E9D5FF', '#FBCFE8', '#67E8F9', '#D9F99D', '#FDBA74', '#C7D2FE'],
                empTot: { bg: 'rgba(59,130,246,0.88)', h: '#93C5FD', b: '#60A5FA' },
                empCer: { bg: 'rgba(52,211,153,0.88)', h: '#6EE7B7', b: '#4ADE80' }
            };
        }
        return {
            donaBorde: '#ffffff',
            estado: ['#DC2626', '#CA8A04', '#15803D'],
            clasificacion: ['#EF4444', '#2563EB'],
            clasBorde: ['#DC2626', '#1D4ED8'],
            prioridad: ['#EF4444', '#D97706', '#16A34A'],
            prioridadHover: ['#FB7185', '#F59E0B', '#4ADE80'],
            prioridadBorde: '#ffffff',
            tipoBar: '#7C3AED',
            tipoHover: '#A78BFA',
            tipoBorde: '#5B21B6',
            lineCreadosStroke: '#2563EB',
            lineCreadosFill: 'rgba(37,99,235,0.14)',
            lineResStroke: '#059669',
            lineResFill: 'rgba(5,150,105,0.14)',
            puntoCreados: { fill: '#ffffff', stroke: '#2563EB', hFill: '#2563EB', hStroke: '#ffffff' },
            puntoRes: { fill: '#ffffff', stroke: '#059669', hFill: '#059669', hStroke: '#ffffff' },
            comp: [
                { bg: '#3B82F6', h: '#60A5FA', b: '#1D4ED8' },
                { bg: '#10B981', h: '#34D399', b: '#047857' },
                { bg: '#F59E0B', h: '#FBBF24', b: '#B45309' }
            ],
            matriz: ['#2563EB', '#047857', '#B45309', '#B91C1C', '#6D28D9', '#BE185D', '#0E7490', '#4D7C0F', '#C2410C', '#4338CA'],
            matrizH: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'],
            empTot: { bg: 'rgba(59,130,246,0.82)', h: '#3B82F6', b: '#1D4ED8' },
            empCer: { bg: 'rgba(22,163,74,0.82)', h: '#22C55E', b: '#15803D' }
        };
    }

    function cursorSobreGrafica(event, elementos) {
        const t = event.native && event.native.target;
        if (t) {
            t.style.cursor = elementos.length ? 'pointer' : 'default';
        }
    }

    function cursorLeyendaPointer(evt) {
        const t = evt.native && evt.native.target;
        if (t) {
            t.style.cursor = 'pointer';
        } else if (evt.chart && evt.chart.canvas) {
            evt.chart.canvas.style.cursor = 'pointer';
        }
    }

    function cursorLeyendaDefault(evt) {
        const t = evt.native && evt.native.target;
        if (t) {
            t.style.cursor = 'default';
        } else if (evt.chart && evt.chart.canvas) {
            evt.chart.canvas.style.cursor = 'default';
        }
    }

    function tooltipEstiloBase(colores, extra) {
        return Object.assign({
            cornerRadius: 10,
            titleMarginBottom: 6,
            caretSize: 6,
            bodySpacing: 5,
            padding: 14,
            backgroundColor: colores.tooltipBg,
            titleColor: colores.tooltipTexto,
            bodyColor: colores.tooltipTexto,
            borderColor: colores.tooltipBorder,
            borderWidth: 1
        }, extra || {});
    }

    function formatearClaveFechaProductividad(fecha) {
        const s = String(fecha == null ? '' : fecha).trim();
        const mIso = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
        if (mIso) {
            return `${parseInt(mIso[3], 10)}/${parseInt(mIso[2], 10)}`;
        }
        const mDmy = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (mDmy) {
            return `${parseInt(mDmy[1], 10)}/${parseInt(mDmy[2], 10)}`;
        }
        return s || '—';
    }

    function valorNumericoTooltipDona(ctx) {
        const raw = ctx.raw;
        if (typeof raw === 'number' && !isNaN(raw)) {
            return raw;
        }
        if (ctx.parsed !== null && typeof ctx.parsed === 'object' && ctx.parsed !== undefined) {
            if (typeof ctx.parsed.y === 'number') {
                return ctx.parsed.y;
            }
        }
        if (typeof ctx.parsed === 'number' && !isNaN(ctx.parsed)) {
            return ctx.parsed;
        }
        return 0;
    }

    function datalabelsBarrasHorizontales(colores, dark) {
        return {
            display: function(ctx) {
                const v = Number(ctx.dataset.data[ctx.dataIndex]);
                return !isNaN(v) && v > 0;
            },
            color: dark ? '#F8FAFC' : '#0F172A',
            textStrokeColor: dark ? 'rgba(0,0,0,0.55)' : 'rgba(255,255,255,0.9)',
            textStrokeWidth: 2.5,
            font: { weight: 'bold', size: 12 },
            anchor: 'end',
            align: 'end',
            offset: 4,
            clip: false,
            formatter: function(value) {
                const n = Number(value);
                if (isNaN(n) || n <= 0) {
                    return '';
                }
                return Number.isInteger(n) ? String(n) : String(Math.round(n * 10) / 10);
            }
        };
    }

    function datalabelsMatrizApilada() {
        return {
            display: function(ctx) {
                const v = Number(ctx.dataset.data[ctx.dataIndex]);
                return !isNaN(v) && v > 0;
            },
            anchor: 'center',
            align: 'center',
            color: '#F8FAFC',
            textStrokeColor: 'rgba(0,0,0,0.55)',
            textStrokeWidth: 2,
            font: { weight: 'bold', size: 9 },
            clip: false,
            formatter: function(value) {
                const n = Number(value);
                return !isNaN(n) && n > 0 ? String(n) : '';
            }
        };
    }

    function datalabelsDona(sumaTotal, dark) {
        return {
            display: function(ctx) {
                const v = Number(ctx.dataset.data[ctx.dataIndex]);
                if (!sumaTotal || sumaTotal <= 0 || !v || isNaN(v)) {
                    return false;
                }
                return v / sumaTotal >= 0.055;
            },
            color: dark ? '#F8FAFC' : '#0F172A',
            textStrokeColor: dark ? 'rgba(0,0,0,0.5)' : 'rgba(255,255,255,0.92)',
            textStrokeWidth: 2,
            font: { weight: 'bold', size: 12 },
            formatter: function(value) {
                const n = Number(value);
                return !isNaN(n) && n > 0 ? String(n) : '';
            }
        };
    }

    // Variables globales para almacenar las instancias de gráficas
    let chartEstado, chartResueltos, chartTendencias, chartPrioridad, chartClasificacion, chartTipoTicket;
    let chartComparacionTiempos6Meses, chartIncidenciasMatriz;



    function obtenerDatosFrescos() {
        const rawData = document.getElementById('productividad-json-data');
        if (!rawData) return null;
        try {
            return JSON.parse(rawData.textContent);
        } catch (e) {
            console.error("Error leyendo JSON de gráficas:", e);
            return null;
        }
    }

    function destruirGraficasProductividad() {
        function destruir(ch) {
            if (ch && typeof ch.destroy === 'function') {
                try {
                    ch.destroy();
                } catch (e) {}
            }
        }
        destruir(chartEstado);
        destruir(chartResueltos);
        destruir(chartTendencias);
        destruir(chartPrioridad);
        destruir(chartClasificacion);
        destruir(chartTipoTicket);
        destruir(chartComparacionTiempos6Meses);
        destruir(chartIncidenciasMatriz);
        chartEstado = null;
        chartResueltos = null;
        chartTendencias = null;
        chartPrioridad = null;
        chartClasificacion = null;
        chartTipoTicket = null;
        chartComparacionTiempos6Meses = null;
        chartIncidenciasMatriz = null;

        var md = null;
        try {
            md = obtenerDatosFrescos();
        } catch (e2) {
            md = null;
        }
        if (md && md.metricas_por_empleado && md.metricas_por_empleado.length) {
            md.metricas_por_empleado.forEach(function(emp) {
                var k = 'chartEmpleado' + emp.empleado_id;
                if (window[k] && typeof window[k].destroy === 'function') {
                    try {
                        window[k].destroy();
                    } catch (e3) {}
                }
                window[k] = null;
            });
        }
    }

    function chartProductividadCanvasConectado(ch) {
        return !!(ch && ch.canvas && ch.canvas.isConnected);
    }

    function necesitaReiniciarGraficasEmpleados() {
        var md = obtenerDatosFrescos();
        if (!md || !md.metricas_por_empleado || !md.metricas_por_empleado.length) {
            return false;
        }
        for (var i = 0; i < md.metricas_por_empleado.length; i++) {
            var emp = md.metricas_por_empleado[i];
            var k = 'chartEmpleado' + emp.empleado_id;
            var canvas = document.getElementById(k);
            if (!canvas) {
                continue;
            }
            if (!isElementVisible(canvas)) {
                continue;
            }
            var ch = window[k];
            if (!ch || !(ch instanceof Chart) || !chartProductividadCanvasConectado(ch)) {
                return true;
            }
        }
        return false;
    }

    function inicializarGraficas() {
        // Verificar que los elementos existan
        if (!document.getElementById('chartEstado')) {
            return;
        }

        // Obtener los datos frescos actualizados
        const metricasData = obtenerDatosFrescos();
        if (!metricasData) return; // Si no hay datos, no intentamos dibujar

        const dark = isDarkMode();

        const colores = {
            texto: dark ? '#F3F4F6' : '#111827',
            textoSecundario: dark ? '#9CA3AF' : '#6B7280',
            grid: dark ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.07)',
            tooltipBg: dark ? 'rgba(15,23,42,0.97)' : '#FFFFFF',
            tooltipTexto: dark ? '#F3F4F6' : '#111827',
            tooltipBorder: dark ? '#334155' : '#E2E8F0',
            emptyDoughnut: dark ? 'rgba(255,255,255,0.06)' : 'rgba(15,23,42,0.06)'
        };

        const p = crearPaletaProductividad(dark);

        const animacionGraficas = {
            duration: 720,
            easing: 'easeOutQuart'
        };

        // Destruir gráficas existentes si ya están creadas
        if (chartEstado) chartEstado.destroy();
        if (chartResueltos) chartResueltos.destroy();
        if (chartTendencias) chartTendencias.destroy();
        if (chartPrioridad) chartPrioridad.destroy();
        if (chartClasificacion) chartClasificacion.destroy();
        if (chartTipoTicket) chartTipoTicket.destroy();
        if (chartComparacionTiempos6Meses) chartComparacionTiempos6Meses.destroy();
        if (chartIncidenciasMatriz) chartIncidenciasMatriz.destroy();

        // Datos para las gráficas extraídos dinámicamente
        const distribucionEstado = metricasData.distribucion_estado || {};
        const resueltosPorDia = metricasData.resueltos_por_dia || {};
        const creadosPorDia = metricasData.creados_por_dia || {};
        const tendenciasSemanales = metricasData.tendencias_semanales || {};
        const ticketsPorPrioridad = metricasData.tickets_por_prioridad || {};
        const ticketsPorClasificacion = metricasData.tickets_por_clasificacion || {};
        const ticketsPorTipo = metricasData.tickets_por_tipo || {};
        const ticketsPorGerenciaSolicitante = metricasData.tickets_por_gerencia_solicitante || {};
        const ticketsPorGerenciaResponsable = metricasData.tickets_por_gerencia_responsable || {};

        // Datos KPIs Operativos
        const backlogAcumulado = metricasData.backlog_acumulado || {};
        const cargaActualResponsable = metricasData.carga_actual_responsable || {};
        const slaRespuesta = metricasData.sla_respuesta || {
            cumplido: 0,
            incumplido: 0
        };
        const slaResolucion = metricasData.sla_resolucion || {
            cumplido: 0,
            incumplido: 0
        };
        const edadBacklog = metricasData.edad_backlog || {};
        const prioridadAbiertos = metricasData.prioridad_abiertos || {};
        const tendenciaEficiencia = metricasData.tendencia_eficiencia || {};
        const comparacionTiempos6Meses = metricasData.comparacion_tiempos_6_meses || {};
        const matrizIncidenciasData = metricasData.matriz_incidencias_responsable || {};
        const responsablesTIList = metricasData.responsables_ti_list || {};

        // -----------------------------------------------------
        // Gráfica de distribución por estado (Doughnut)
        // -----------------------------------------------------
        const ctxEstado = document.getElementById('chartEstado').getContext('2d');
        const valoresEstado = Object.values(distribucionEstado);
        const sumaEstados = valoresEstado.reduce((a, b) => a + b, 0);

        chartEstado = new Chart(ctxEstado, {
            type: 'doughnut',
            data: {
                labels: sumaEstados > 0 ? Object.keys(distribucionEstado) : ['Sin tickets este mes'],
                datasets: [{
                    data: sumaEstados > 0 ? valoresEstado : [1],
                    backgroundColor: sumaEstados > 0 ? p.estado : [colores.emptyDoughnut],
                    borderColor: sumaEstados > 0 ? p.donaBorde : colores.tooltipBorder,
                    borderWidth: sumaEstados > 0 ? (dark ? 3 : 2) : 0,
                    hoverOffset: sumaEstados > 0 ? 16 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animacionGraficas,
                cutout: '62%',
                onHover: cursorSobreGrafica,
                plugins: {
                    legend: {
                        position: 'bottom',
                        onHover: cursorLeyendaPointer,
                        onLeave: cursorLeyendaDefault,
                        labels: {
                            color: colores.textoSecundario,
                            padding: 14,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: tooltipEstiloBase(colores, {
                        enabled: sumaEstados > 0,
                        intersect: false,
                        position: 'nearest',
                        callbacks: {
                            label(context) {
                                const n = valorNumericoTooltipDona(context);
                                return `${context.label}: ${n} tickets`;
                            }
                        }
                    }),
                    datalabels: datalabelsDona(sumaEstados, dark)
                }
            }
        });

        // -----------------------------------------------------
        // Gráfica de tickets creados vs resueltos por día (Line)
        // -----------------------------------------------------
        const ctxResueltos = document.getElementById('chartResueltosPorDia').getContext('2d');
        const fechas = Object.keys(resueltosPorDia);
        const valoresResueltos = fechas.map(f => Number(resueltosPorDia[f]) || 0);
        const valoresCreados = fechas.map(f => Number(creadosPorDia[f]) || 0);

        const fechasFormateadas = fechas.map(formatearClaveFechaProductividad);

        chartResueltos = new Chart(ctxResueltos, {
            type: 'line',
            data: {
                labels: fechasFormateadas,
                datasets: [{
                        label: 'Tickets Creados',
                        data: valoresCreados,
                        borderColor: p.lineCreadosStroke,
                        backgroundColor: p.lineCreadosFill,
                        borderWidth: dark ? 2.5 : 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: dark ? 2.5 : 3,
                        pointHoverRadius: 9,
                        pointHitRadius: 18,
                        pointBackgroundColor: p.puntoCreados.fill,
                        pointBorderColor: p.puntoCreados.stroke,
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: p.puntoCreados.hFill,
                        pointHoverBorderColor: p.puntoCreados.hStroke,
                        pointHoverBorderWidth: 2
                    },
                    {
                        label: 'Tickets Resueltos',
                        data: valoresResueltos,
                        borderColor: p.lineResStroke,
                        backgroundColor: p.lineResFill,
                        borderWidth: dark ? 2.5 : 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: dark ? 2.5 : 3,
                        pointHoverRadius: 9,
                        pointHitRadius: 18,
                        pointBackgroundColor: p.puntoRes.fill,
                        pointBorderColor: p.puntoRes.stroke,
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: p.puntoRes.hFill,
                        pointHoverBorderColor: p.puntoRes.hStroke,
                        pointHoverBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animacionGraficas,
                onHover: cursorSobreGrafica,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                elements: {
                    point: {
                        hoverBorderWidth: 2
                    },
                    line: {
                        borderWidth: 2,
                        hoverBorderWidth: dark ? 4 : 3
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: colores.textoSecundario
                        },
                        grid: {
                            color: colores.grid
                        }
                    },
                    x: {
                        ticks: {
                            color: colores.textoSecundario
                        },
                        grid: {
                            color: colores.grid
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        onHover: cursorLeyendaPointer,
                        onLeave: cursorLeyendaDefault,
                        labels: {
                            color: colores.textoSecundario,
                            usePointStyle: true,
                            padding: 16
                        }
                    },
                    tooltip: tooltipEstiloBase(colores, {
                        intersect: false,
                        position: 'nearest',
                        callbacks: {
                            title: function(items) {
                                if (!items || !items.length) {
                                    return '';
                                }
                                const i = items[0].dataIndex;
                                if (i >= 0 && i < fechas.length && fechas[i]) {
                                    return formatearClaveFechaProductividad(fechas[i]);
                                }
                                const lab = items[0].chart && items[0].chart.data.labels[i];
                                return lab != null ? String(lab) : '';
                            },
                            label: function(ctx) {
                                const v = ctx.parsed && typeof ctx.parsed.y === 'number' ? ctx.parsed.y : Number(ctx.raw);
                                const n = isNaN(v) ? 0 : v;
                                return (ctx.dataset.label || '') + ': ' + n + ' tickets';
                            }
                        }
                    }),
                    datalabels: {
                        display: false
                    }
                }
            }
        });

        // -----------------------------------------------------
        // Gráfica de tickets por prioridad (Bar horizontal)
        // -----------------------------------------------------
        const ctxPrioridad = document.getElementById('chartPrioridad').getContext('2d');
        const clavesPrioridad = Object.keys(ticketsPorPrioridad);
        const hasPrioridad = clavesPrioridad.length > 0;

        chartPrioridad = new Chart(ctxPrioridad, {
            type: 'bar',
            data: {
                labels: hasPrioridad ? clavesPrioridad : ['Sin tickets este mes'],
                datasets: [{
                    label: 'Tickets',
                    data: hasPrioridad ? Object.values(ticketsPorPrioridad) : [0],
                    backgroundColor: hasPrioridad ? p.prioridad : [colores.grid],
                    hoverBackgroundColor: hasPrioridad ? p.prioridadHover : [colores.grid],
                    borderColor: hasPrioridad ? p.prioridadBorde : ['transparent'],
                    borderWidth: hasPrioridad ? (dark ? 1.5 : 1) : 0,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: animacionGraficas,
                onHover: cursorSobreGrafica,
                interaction: {
                    mode: 'nearest',
                    axis: 'y',
                    intersect: false
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: colores.textoSecundario
                        },
                        grid: {
                            color: colores.grid
                        }
                    },
                    y: {
                        ticks: {
                            color: colores.textoSecundario
                        },
                        grid: {
                            color: colores.grid
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: tooltipEstiloBase(colores, {
                        enabled: hasPrioridad,
                        intersect: false,
                        position: 'nearest'
                    }),
                    datalabels: datalabelsBarrasHorizontales(colores, dark)
                }
            }
        });

        // -----------------------------------------------------
        // Gráfica de tickets por clasificación (Doughnut)
        // -----------------------------------------------------
        const ctxClasificacion = document.getElementById('chartClasificacion');
        if (ctxClasificacion) {
            const valoresClasificacion = Object.values(ticketsPorClasificacion);
            const sumaClasificacion = valoresClasificacion.reduce((a, b) => a + b, 0);

            chartClasificacion = new Chart(ctxClasificacion.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: sumaClasificacion > 0 ? Object.keys(ticketsPorClasificacion) : ['Sin tickets este mes'],
                    datasets: [{
                        data: sumaClasificacion > 0 ? valoresClasificacion : [1],
                        backgroundColor: sumaClasificacion > 0 ? p.clasificacion : [colores.emptyDoughnut],
                        borderColor: sumaClasificacion > 0 ? p.clasBorde : [colores.tooltipBorder],
                        borderWidth: sumaClasificacion > 0 ? (dark ? 3 : 2) : 0,
                        hoverOffset: sumaClasificacion > 0 ? 16 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: animacionGraficas,
                    cutout: '62%',
                    onHover: cursorSobreGrafica,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            onHover: cursorLeyendaPointer,
                            onLeave: cursorLeyendaDefault,
                            labels: {
                                color: colores.textoSecundario,
                                padding: 14,
                                font: {
                                    size: 12
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: tooltipEstiloBase(colores, {
                            enabled: sumaClasificacion > 0,
                            intersect: false,
                            position: 'nearest',
                            callbacks: {
                                label: function(context) {
                                    const n = valorNumericoTooltipDona(context);
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    return label + n + ' tickets';
                                }
                            }
                        }),
                        datalabels: datalabelsDona(sumaClasificacion, dark)
                    }
                }
            });
        }

        // -----------------------------------------------------
        // Gráfica de tickets por tipo (Bar horizontal - Top 12)
        // -----------------------------------------------------
        const ctxTipoTicket = document.getElementById('chartTipoTicket');
        if (ctxTipoTicket) {
            const tiposLabels = Object.keys(ticketsPorTipo);
            const tiposValores = Object.values(ticketsPorTipo);
            const hasTipos = tiposLabels.length > 0;

            chartTipoTicket = new Chart(ctxTipoTicket.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: hasTipos ? tiposLabels : ['Sin tickets este mes'],
                    datasets: [{
                        label: 'Tickets',
                        data: hasTipos ? tiposValores : [0],
                        backgroundColor: p.tipoBar,
                        hoverBackgroundColor: p.tipoHover,
                        borderColor: p.tipoBorde,
                        borderWidth: dark ? 1.5 : 1,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: animacionGraficas,
                    onHover: cursorSobreGrafica,
                    interaction: {
                        mode: 'nearest',
                        axis: 'y',
                        intersect: false
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: colores.textoSecundario
                            },
                            grid: {
                                color: colores.grid
                            }
                        },
                        y: {
                            ticks: {
                                color: colores.textoSecundario,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                color: colores.grid
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: tooltipEstiloBase(colores, {
                            enabled: hasTipos,
                            intersect: false,
                            position: 'nearest'
                        }),
                        datalabels: datalabelsBarrasHorizontales(colores, dark)
                    }
                }
            });
        }

        // =====================================================================
        // GRÁFICA: Comparación de Tiempos - Últimos 6 Meses (Barras Agrupadas)
        // =====================================================================
        const ctxComparacionTiempos = document.getElementById('chartComparacionTiempos6Meses');
        if (ctxComparacionTiempos) {
            const mesesLabels = Object.keys(comparacionTiempos6Meses);
            const tiemposRespuesta = mesesLabels.map(mes => comparacionTiempos6Meses[mes].respuesta);
            const tiemposResolucion = mesesLabels.map(mes => comparacionTiempos6Meses[mes].resolucion);
            const tiemposTotal = mesesLabels.map(mes => comparacionTiempos6Meses[mes].total);
            const hayDatos = mesesLabels.length > 0;

            chartComparacionTiempos6Meses = new Chart(ctxComparacionTiempos.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: hayDatos ? mesesLabels : ['Sin datos'],
                    datasets: [{
                            label: 'Tiempo de Respuesta',
                            data: hayDatos ? tiemposRespuesta : [0],
                            backgroundColor: p.comp[0].bg,
                            hoverBackgroundColor: p.comp[0].h,
                            borderColor: p.comp[0].b,
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 'flex',
                            maxBarThickness: 56
                        },
                        {
                            label: 'Tiempo de Resolución',
                            data: hayDatos ? tiemposResolucion : [0],
                            backgroundColor: p.comp[1].bg,
                            hoverBackgroundColor: p.comp[1].h,
                            borderColor: p.comp[1].b,
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 'flex',
                            maxBarThickness: 56
                        },
                        {
                            label: 'Tiempo Total',
                            data: hayDatos ? tiemposTotal : [0],
                            backgroundColor: p.comp[2].bg,
                            hoverBackgroundColor: p.comp[2].h,
                            borderColor: p.comp[2].b,
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 'flex',
                            maxBarThickness: 56
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: animacionGraficas,
                    onHover: cursorSobreGrafica,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: colores.textoSecundario,
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return value.toFixed(1) + ' hrs';
                                }
                            },
                            grid: {
                                color: colores.grid,
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                color: colores.textoSecundario,
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'center',
                            onHover: cursorLeyendaPointer,
                            onLeave: cursorLeyendaDefault,
                            labels: {
                                color: colores.texto,
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                usePointStyle: true,
                                pointStyle: 'rect',
                                boxWidth: 12,
                                boxHeight: 12
                            }
                        },
                        tooltip: tooltipEstiloBase(colores, {
                            enabled: hayDatos,
                            displayColors: true,
                            boxWidth: 10,
                            boxHeight: 10,
                            boxPadding: 5,
                            intersect: false,
                            position: 'nearest',
                            titleFont: {
                                size: 13,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    const y = context.parsed && typeof context.parsed.y === 'number' ? context.parsed.y : Number(context.raw);
                                    const n = isNaN(y) ? 0 : y;
                                    return ' ' + context.dataset.label + ': ' + n.toFixed(2) + ' hrs';
                                }
                            }
                        }),
                        datalabels: {
                            display: function(ctx) {
                                if (!hayDatos) {
                                    return false;
                                }
                                const v = Number(ctx.dataset.data[ctx.dataIndex]);
                                return !isNaN(v) && v > 0;
                            },
                            color: dark ? '#F8FAFC' : '#0F172A',
                            textStrokeColor: dark ? 'rgba(0,0,0,0.5)' : 'rgba(255,255,255,0.95)',
                            textStrokeWidth: 2,
                            font: {
                                weight: 'bold',
                                size: 10
                            },
                            formatter: function(value) {
                                const n = Number(value);
                                if (isNaN(n) || n <= 0) {
                                    return '';
                                }
                                return n.toFixed(1) + ' h';
                            },
                            anchor: 'end',
                            align: 'top',
                            offset: 6,
                            clip: false
                        }
                    }
                }
            });
        }

        // =====================================================================
        // GRÁFICA: Matriz de Incidencias (Tipo → Subtipo vs Responsable TI)
        // =====================================================================
        const ctxIncidenciasMatriz = document.getElementById('chartIncidenciasMatriz');
        if (ctxIncidenciasMatriz) {
            const matrizData = metricasData.matriz_incidencias_responsable || {};
            const responsablesList = metricasData.responsables_ti_list || {};
            
            // Preparar labels (todos los subtipos con su tipo padre)
            const labels = [];
            const datasets = [];
            
            // Crear un dataset por responsable
            const responsablesArray = Object.keys(responsablesList);
            // Primero, crear los labels (tipos → subtipos) y preparar datos
            Object.keys(matrizData).forEach((tipo, tipoIdx) => {
                const tipoData = matrizData[tipo];
                Object.keys(tipoData.subtipos).forEach((subtipo) => {
                    labels.push(`${tipo} → ${subtipo}`);
                });
            });
            
            // Crear datasets por responsable
            responsablesArray.forEach((responsableID, idx) => {
                const responsableNombre = responsablesList[responsableID];
                const data = [];
                
                Object.keys(matrizData).forEach((tipo) => {
                    const tipoData = matrizData[tipo];
                    Object.keys(tipoData.subtipos).forEach((subtipo) => {
                        const subtipoData = tipoData.subtipos[subtipo];
                        const valor = subtipoData.responsables[responsableID] || 0;
                        data.push(valor);
                    });
                });
                
                datasets.push({
                    label: responsableNombre,
                    data: data,
                    backgroundColor: p.matriz[idx % p.matriz.length],
                    hoverBackgroundColor: p.matrizH[idx % p.matrizH.length],
                    borderColor: dark ? 'rgba(15,23,42,0.4)' : 'rgba(15,23,42,0.1)',
                    borderWidth: dark ? 1.5 : 1,
                    borderRadius: 4,
                    borderSkipped: false
                });
            });
            
            const hayDatos = labels.length > 0 && datasets.some(ds => ds.data.some(v => v > 0));
            
            chartIncidenciasMatriz = new Chart(ctxIncidenciasMatriz.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: hayDatos ? labels : ['Sin datos'],
                    datasets: hayDatos ? datasets : [{
                        label: 'Sin datos',
                        data: [0],
                        backgroundColor: dark ? '#334155' : '#E5E7EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: animacionGraficas,
                    indexAxis: 'y',
                    onHover: cursorSobreGrafica,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                color: colores.textoSecundario,
                                font: { size: 11 },
                                stepSize: 1
                            },
                            grid: {
                                color: colores.grid,
                                drawBorder: false
                            }
                        },
                        y: {
                            stacked: true,
                            ticks: {
                                color: colores.textoSecundario,
                                font: { size: 10 },
                                autoSkip: false
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            onHover: cursorLeyendaPointer,
                            onLeave: cursorLeyendaDefault,
                            labels: {
                                color: colores.texto,
                                padding: 15,
                                font: { size: 12, weight: 'bold' },
                                usePointStyle: true,
                                pointStyle: 'rect',
                                boxWidth: 15,
                                boxHeight: 15
                            }
                        },
                        tooltip: tooltipEstiloBase(colores, {
                            enabled: hayDatos,
                            intersect: false,
                            position: 'nearest',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 12 },
                            callbacks: {
                                title: function(items) {
                                    if (!items || !items.length) {
                                        return '';
                                    }
                                    const i = items[0].dataIndex;
                                    return labels[i] != null ? String(labels[i]) : '';
                                },
                                label: function(context) {
                                    const px = context.parsed && typeof context.parsed.x === 'number' ? context.parsed.x : Number(context.raw);
                                    const n = isNaN(px) ? 0 : px;
                                    if (n <= 0) {
                                        return null;
                                    }
                                    return context.dataset.label + ': ' + n + ' tickets';
                                }
                            },
                            filter: function(tooltipItem) {
                                const px = tooltipItem.parsed && typeof tooltipItem.parsed.x === 'number' ? tooltipItem.parsed.x : Number(tooltipItem.raw);
                                return !isNaN(px) && px > 0;
                            }
                        }),
                        datalabels: datalabelsMatrizApilada()
                    }
                }
            });
        }
    }

    // Función para verificar si el elemento está visible (no tiene x-cloak y está renderizado)
    function isElementVisible(element) {
        if (!element) return false;
        const parent = element.closest('[x-show]');
        if (!parent) return true; // Si no tiene x-show, asumir que está visible
        const style = window.getComputedStyle(parent);
        return style.display !== 'none' && !parent.hasAttribute('x-cloak');
    }

    // Función para inicializar las gráficas cuando el tab esté visible
    function inicializarCuandoVisible() {
        const canvasEstado = document.getElementById('chartEstado');
        let inicializado = false;

        if (canvasEstado && isElementVisible(canvasEstado)) {
            if (!chartEstado || !chartProductividadCanvasConectado(chartEstado)) {
                inicializarGraficas();
                inicializado = true;
            }
        }

        const canvasEmpleado = document.querySelector('[id^="chartEmpleado"]');
        if (canvasEmpleado && isElementVisible(canvasEmpleado)) {
            if (necesitaReiniciarGraficasEmpleados()) {
                inicializarGraficasEmpleados();
                inicializado = true;
            }
        }

        return inicializado;
    }

    // Intentar inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        let intentos = 0;
        const maxIntentos = 10;

        const intervalo = setInterval(function() {
            intentos++;
            if (inicializarCuandoVisible() || intentos >= maxIntentos) {
                clearInterval(intervalo);
            }
        }, 200);

        setTimeout(inicializarCuandoVisible, 100);
    });

    // Observar cuando el tab cambie (debounce: evita destruir Chart.js al mover el tooltip o al reconciliar Alpine)
    let prodTabMutDebounce = null;
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('[x-data*="tab"]');
        if (container) {
            const observer = new MutationObserver(function() {
                clearTimeout(prodTabMutDebounce);
                prodTabMutDebounce = setTimeout(function() {
                    const canvasEstado = document.getElementById('chartEstado');
                    if (!canvasEstado || !isElementVisible(canvasEstado)) {
                        return;
                    }
                    if (!chartEstado || !chartProductividadCanvasConectado(chartEstado)) {
                        inicializarGraficas();
                    }
                    if (necesitaReiniciarGraficasEmpleados()) {
                        inicializarGraficasEmpleados();
                    }
                }, 450);
            });

            observer.observe(container, {
                attributes: true,
                attributeFilter: ['x-cloak'],
                childList: true,
                subtree: true
            });
        }

        let prodVisMutDebounce = null;
        const productividadDiv = document.querySelector('[x-show*="tab === 2"]');
        if (productividadDiv) {
            const productividadObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const style = window.getComputedStyle(productividadDiv);
                        if (style.display !== 'none' && !productividadDiv.hasAttribute('x-cloak')) {
                            clearTimeout(prodVisMutDebounce);
                            prodVisMutDebounce = setTimeout(function() {
                                const canvasEstado = document.getElementById('chartEstado');
                                if (!canvasEstado || !isElementVisible(canvasEstado)) {
                                    return;
                                }
                                if (!chartEstado || !chartProductividadCanvasConectado(chartEstado)) {
                                    inicializarGraficas();
                                }
                                if (necesitaReiniciarGraficasEmpleados()) {
                                    inicializarGraficasEmpleados();
                                }
                            }, 450);
                        }
                    }
                });
            });

            productividadObserver.observe(productividadDiv, {
                attributes: true,
                attributeFilter: ['style', 'x-cloak'],
                childList: false,
                subtree: false
            });
        }

        if (window.Alpine) {
            setTimeout(function() {
                inicializarCuandoVisible();
            }, 300);
        }

        let intentosGraficas = 0;
        const maxIntentosGraficas = 20;
        const intervaloGraficas = setInterval(function() {
            intentosGraficas++;
            const productividadVisible = document.querySelector('[x-show*="tab === 2"]');
            if (productividadVisible) {
                const style = window.getComputedStyle(productividadVisible);
                if (style.display !== 'none' && !productividadVisible.hasAttribute('x-cloak')) {
                    if (!chartEstado || !chartProductividadCanvasConectado(chartEstado)) {
                        inicializarGraficas();
                    }
                    if (necesitaReiniciarGraficasEmpleados()) {
                        inicializarGraficasEmpleados();
                    }
                    clearInterval(intervaloGraficas);
                }
            }
            if (intentosGraficas >= maxIntentosGraficas) {
                clearInterval(intervaloGraficas);
            }
        }, 300);
    });

    // =======================================================
    // Función para inicializar gráficas de empleados
    // =======================================================
    function inicializarGraficasEmpleados() {
        try {
            // Obtener la data fresca dinámica
            const metricasData = obtenerDatosFrescos();
            if (!metricasData) return;

            const metricasEmpleados = metricasData.metricas_por_empleado || [];

            if (!metricasEmpleados || metricasEmpleados.length === 0) {
                return;
            }

            // Mapeo de meses en inglés a español
            const mesesEspanol = {
                'Jan': 'Ene',
                'Feb': 'Feb',
                'Mar': 'Mar',
                'Apr': 'Abr',
                'May': 'May',
                'Jun': 'Jun',
                'Jul': 'Jul',
                'Aug': 'Ago',
                'Sep': 'Sep',
                'Oct': 'Oct',
                'Nov': 'Nov',
                'Dec': 'Dic'
            };

            metricasEmpleados.forEach(empleado => {
                const canvasId = 'chartEmpleado' + empleado.empleado_id;
                const canvas = document.getElementById(canvasId);

                // Verificar si ya existe una gráfica para este canvas
                const chartKey = 'chartEmpleado' + empleado.empleado_id;
                if (window[chartKey]) {
                    if (window[chartKey] instanceof Chart && typeof window[chartKey].destroy === 'function') {
                        try {
                            window[chartKey].destroy();
                        } catch (e) {}
                    }
                    window[chartKey] = null;
                }

                if (!canvas) {
                    return;
                }

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return;
                }

                // Obtener meses en orden (de más antiguo a más reciente)
                const meses = Object.keys(empleado.tickets_por_mes).reverse();
                const mesesEspanolLabels = meses.map(mes => {
                    const partes = mes.split(' ');
                    if (partes.length === 2) {
                        const mesIngles = partes[0];
                        const anio = partes[1];
                        return (mesesEspanol[mesIngles] || mesIngles) + ' ' + anio;
                    }
                    return mes;
                });
                const totales = meses.map(mes => empleado.tickets_por_mes[mes].total);
                const cerrados = meses.map(mes => empleado.tickets_por_mes[mes].cerrados);

                const dark = isDarkMode();
                const pEmp = crearPaletaProductividad(dark);
                const coloresEmpleado = {
                    texto: dark ? '#F3F4F6' : '#111827',
                    textoSecundario: dark ? '#9CA3AF' : '#6B7280',
                    grid: dark ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.07)',
                    tooltipBg: dark ? 'rgba(15,23,42,0.97)' : '#FFFFFF',
                    tooltipTexto: dark ? '#F3F4F6' : '#111827',
                    tooltipBorder: dark ? '#334155' : '#E2E8F0'
                };

                try {
                    window[chartKey] = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: mesesEspanolLabels,
                            datasets: [                                {
                                    label: 'Total de Tickets',
                                    data: totales,
                                    backgroundColor: pEmp.empTot.bg,
                                    hoverBackgroundColor: pEmp.empTot.h,
                                    borderColor: pEmp.empTot.b,
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    borderSkipped: false
                                },
                                {
                                    label: 'Tickets Cerrados',
                                    data: cerrados,
                                    backgroundColor: pEmp.empCer.bg,
                                    hoverBackgroundColor: pEmp.empCer.h,
                                    borderColor: pEmp.empCer.b,
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    borderSkipped: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 700,
                                easing: 'easeOutQuart'
                            },
                            onHover: cursorSobreGrafica,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        precision: 0,
                                        color: coloresEmpleado.textoSecundario
                                    },
                                    grid: {
                                        color: coloresEmpleado.grid
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: coloresEmpleado.textoSecundario
                                    },
                                    grid: {
                                        color: coloresEmpleado.grid
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    onHover: cursorLeyendaPointer,
                                    onLeave: cursorLeyendaDefault,
                                    labels: {
                                        usePointStyle: true,
                                        padding: 15,
                                        color: coloresEmpleado.textoSecundario,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: tooltipEstiloBase(coloresEmpleado, {
                                    intersect: false,
                                    titleFont: {
                                        size: 14
                                    },
                                    bodyFont: {
                                        size: 13
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += context.parsed.y + ' tickets';
                                            return label;
                                        }
                                    }
                                }),
                                datalabels: {
                                    color: coloresEmpleado.texto,
                                    anchor: 'end',
                                    align: 'top',
                                    font: { weight: 'bold', size: 10 }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creando gráfica para empleado ' + empleado.empleado_id + ':', error);
                    window[chartKey] = null;
                }
            });
            setTimeout(function() {
                if (typeof resizeChartsProductividadEmpleados === 'function') {
                    resizeChartsProductividadEmpleados();
                }
            }, 400);
        } catch (error) {
            console.error('Error en inicializarGraficasEmpleados:', error);
        }
    }

    function resizeChartsProductividadEmpleados() {
        const metricasData = obtenerDatosFrescos();
        if (!metricasData || !metricasData.metricas_por_empleado) {
            return;
        }
        metricasData.metricas_por_empleado.forEach(function(empleado) {
            const chartKey = 'chartEmpleado' + empleado.empleado_id;
            const ch = window[chartKey];
            if (ch && typeof ch.resize === 'function') {
                ch.resize();
            }
        });
    }

    // Función para forzar reinicialización de gráficas de empleados
    function reinicializarGraficasEmpleados() {
        const metricasData = obtenerDatosFrescos();
        if (!metricasData) return;
        const metricasEmpleados = metricasData.metricas_por_empleado || [];

        if (metricasEmpleados) {
            metricasEmpleados.forEach(empleado => {
                const chartKey = 'chartEmpleado' + empleado.empleado_id;
                if (window[chartKey]) {
                    window[chartKey].destroy();
                    window[chartKey] = null;
                }
            });
        }
        // Reinicializar
        setTimeout(inicializarGraficasEmpleados, 100);
    }

    // Escuchar cuando se hace clic en el tab de productividad
    document.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (target && target.textContent && target.textContent.includes('Productividad')) {
            setTimeout(function() {
                inicializarGraficas();
                inicializarGraficasEmpleados();
            }, 400);
        }
    });

    // Alpine actualiza el DOM con mucha frecuencia: no recrear Chart.js cada vez (rompe tooltips y parece que “vuelve al primero”).
    if (window.Alpine) {
        let alpineProdChartsDebounce = null;
        document.addEventListener('alpine:updated', function() {
            clearTimeout(alpineProdChartsDebounce);
            alpineProdChartsDebounce = setTimeout(function() {
                const productividadVisible = document.querySelector('[x-show*="tab === 2"]');
                if (!productividadVisible) {
                    return;
                }
                const style = window.getComputedStyle(productividadVisible);
                if (style.display === 'none') {
                    return;
                }
                const canvasEstado = document.getElementById('chartEstado');
                if (!canvasEstado || !isElementVisible(canvasEstado)) {
                    return;
                }
                if (!chartEstado || !chartProductividadCanvasConectado(chartEstado)) {
                    inicializarGraficas();
                }
                if (necesitaReiniciarGraficasEmpleados()) {
                    inicializarGraficasEmpleados();
                }
            }, 450);
        });
    }

    // MutationObserver para detectar cambios en dark mode y reinicializar gráficas
    const observerDarkMode = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Solo reinicializar si las gráficas están visibles
                setTimeout(function() {
                    const productividadContainer = document.getElementById('productividad-container');
                    if (productividadContainer) {
                        const style = window.getComputedStyle(productividadContainer);
                        if (style.display !== 'none') {
                            inicializarGraficas();
                            inicializarGraficasEmpleados();
                        }
                    }
                }, 50);
            }
        });
    });

    // Observar cambios en la clase del elemento <html>
    observerDarkMode.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
</script>