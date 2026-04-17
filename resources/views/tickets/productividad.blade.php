<div class="space-y-6 min-h-screen p-6" id="productividad-container" x-data="{ activeTab: sessionStorage.getItem('prodTab') || 'general' }" x-init="$watch('activeTab', val => sessionStorage.setItem('prodTab', val))">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte de Productividad</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Análisis de rendimiento y métricas de tiempos.</p>
        </div>

        <div class="flex items-center gap-4"
            x-data="{ 
                 mes: {{ $mes ?? now()->month }}, 
                 anio: {{ $anio ?? now()->year }}, 
                 cargando: false,
                 cargarProductividad() {
                     this.cargando = true;
                     
                     fetch(`{{ route('tickets.productividad-ajax') }}?mes=${this.mes}&anio=${this.anio}`)
                         .then(response => response.json())
                         .then(data => {
                             if (data.success) {
                                 // 1. Reemplazamos el HTML limpiamente
                                 const container = document.getElementById('productividad-container');
                                 container.outerHTML = data.html;
                                 
                                 // 2. Forzamos a que se dibujen las gráficas si estamos en la pestaña principal
                                 setTimeout(() => {
                                     if (sessionStorage.getItem('prodTab') === 'general' || !sessionStorage.getItem('prodTab')) {
                                         if (typeof inicializarGraficas === 'function') inicializarGraficas();
                                         if (typeof inicializarGraficasEmpleados === 'function') inicializarGraficasEmpleados();
                                     }
                                 }, 300);
                             }
                             this.cargando = false;
                         })
                         .catch(error => {
                             console.error('Error:', error);
                             this.cargando = false;
                         });
                 }
             }">

            <div class="flex items-center gap-2 bg-gray-50 dark:bg-[#1F2937] p-1.5 rounded-xl border border-gray-200 dark:border-[#2A2F3A] shadow-sm">
                <select x-model="mes" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-3 pr-8 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ \Carbon\Carbon::create(now()->year, $i, 1)->locale('es')->isoFormat('MMMM') }}</option>
                        @endfor
                </select>

                <div class="w-px h-5 bg-gray-300 dark:bg-gray-600"></div>

                <select x-model="anio" @change="cargarProductividad()" :disabled="cargando" class="border-0 bg-transparent py-1.5 pl-3 pr-8 text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                    @for($i = now()->year; $i >= now()->year - 5; $i--)
                    <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>

                <div x-show="cargando" class="pr-3">
                    <i class="fas fa-spinner fa-spin text-[#2563EB]"></i>
                </div>
            </div>

            <a href="{{ route('tickets.exportar-reporte-mensual-excel', ['mes' => $mes ?? now()->month, 'anio' => $anio ?? now()->year]) }}"
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
                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Distribución por Estado</h3>
                    <div class="relative h-[300px]">
                        <canvas id="chartEstado"></canvas>
                    </div>
                </div>

                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Tickets Creados vs Resueltos (Últimos 30 días)</h3>
                    <div class="relative h-[300px]">
                        <canvas id="chartResueltosPorDia"></canvas>
                    </div>
                </div>
            </div>

            <!-- Comparación de Tiempos - Últimos 6 Meses -->
            <div class="rounded-lg shadow-md p-6 border border-gray-200 dark:border-[#2A2F3A]">
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Comparación de Tiempos - Últimos 6 Meses</h3>
                <p class="text-sm mb-4 text-gray-500 dark:text-gray-400">Evolución de tiempos promedio de respuesta, resolución y total</p>
                <div class="h-80">
                    <canvas id="chartComparacionTiempos6Meses"></canvas>
                </div>
            </div>

            <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Distribución por Clasificación (En Progreso y Cerrados)</h3>
                <div class="relative h-[300px]">
                    <canvas id="chartClasificacion"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Tickets por Prioridad</h3>
                    <div class="relative h-[300px]">
                        <canvas id="chartPrioridad"></canvas>
                    </div>
                </div>

                <div class="rounded-lg p-6 border border-gray-200 dark:border-[#2A2F3A]">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Distribución por Tipo de Ticket (Top 12)</h3>
                    <div class="relative h-[300px]">
                        <canvas id="chartTipoTicket"></canvas>
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
                <div class="rounded-lg p-4 border border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1F2937]/50">
                    <h4 class="text-sm font-semibold mb-4 dark:text-white">Visualización: Incidencias por Tipo y Responsable</h4>
                    <div class="h-[700px]">
                        <canvas id="chartIncidenciasMatriz"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 dark:border-[#2A2F3A] p-6 shadow-md"
                x-data="{ 
                     currentEmpleado: 0, 
                     totalEmpleados: {{ count($metricasProductividad['metricas_por_empleado']) }},
                     siguiente() {
                         if (this.currentEmpleado < this.totalEmpleados - 1) {
                             this.currentEmpleado++;
                         }
                     },
                     anterior() {
                         if (this.currentEmpleado > 0) {
                             this.currentEmpleado--;
                         }
                     }
                 }"
                @keydown.arrow-left.window="anterior()"
                @keydown.arrow-right.window="siguiente()">

                <!-- Header con navegación -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Desempeño por Empleado TI</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Análisis mensual del rendimiento de cada responsable de TI (Últimos 6 meses)</p>
                    </div>

                    @if(count($metricasProductividad['metricas_por_empleado']) > 0)
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400" x-text="`${currentEmpleado + 1} de ${totalEmpleados}`"></span>
                        <button
                            @click="anterior()"
                            :disabled="currentEmpleado === 0"
                            :class="currentEmpleado === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'"
                            class="px-3 py-2 bg-blue-500 text-white rounded-lg transition-all duration-200 flex items-center gap-2 disabled:pointer-events-none">
                            <i class="fas fa-chevron-left"></i>
                            <span class="hidden sm:inline">Anterior</span>
                        </button>
                        <button
                            @click="siguiente()"
                            :disabled="currentEmpleado === totalEmpleados - 1"
                            :class="currentEmpleado === totalEmpleados - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'"
                            class="px-3 py-2 bg-blue-500 text-white rounded-lg transition-all duration-200 flex items-center gap-2 disabled:pointer-events-none">
                            <span class="hidden sm:inline">Siguiente</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Indicadores de navegación (dots) -->
                @if(count($metricasProductividad['metricas_por_empleado']) > 1)
                <div class="flex justify-center gap-2 mb-6">
                    @foreach($metricasProductividad['metricas_por_empleado'] as $index => $emp)
                    <button
                        @click="currentEmpleado = {{ $index }}"
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
                        class="rounded-lg p-6 border border-gray-100 dark:border-[#2A2F3A] bg-gray-50/30 dark:bg-transparent transition-colors">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 dark:border-[#2A2F3A]">
                            <div class="flex items-center gap-4">
                                <div class="rounded-full p-3" style="background-color: rgba(59, 130, 246, 0.15);">
                                    <i class="fas fa-user-tie text-xl text-blue-500"></i>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold dark:text-white">{{ $empleado['nombre'] ?? 'Sin nombre' }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-[#9CA3AF]">
                                        Total acumulado: <span class="font-semibold">{{ $empleado['total'] ?? 0 }} tickets</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                @php
                                $tasa = $empleado['tasa_cierre'] ?? 0;
                                $colorTasa = $tasa >= 70 ? 'text-[#4ADE80]' : ($tasa >= 50 ? 'text-[#FBBF24]' : 'text-[#F87171]');
                                @endphp
                                <div class="text-center rounded-lg px-4 py-2">
                                    <p class="text-xs mb-1 text-gray-500 dark:text-gray-400">Tasa de Cierre</p>
                                    <p class="text-2xl font-bold {{ $colorTasa }}">{{ $tasa }}%</p>
                                </div>

                                <div class="text-center rounded-lg px-4 py-2">
                                    <p class="text-xs mb-1 text-gray-500 dark:text-gray-400">Tiempo Promedio</p>
                                    <p class="text-2xl font-bold text-[#3B82F6]">
                                        {{ isset($empleado['tiempo_promedio_resolucion']) && $empleado['tiempo_promedio_resolucion'] > 0
                                            ? number_format($empleado['tiempo_promedio_resolucion'], 1)
                                            : '0' }}h
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="rounded-lg p-4 text-center border" style="background-color: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                                <i class="fas fa-check-circle text-[#4ADE80] text-2xl mb-2"></i>
                                <p class="text-sm mb-1 text-gray-600 dark:text-gray-300">Cerrados</p>
                                <p class="text-3xl font-bold text-[#4ADE80]">{{ $empleado['cerrados'] }}</p>
                            </div>
                            <div class="rounded-lg p-4 text-center border" style="background-color: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.3);">
                                <i class="fas fa-clock text-[#FBBF24] text-2xl mb-2"></i>
                                <p class="text-sm mb-1 text-gray-600 dark:text-gray-300">En Progreso</p>
                                <p class="text-3xl font-bold text-[#FBBF24]">{{ $empleado['en_progreso'] }}</p>
                            </div>
                            <div class="rounded-lg p-4 text-center border" style="background-color: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.3);">
                                <i class="fas fa-exclamation-circle text-[#F87171] text-2xl mb-2"></i>
                                <p class="text-sm mb-1 text-gray-600 dark:text-gray-300">Pendientes</p>
                                <p class="text-3xl font-bold text-[#F87171]">{{ $empleado['pendientes'] }}</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h5 class="text-sm font-semibold mb-4 flex items-center gap-2 dark:text-white">
                                <i class="fas fa-calendar-alt text-[#3B82F6]"></i> Desempeño Mensual (Últimos 6 meses)
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                                <div class="border-2 {{ $totalMes > 0 ? 'border-[#3B82F6]' : 'border-gray-200 dark:border-[#2A2F3A]' }} bg-gray-50 dark:bg-transparent rounded-lg p-4 hover:border-[#4A8FF6] transition-all">
                                    <div class="flex items-center justify-between mb-3">
                                        <h6 class="font-bold text-sm dark:text-gray-200">{{ $mesFormateado }}</h6>
                                        @if($totalMes > 0)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $tasaCierreMes >= 70 ? 'bg-[#4ADE80]/20 text-[#4ADE80]' : ($tasaCierreMes >= 50 ? 'bg-[#FBBF24]/20 text-[#FBBF24]' : 'bg-[#F87171]/20 text-[#F87171]') }}">
                                            {{ $tasaCierreMes }}%
                                        </span>
                                        @else
                                        <span class="text-gray-400 dark:text-[#6B7280] text-xs">Sin datos</span>
                                        @endif
                                    </div>

                                    @if($totalMes > 0)
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-[#9CA3AF]">Total</span>
                                            <span class="text-sm font-bold dark:text-white">{{ $totalMes }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-[#2A2F3A] rounded-full h-2">
                                            <div class="bg-[#3B82F6] h-2 rounded-full" style="width: 100%"></div>
                                        </div>

                                        <div class="flex items-center justify-between mt-3">
                                            <span class="text-xs flex items-center gap-1">
                                                <i class="fas fa-check text-[#4ADE80]"></i> <span class="dark:text-gray-300">Cerrados</span>
                                            </span>
                                            <span class="text-sm font-bold text-[#4ADE80]">{{ $cerradosMes }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-[#2A2F3A] rounded-full h-2">
                                            <div class="bg-[#4ADE80] h-2 rounded-full" style="width: {{ min($tasaCierreMes, 100) }}%"></div>
                                        </div>

                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-gray-500 dark:text-[#9CA3AF]">Pendientes</span>
                                            <span class="text-sm font-bold text-[#F87171]">{{ $totalMes - $cerradosMes }}</span>
                                        </div>
                                    </div>
                                    @else
                                    <div class="text-center py-4 text-gray-400 dark:text-[#6B7280]">
                                        <i class="fas fa-inbox text-2xl mb-2"></i>
                                        <p class="text-xs">Sin tickets</p>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h5 class="text-sm font-semibold mb-3 flex items-center gap-2 dark:text-white">
                                <i class="fas fa-signal text-[#3B82F6]"></i> Distribución por Prioridad
                            </h5>
                            <div class="grid grid-cols-3 gap-4">
                                @php
                                $prioridades = [
                                'Alta' => ['color' => '#F87171', 'bg' => '#F87171', 'icon' => 'fa-exclamation-triangle'],
                                'Media' => ['color' => '#FBBF24', 'bg' => '#FBBF24', 'icon' => 'fa-exclamation-circle'],
                                'Baja' => ['color' => '#4ADE80', 'bg' => '#4ADE80', 'icon' => 'fa-info-circle']
                                ];
                                @endphp
                                @foreach($prioridades as $prioridad => $config)
                                <div class="rounded-lg p-4 bg-gray-50 dark:bg-[#1F2937] border border-transparent dark:border-[#2A2F3A]">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <i class="fas {{ $config['icon'] }}" style="color: {{ $config['color'] }};"></i>
                                            <span class="text-sm font-semibold dark:text-gray-200">{{ $prioridad }}</span>
                                        </div>
                                        <span class="text-lg font-bold dark:text-white">
                                            {{ $empleado['tickets_por_prioridad'][$prioridad] ?? 0 }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-[#2A2F3A] rounded-full h-3">
                                        <div class="h-3 rounded-full transition-all duration-300" style="background-color: {{ $config['bg'] }}; width: {{ $empleado['total'] > 0 ? min((($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100, 100) : 0 }}%"></div>
                                    </div>
                                    <p class="text-xs mt-1 text-gray-500 dark:text-[#9CA3AF]">
                                        {{ $empleado['total'] > 0 ? round((($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100, 1) : 0 }}% del total
                                    </p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-[#2A2F3A] rounded-lg">
                        <i class="fas fa-users text-5xl mb-4 text-gray-400 dark:text-[#6B7280]"></i>
                        <p class="text-lg font-semibold dark:text-gray-300">No hay métricas disponibles para empleados</p>
                        <p class="text-sm mt-2 text-gray-500 dark:text-[#9CA3AF]">Los empleados aparecerán aquí cuando tengan tickets asignados</p>
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

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
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
            grid: dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
            tooltipBg: dark ? 'rgba(15,17,21,0.95)' : '#FFFFFF',
            tooltipTexto: dark ? '#F3F4F6' : '#111827',
            tooltipBorder: dark ? '#2A2F3A' : '#E5E7EB',
            emptyDoughnut: dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'
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
                    backgroundColor: sumaEstados > 0 ? ['#F87171', '#FBBF24', '#4ADE80'] : [colores.emptyDoughnut],
                    borderColor: colores.tooltipBorder,
                    borderWidth: sumaEstados > 0 ? 2 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: colores.textoSecundario,
                            padding: 15
                        }
                    },
                    tooltip: {
                        enabled: sumaEstados > 0,
                        backgroundColor: colores.tooltipBg,
                        titleColor: colores.tooltipTexto,
                        bodyColor: colores.tooltipTexto,
                        borderColor: colores.tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label(context) {
                                return `${context.label}: ${context.parsed} tickets`;
                            }
                        }
                    }
                }
            }
        });

        // -----------------------------------------------------
        // Gráfica de tickets creados vs resueltos por día (Line)
        // -----------------------------------------------------
        const ctxResueltos = document.getElementById('chartResueltosPorDia').getContext('2d');
        const fechas = Object.keys(resueltosPorDia);
        const valoresResueltos = Object.values(resueltosPorDia);
        const valoresCreados = Object.values(creadosPorDia);

        const fechasFormateadas = fechas.map(fecha => {
            const d = new Date(fecha);
            return d.getDate() + '/' + (d.getMonth() + 1);
        });

        chartResueltos = new Chart(ctxResueltos, {
            type: 'line',
            data: {
                labels: fechasFormateadas,
                datasets: [{
                        label: 'Tickets Creados',
                        data: valoresCreados,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Tickets Resueltos',
                        data: valoresResueltos,
                        borderColor: '#22C55E',
                        backgroundColor: 'rgba(34, 197, 94, 0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6B7280'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6B7280'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#9CA3AF'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 17, 21, 0.95)',
                        titleColor: '#F3F4F6',
                        bodyColor: '#F3F4F6',
                        borderColor: '#2A2F3A',
                        borderWidth: 1,
                        padding: 12
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
                    backgroundColor: hasPrioridad ? ['#F87171', '#FBBF24', '#4ADE80'] : [colores.grid],
                    borderColor: hasPrioridad ? ['#F87171', '#FBBF24', '#4ADE80'] : ['transparent'],
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
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
                    tooltip: {
                        enabled: hasPrioridad,
                        backgroundColor: colores.tooltipBg,
                        titleColor: colores.tooltipTexto,
                        bodyColor: colores.tooltipTexto,
                        borderColor: colores.tooltipBorder,
                        borderWidth: 1,
                        padding: 12
                    }
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
                        backgroundColor: sumaClasificacion > 0 ? ['#F87171', '#3B82F6'] : [colores.emptyDoughnut],
                        borderColor: sumaClasificacion > 0 ? ['#F87171', '#3B82F6'] : [colores.tooltipBorder],
                        borderWidth: sumaClasificacion > 0 ? 2 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: colores.textoSecundario,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            enabled: sumaClasificacion > 0,
                            backgroundColor: colores.tooltipBg,
                            titleColor: colores.tooltipTexto,
                            bodyColor: colores.tooltipTexto,
                            borderColor: colores.tooltipBorder,
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed + ' tickets';
                                    return label;
                                }
                            }
                        }
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
                        backgroundColor: '#8B5CF6',
                        borderColor: '#8B5CF6',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
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
                        tooltip: {
                            enabled: hasTipos,
                            backgroundColor: colores.tooltipBg,
                            titleColor: colores.tooltipTexto,
                            bodyColor: colores.tooltipTexto,
                            borderColor: colores.tooltipBorder,
                            borderWidth: 1,
                            padding: 12
                        }
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
                            backgroundColor: '#3B82F6',
                            borderColor: '#2563EB',
                            borderWidth: 2,
                            borderRadius: 6,
                            barThickness: 'flex',
                            maxBarThickness: 60
                        },
                        {
                            label: 'Tiempo de Resolución',
                            data: hayDatos ? tiemposResolucion : [0],
                            backgroundColor: '#10B981',
                            borderColor: '#059669',
                            borderWidth: 2,
                            borderRadius: 6,
                            barThickness: 'flex',
                            maxBarThickness: 60
                        },
                        {
                            label: 'Tiempo Total',
                            data: hayDatos ? tiemposTotal : [0],
                            backgroundColor: '#F59E0B',
                            borderColor: '#D97706',
                            borderWidth: 2,
                            borderRadius: 6,
                            barThickness: 'flex',
                            maxBarThickness: 60
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                        tooltip: {
                            enabled: hayDatos,
                            backgroundColor: colores.tooltipBg,
                            titleColor: colores.tooltipTexto,
                            bodyColor: colores.tooltipTexto,
                            borderColor: colores.tooltipBorder,
                            borderWidth: 1,
                            padding: 14,
                            displayColors: true,
                            boxWidth: 10,
                            boxHeight: 10,
                            boxPadding: 5,
                            titleFont: {
                                size: 13,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' hrs';
                                }
                            }
                        },
                        datalabels: {
                            display: hayDatos,
                            color: function(context) {
                                return colores.texto;
                            },
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: function(value) {
                                return value.toFixed(2) + ' h';
                            },
                            anchor: 'end',
                            align: 'top',
                            offset: 8
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
            const coloresResponsables = [
                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', 
                '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'
            ];
            
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
                    backgroundColor: coloresResponsables[idx % coloresResponsables.length],
                    borderColor: coloresResponsables[idx % coloresResponsables.length],
                    borderWidth: 1
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
                        backgroundColor: '#E5E7EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
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
                        tooltip: {
                            enabled: hayDatos,
                            backgroundColor: colores.tooltipBg,
                            titleColor: colores.tooltipTexto,
                            bodyColor: colores.tooltipTexto,
                            borderColor: colores.tooltipBorder,
                            borderWidth: 1,
                            padding: 12,
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.x + ' tickets';
                                }
                            }
                        },
                        datalabels: {
                            display: false
                        }
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
            if (!chartEstado) {
                inicializarGraficas();
                inicializado = true;
            }
        }

        const canvasEmpleado = document.querySelector('[id^="chartEmpleado"]');
        if (canvasEmpleado && isElementVisible(canvasEmpleado)) {
            inicializarGraficasEmpleados();
            inicializado = true;
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

    // Observar cuando el tab cambie usando MutationObserver
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('[x-data*="tab"]');
        if (container) {
            const observer = new MutationObserver(function() {
                setTimeout(function() {
                    if (!chartEstado) {
                        inicializarGraficas();
                    }
                    inicializarGraficasEmpleados();
                }, 200);
            });

            observer.observe(container, {
                attributes: true,
                attributeFilter: ['x-cloak'],
                childList: true,
                subtree: true
            });
        }

        const productividadDiv = document.querySelector('[x-show*="tab === 2"]');
        if (productividadDiv) {
            const productividadObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const style = window.getComputedStyle(productividadDiv);
                        if (style.display !== 'none' && !productividadDiv.hasAttribute('x-cloak')) {
                            setTimeout(function() {
                                if (!chartEstado) {
                                    inicializarGraficas();
                                }
                                inicializarGraficasEmpleados();
                            }, 300);
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
                    if (!chartEstado) {
                        inicializarGraficas();
                    }
                    inicializarGraficasEmpleados();
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

                try {
                    window[chartKey] = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: mesesEspanolLabels,
                            datasets: [{
                                    label: 'Total de Tickets',
                                    data: totales,
                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                    borderColor: '#3B82F6',
                                    borderWidth: 2,
                                    borderRadius: 4
                                },
                                {
                                    label: 'Tickets Cerrados',
                                    data: cerrados,
                                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                    borderColor: '#22C55E',
                                    borderWidth: 2,
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        precision: 0,
                                        color: '#6B7280'
                                    },
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: '#6B7280'
                                    },
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 15,
                                        color: '#9CA3AF',
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 17, 21, 0.95)',
                                    titleColor: '#F3F4F6',
                                    bodyColor: '#F3F4F6',
                                    borderColor: '#2A2F3A',
                                    borderWidth: 1,
                                    padding: 12,
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
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creando gráfica para empleado ' + empleado.empleado_id + ':', error);
                    window[chartKey] = null;
                }
            });
        } catch (error) {
            console.error('Error en inicializarGraficasEmpleados:', error);
        }
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

    // También intentar cuando Alpine actualice el DOM
    if (window.Alpine) {
        document.addEventListener('alpine:updated', function() {
            setTimeout(function() {
                const productividadVisible = document.querySelector('[x-show*="tab === 2"]');
                if (productividadVisible) {
                    const style = window.getComputedStyle(productividadVisible);
                    if (style.display !== 'none') {
                        inicializarGraficas();
                        inicializarGraficasEmpleados();
                    }
                }
            }, 200);
        });
    }
</script>