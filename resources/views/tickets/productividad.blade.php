<div class="space-y-6  min-h-screen p-6" id="productividad-container">
    <!-- Encabezado con selector de mes/año y botón de exportar -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Reporte de Productividad</h2>
        
        <div class="flex items-center gap-4" 
             x-data="{ 
                 mes: {{ $mes ?? now()->month }}, 
                 anio: {{ $anio ?? now()->year }}, 
                 cargando: false,
                 cargarProductividad() {
                     this.cargando = true;
                     const mes = this.mes;
                     const anio = this.anio;
                     const productividadContainer = document.getElementById('productividad-container');
                     
                     fetch(`{{ route('tickets.productividad-ajax') }}?mes=${mes}&anio=${anio}`)
                         .then(response => response.json())
                         .then(data => {
                             if (data.success && productividadContainer) {
                                 productividadContainer.innerHTML = data.html;
                                 // Reinicializar Alpine.js
                                 if (window.Alpine) {
                                     window.Alpine.initTree(productividadContainer);
                                 }
                                 // Reinicializar gráficas después de un breve delay
                                 setTimeout(() => {
                                     if (typeof inicializarGraficas === 'function') {
                                         inicializarGraficas();
                                     }
                                     if (typeof inicializarGraficasEmpleados === 'function') {
                                         inicializarGraficasEmpleados();
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
            <!-- Selector de mes y año -->
        <div class="flex items-center gap-2">
            <select
                x-model="mes"
                @change="cargarProductividad()"
                :disabled="cargando"
                class="
                    px-3 py-2 rounded-lg
                    text-gray-800 border border-gray-300
                    dark:bg-[#1F2937] dark dark:border-[#2A2F3A]
                    focus:ring-2 focus:ring-[#2563EB] focus:border-[#2563EB]
                    disabled:bg-gray-100 disabled:text-gray-400
                    dark:disabled:dark:disabled:text-[#6B7280]
                    disabled:cursor-not-allowed
                "
            >
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">
                        {{ \Carbon\Carbon::create(now()->year, $i, 1)->locale('es')->isoFormat('MMMM') }}
                    </option>
                @endfor
            </select>

            <select
                x-model="anio"
                @change="cargarProductividad()"
                :disabled="cargando"
                class="
                    px-3 py-2 rounded-lg
                    text-gray-800 border border-gray-300
                    dark:bg-[#1F2937] dark dark:border-[#2A2F3A]
                    focus:ring-2 focus:ring-[#2563EB] focus:border-[#2563EB]
                    disabled:bg-gray-100 disabled:text-gray-400
                    dark:disabled:dark:disabled:text-[#6B7280]
                    disabled:cursor-not-allowed
                "
            >
                @for($i = now()->year; $i >= now()->year - 5; $i--)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>

            <div x-show="cargando" class="ml-2">
                <i class="fas fa-spinner fa-spin text-[#2563EB]"></i>
            </div>
        </div>

                
                <!-- Botón de exportar a Excel -->
        <a href="{{ route('tickets.exportar-reporte-mensual-excel', ['mes' => $mes ?? now()->month, 'anio' => $anio ?? now()->year]) }}"
        class="
                px-4 py-2 rounded-lg font-medium transition-colors
                flex items-center gap-2
                bg-green-500 hover:bg-green-600 text-white
        ">
            <i class="fas fa-file-excel mr-2"></i>
            Exportar a Excel
        </a>
    </div>
    <script id="productividad-json-data" type="application/json">
            {!! json_encode($metricasProductividad) !!}
        </script>
</div>

<!-- Tarjetas de resumen -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

    <!-- Total de tickets -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium  dark:text-[#9CA3AF]">
                    Total de Tickets
                </p>
                <p class="text-3xl font-bold  dark mt-2">
                    {{ $metricasProductividad['total_tickets'] }}
                </p>
            </div>
            <div class="rounded-full p-4 bg-blue-500/15">
                <i class="fas fa-ticket-alt text-blue-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Tickets cerrados -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium  dark:text-[#9CA3AF]">
                    Tickets Cerrados
                </p>
                <p class="text-3xl font-bold  dark mt-2">
                    {{ $metricasProductividad['tickets_cerrados'] }}
                </p>
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

    <!-- Tiempo promedio de resolución -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium  dark:text-[#9CA3AF]">
                    Tiempo Promedio Resolución
                </p>
                <p class="text-3xl font-bold  dark mt-2">
                    {{ $metricasProductividad['tiempo_promedio_resolucion'] > 0
                        ? number_format($metricasProductividad['tiempo_promedio_resolucion'], 1)
                        : '0' }}
                </p>
                <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">
                    horas laborales
                </p>
            </div>
            <div class="rounded-full p-4 bg-purple-500/15">
                <i class="fas fa-clock text-purple-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Tiempo promedio de respuesta -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium  dark:text-[#9CA3AF]">
                    Tiempo Promedio Respuesta
                </p>
                <p class="text-3xl font-bold  dark mt-2">
                    {{ $metricasProductividad['tiempo_promedio_respuesta'] > 0
                        ? number_format($metricasProductividad['tiempo_promedio_respuesta'], 1)
                        : '0' }}
                </p>
                <p class="text-xs text-gray-400 dark:text-[#6B7280] mt-1">
                    horas laborales
                </p>
            </div>
            <div class="rounded-full p-4 bg-orange-500/15">
                <i class="fas fa-hourglass-half text-orange-500 text-2xl"></i>
            </div>
        </div>
    </div>

</div>

<!-- Gráficas principales -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Distribución por estado -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <h3 class="text-lg font-semibold mb-4
                    dark">
            Distribución por Estado
        </h3>

        <div class="relative h-[300px]">
            <canvas id="chartEstado"></canvas>
        </div>
    </div>

    <!-- Tickets resueltos por día -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <h3 class="text-lg font-semibold mb-4
                    dark">
            Tickets Resueltos (Últimos 30 días)
        </h3>

        <div class="relative h-[300px]">
            <canvas id="chartResueltosPorDia"></canvas>
        </div>
    </div>

</div>

<!-- Gráficas secundarias -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Tendencias semanales -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <h3 class="text-lg font-semibold mb-4
                    dark">
            Tendencias Semanales
        </h3>

        <div class="relative h-[300px]">
            <canvas id="chartTendenciasSemanales"></canvas>
        </div>
    </div>

    <!-- Tickets por prioridad -->
    <div class="
        rounded-lg p-6 border
        border-gray-200
        dark:dark:border-[#2A2F3A]
    ">
        <h3 class="text-lg font-semibold mb-4
                    dark">
            Tickets por Prioridad
        </h3>

        <div class="relative h-[300px]">
            <canvas id="chartPrioridad"></canvas>
        </div>
    </div>

</div>

<!-- Gráfica de clasificaciones -->
<div class="
    rounded-lg p-6 border
    border-gray-200
    dark:dark:border-[#2A2F3A]
">
    <h3 class="text-lg font-semibold mb-4
                dark">
        Distribución por Clasificación (En Progreso y Cerrados)
    </h3>

    <div class="relative h-[300px]">
        <canvas id="chartClasificacion"></canvas>
    </div>
</div>


    <!-- Tabla de tickets por responsable -->
    <div class="rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Rendimiento por Responsable TI</h3>
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Responsable</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Cerrados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">En Progreso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Pendientes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Problemas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Servicios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Tasa de Cierre</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($metricasProductividad['tickets_por_responsable'] as $responsable)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ">
                                {{ $responsable['nombre'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                {{ $responsable['total'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $responsable['cerrados'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    {{ $responsable['en_progreso'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    {{ $responsable['pendientes'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    {{ $responsable['problemas'] ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $responsable['servicios'] ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                @if($responsable['total'] > 0)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ round(($responsable['cerrados'] / $responsable['total']) * 100, 1) }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm ">
                                No hay datos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Métricas por Empleado -->
    <div class="rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Desempeño por Empleado TI</h3>
        <p class="text-sm mb-6">Análisis mensual del rendimiento de cada responsable de TI (Últimos 6 meses)</p>
        
        <div class="space-y-8">
            @forelse($metricasProductividad['metricas_por_empleado'] as $empleado)
            <div class="rounded-lg p-6 hover: transition-colors">
                <!-- Encabezado del empleado -->
<div class="flex items-center justify-between mb-6 pb-4 border-b-2">
    <div class="flex items-center gap-4">
        <div class="rounded-full p-3" style="background-color: rgba(59, 130, 246, 0.15);">
            <i class="fas fa-user-tie text-xl"></i>
        </div>

        <div>
            <h4 class="text-xl font-bold">
                {{ $empleado['nombre'] ?? 'Sin nombre' }}
            </h4>
            <p class="text-sm text-[#9CA3AF]">
                Total acumulado:
                <span class="font-semibold">
                    {{ $empleado['total'] ?? 0 }} tickets
                </span>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-6">
        <!-- Tasa de cierre -->
        @php
            $tasa = $empleado['tasa_cierre'] ?? 0;
            $colorTasa = $tasa >= 70
                ? 'text-[#4ADE80]'
                : ($tasa >= 50 ?  : 'text-[#F87171]');
        @endphp

        <div class="text-center rounded-lg px-4 py-2">
            <p class="text-xs mb-1">Tasa de Cierre</p>
            <p class="text-2xl font-bold {{ $colorTasa }}">
                {{ $tasa }}%
            </p>
        </div>

        <!-- Tiempo promedio -->
        <div class="text-center rounded-lg px-4 py-2">
            <p class="text-xs mb-1">Tiempo Promedio</p>
            <p class="text-2xl font-bold text-[#3B82F6]">
                {{ isset($empleado['tiempo_promedio_resolucion']) && $empleado['tiempo_promedio_resolucion'] > 0
                    ? number_format($empleado['tiempo_promedio_resolucion'], 1)
                    : '0' }}h
            </p>
        </div>
    </div>
</div>


                <!-- Resumen por estado -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg p-4 text-center" style="background-color: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                        <i class="fas fa-check-circle text-[#4ADE80] text-2xl mb-2"></i>
                        <p class="text-sm mb-1">Cerrados</p>
                        <p class="text-3xl font-bold text-[#4ADE80]">{{ $empleado['cerrados'] }}</p>
                    </div>
                    <div class="rounded-lg p-4 text-center" style="background-color: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.3);">
                        <i class="fas fa-clock  text-2xl mb-2"></i>
                        <p class="text-sm mb-1">En Progreso</p>
                        <p class="text-3xl font-bold ">{{ $empleado['en_progreso'] }}</p>
                    </div>
                    <div class="rounded-lg p-4 text-center" style="background-color: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.3);">
                        <i class="fas fa-exclamation-circle text-[#F87171] text-2xl mb-2"></i>
                        <p class="text-sm mb-1">Pendientes</p>
                        <p class="text-3xl font-bold text-[#F87171]">{{ $empleado['pendientes'] }}</p>
                    </div>
                </div>

                <!-- Tarjetas por mes -->
                <div class="mb-6">
                    <h5 class="text-sm font-semibold mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-[#3B82F6]"></i>
                        Desempeño Mensual (Últimos 6 meses)
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
                        <div class="border-2 {{ $totalMes > 0 ? 'border-[#3B82F6]' : 'border-[#2A2F3A]' }} rounded-lg p-4 hover:border-[#4A8FF6] transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <h6 class="font-bold text-sm">{{ $mesFormateado }}</h6>
                                @if($totalMes > 0)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $tasaCierreMes >= 70 ? 'bg-[#4ADE80]/20 text-[#4ADE80]' : ($tasaCierreMes >= 50 ? 'bg-[#FBBF24]/20 ' : 'bg-[#F87171]/20 text-[#F87171]') }}">
                                    {{ $tasaCierreMes }}%
                                </span>
                                @else
                                <span class="text-[#6B7280] text-xs">Sin datos</span>
                                @endif
                            </div>
                            
                            @if($totalMes > 0)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-[#9CA3AF]">Total</span>
                                    <span class="text-sm font-bold">{{ $totalMes }}</span>
                                </div>
                                <div class="w-full bg-[#2A2F3A] rounded-full h-2">
                                    <div class="bg-[#3B82F6] h-2 rounded-full" style="width: 100%"></div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-3">
                                    <span class="text-xs flex items-center gap-1">
                                        <i class="fas fa-check text-[#4ADE80]"></i> Cerrados
                                    </span>
                                    <span class="text-sm font-bold text-[#4ADE80]">{{ $cerradosMes }}</span>
                                </div>
                                <div class="w-full bg-[#2A2F3A] rounded-full h-2">
                                    <div class="bg-[#4ADE80] h-2 rounded-full" style="width: {{ $tasaCierreMes }}%"></div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-[#9CA3AF]">Pendientes</span>
                                    <span class="text-sm font-bold text-[#F87171]">{{ $totalMes - $cerradosMes }}</span>
                                </div>
                            </div>
                            @else
                            <div class="text-center py-4 text-[#6B7280]">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p class="text-xs">Sin tickets</p>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Gráfica de tickets por mes -->
                <div class="mb-6">
                    <h5 class="text-sm font-semibold mb-3 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-[#3B82F6]"></i>
                        Gráfica de Tendencia Mensual
                    </h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartEmpleado{{ $empleado['empleado_id'] }}"></canvas>
                    </div>
        </div>
        
                <!-- Tickets por prioridad -->
                <div>
                    <h5 class="text-sm font-semibold mb-3 flex items-center gap-2">
                        <i class="fas fa-signal text-[#3B82F6]"></i>
                        Distribución por Prioridad
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
                        <div class="rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas {{ $config['icon'] }}" style="color: {{ $config['color'] }};"></i>
                                    <span class="text-sm font-semibold">{{ $prioridad }}</span>
                                </div>
                                <span class="text-lg font-bold">
                                    {{ $empleado['tickets_por_prioridad'][$prioridad] ?? 0 }}
                                </span>
                            </div>
                            <div class="w-full bg-[#2A2F3A] rounded-full h-3">
                                <div class="h-3 rounded-full transition-all duration-300" style="background-color: {{ $config['bg'] }}; width: {{ $empleado['total'] > 0 ? (($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100 : 0 }}%"></div>
                            </div>
                            <p class="text-xs mt-1">
                                {{ $empleado['total'] > 0 ? round((($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100, 1) : 0 }}% del total
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12 border-2 border-dashed border-[#2A2F3A] rounded-lg">
                <i class="fas fa-users text-5xl mb-4 text-[#6B7280]"></i>
                <p class="text-lg font-semibold">No hay métricas disponibles para empleados</p>
                <p class="text-sm mt-2 text-[#9CA3AF]">Los empleados aparecerán aquí cuando tengan tickets asignados</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}
    
// Variables globales para almacenar las instancias de gráficas
let chartEstado, chartResueltos, chartTendencias, chartPrioridad, chartClasificacion;

// ==========================================
// FUNCIÓN PARA LEER DATOS FRESCOS DEL AJAX
// ==========================================
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

    // Datos para las gráficas extraídos dinámicamente
    const distribucionEstado = metricasData.distribucion_estado || {};
    const resueltosPorDia = metricasData.resueltos_por_dia || {};
    const tendenciasSemanales = metricasData.tendencias_semanales || {};
    const ticketsPorPrioridad = metricasData.tickets_por_prioridad || {};
    const ticketsPorClasificacion = metricasData.tickets_por_clasificacion || {};

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
                    labels: { color: colores.textoSecundario, padding: 15 }
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
                        label(context) { return `${context.label}: ${context.parsed} tickets`; }
                    }
                }
            }
        }
    });

    // -----------------------------------------------------
    // Gráfica de tickets resueltos por día (Line)
    // -----------------------------------------------------
    const ctxResueltos = document.getElementById('chartResueltosPorDia').getContext('2d');
    const fechas = Object.keys(resueltosPorDia);
    const valores = Object.values(resueltosPorDia);
    
    const fechasFormateadas = fechas.map(fecha => {
        const d = new Date(fecha);
        return d.getDate() + '/' + (d.getMonth() + 1);
    });

    chartResueltos = new Chart(ctxResueltos, {
        type: 'line',
        data: {
            labels: fechasFormateadas,
            datasets: [{
                label: 'Tickets Resueltos',
                data: valores,
                borderColor: '#22C55E',
                backgroundColor: 'rgba(34, 197, 94, 0.15)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, color: '#6B7280' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                x: { ticks: { color: '#6B7280' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } }
            },
            plugins: {
                legend: { display: true, position: 'top', labels: { color: '#9CA3AF' } },
                tooltip: { backgroundColor: 'rgba(15, 17, 21, 0.95)', titleColor: '#F3F4F6', bodyColor: '#F3F4F6', borderColor: '#2A2F3A', borderWidth: 1, padding: 12 }
            }
        }
    });

    // -----------------------------------------------------
    // Gráfica de tendencias semanales (Bar)
    // -----------------------------------------------------
    const ctxTendencias = document.getElementById('chartTendenciasSemanales').getContext('2d');
    const semanas = Object.keys(tendenciasSemanales);
    const creados = semanas.map(semana => tendenciasSemanales[semana].creados);
    const resueltos = semanas.map(semana => tendenciasSemanales[semana].resueltos);

    chartTendencias = new Chart(ctxTendencias, {
        type: 'bar',
        data: {
            labels: semanas,
            datasets: [
                { label: 'Creados', data: creados, backgroundColor: 'rgba(59, 130, 246, 0.8)', borderColor: '#3B82F6', borderWidth: 1 },
                { label: 'Resueltos', data: resueltos, backgroundColor: 'rgba(34, 197, 94, 0.8)', borderColor: '#22C55E', borderWidth: 1 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, color: '#6B7280' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                x: { ticks: { color: '#6B7280' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } }
            },
            plugins: {
                legend: { display: true, position: 'top', labels: { color: '#9CA3AF' } },
                tooltip: { backgroundColor: 'rgba(15, 17, 21, 0.95)', titleColor: '#F3F4F6', bodyColor: '#F3F4F6', borderColor: '#2A2F3A', borderWidth: 1, padding: 12 }
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
                x: { beginAtZero: true, ticks: { stepSize: 1, color: colores.textoSecundario }, grid: { color: colores.grid } },
                y: { ticks: { color: colores.textoSecundario }, grid: { color: colores.grid } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: hasPrioridad, backgroundColor: colores.tooltipBg, titleColor: colores.tooltipTexto, bodyColor: colores.tooltipTexto, borderColor: colores.tooltipBorder, borderWidth: 1, padding: 12 }
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
                        labels: { color: colores.textoSecundario, padding: 15, font: { size: 12 } }
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
                                if (label) { label += ': '; }
                                label += context.parsed + ' tickets';
                                return label;
                            }
                        }
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
            'Jan': 'Ene', 'Feb': 'Feb', 'Mar': 'Mar', 'Apr': 'Abr',
            'May': 'May', 'Jun': 'Jun', 'Jul': 'Jul', 'Aug': 'Ago',
            'Sep': 'Sep', 'Oct': 'Oct', 'Nov': 'Nov', 'Dec': 'Dic'
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
                        datasets: [
                            {
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
                                titleFont: { size: 14 },
                                bodyFont: { size: 13 },
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
