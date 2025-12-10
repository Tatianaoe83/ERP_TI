<div class="space-y-6">
    <!-- Tarjetas de resumen -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total de tickets -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total de Tickets</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $metricasProductividad['total_tickets'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-ticket-alt text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Tickets cerrados -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tickets Cerrados</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $metricasProductividad['tickets_cerrados'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $metricasProductividad['total_tickets'] > 0 ? round(($metricasProductividad['tickets_cerrados'] / $metricasProductividad['total_tickets']) * 100, 1) : 0 }}% del total
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Tiempo promedio de resolución -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tiempo Promedio Resolución</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $metricasProductividad['tiempo_promedio_resolucion'] > 0 ? number_format($metricasProductividad['tiempo_promedio_resolucion'], 1) : '0' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">horas laborales</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-clock text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Tiempo promedio de respuesta -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tiempo Promedio Respuesta</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $metricasProductividad['tiempo_promedio_respuesta'] > 0 ? number_format($metricasProductividad['tiempo_promedio_respuesta'], 1) : '0' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">horas laborales</p>
                </div>
                <div class="bg-orange-100 rounded-full p-4">
                    <i class="fas fa-hourglass-half text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas principales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Distribución por estado -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribución por Estado</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chartEstado"></canvas>
            </div>
        </div>

        <!-- Tickets resueltos por día (últimos 30 días) -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tickets Resueltos (Últimos 30 días)</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chartResueltosPorDia"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráficas secundarias -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tendencias semanales -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tendencias Semanales</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chartTendenciasSemanales"></canvas>
            </div>
        </div>

        <!-- Tickets por prioridad -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tickets por Prioridad</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chartPrioridad"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla de tickets por responsable -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Rendimiento por Responsable TI</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cerrados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">En Progreso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendientes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasa de Cierre</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($metricasProductividad['tickets_por_responsable'] as $responsable)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $responsable['nombre'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $responsable['total'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $responsable['cerrados'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    {{ $responsable['en_progreso'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    {{ $responsable['pendientes'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                No hay datos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Métricas por Empleado -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Desempeño por Empleado TI</h3>
        <p class="text-sm text-gray-600 mb-6">Análisis mensual del rendimiento de cada responsable de TI (Últimos 6 meses)</p>
        
        <div class="space-y-8">
            @forelse($metricasProductividad['metricas_por_empleado'] as $empleado)
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gradient-to-br from-white to-gray-50">
                <!-- Encabezado del empleado -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-300">
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-user-tie text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-800">{{ $empleado['nombre'] }}</h4>
                            <p class="text-sm text-gray-500">Total acumulado: <span class="font-semibold text-gray-700">{{ $empleado['total'] }} tickets</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-center bg-white rounded-lg px-4 py-2 shadow-sm">
                            <p class="text-xs text-gray-500 mb-1">Tasa de Cierre</p>
                            <p class="text-2xl font-bold {{ $empleado['tasa_cierre'] >= 70 ? 'text-green-600' : ($empleado['tasa_cierre'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $empleado['tasa_cierre'] }}%
                            </p>
                        </div>
                        <div class="text-center bg-white rounded-lg px-4 py-2 shadow-sm">
                            <p class="text-xs text-gray-500 mb-1">Tiempo Promedio</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ $empleado['tiempo_promedio_resolucion'] > 0 ? number_format($empleado['tiempo_promedio_resolucion'], 1) : '0' }}h
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resumen por estado -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center border border-green-200">
                        <i class="fas fa-check-circle text-green-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600 mb-1">Cerrados</p>
                        <p class="text-3xl font-bold text-green-600">{{ $empleado['cerrados'] }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 text-center border border-yellow-200">
                        <i class="fas fa-clock text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600 mb-1">En Progreso</p>
                        <p class="text-3xl font-bold text-yellow-600">{{ $empleado['en_progreso'] }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 text-center border border-red-200">
                        <i class="fas fa-exclamation-circle text-red-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600 mb-1">Pendientes</p>
                        <p class="text-3xl font-bold text-red-600">{{ $empleado['pendientes'] }}</p>
                    </div>
                </div>

                <!-- Tarjetas por mes -->
                <div class="mb-6">
                    <h5 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-600"></i>
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
                        <div class="bg-white border-2 {{ $totalMes > 0 ? 'border-blue-300' : 'border-gray-200' }} rounded-lg p-4 hover:shadow-md transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <h6 class="font-bold text-gray-800 text-sm">{{ $mesFormateado }}</h6>
                                @if($totalMes > 0)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $tasaCierreMes >= 70 ? 'bg-green-100 text-green-700' : ($tasaCierreMes >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $tasaCierreMes }}%
                                </span>
                                @else
                                <span class="text-gray-400 text-xs">Sin datos</span>
                                @endif
                            </div>
                            
                            @if($totalMes > 0)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Total</span>
                                    <span class="text-sm font-bold text-gray-800">{{ $totalMes }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: 100%"></div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-3">
                                    <span class="text-xs text-gray-600 flex items-center gap-1">
                                        <i class="fas fa-check text-green-600"></i> Cerrados
                                    </span>
                                    <span class="text-sm font-bold text-green-600">{{ $cerradosMes }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $tasaCierreMes }}%"></div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-gray-600">Pendientes</span>
                                    <span class="text-sm font-bold text-red-600">{{ $totalMes - $cerradosMes }}</span>
                                </div>
                            </div>
                            @else
                            <div class="text-center py-4 text-gray-400">
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
                    <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-blue-600"></i>
                        Gráfica de Tendencia Mensual
                    </h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartEmpleado{{ $empleado['empleado_id'] }}"></canvas>
                    </div>
        </div>
        
                <!-- Tickets por prioridad -->
                <div>
                    <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                        <i class="fas fa-signal text-blue-600"></i>
                        Distribución por Prioridad
                    </h5>
                    <div class="grid grid-cols-3 gap-4">
                        @php
                            $prioridades = [
                                'Alta' => ['color' => 'red', 'bg' => 'bg-red-500', 'icon' => 'fa-exclamation-triangle'],
                                'Media' => ['color' => 'yellow', 'bg' => 'bg-yellow-500', 'icon' => 'fa-exclamation-circle'],
                                'Baja' => ['color' => 'green', 'bg' => 'bg-green-500', 'icon' => 'fa-info-circle']
                            ];
                        @endphp
                        @foreach($prioridades as $prioridad => $config)
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas {{ $config['icon'] }} text-{{ $config['color'] }}-600"></i>
                                    <span class="text-sm font-semibold text-gray-700">{{ $prioridad }}</span>
                                </div>
                                <span class="text-lg font-bold text-gray-800">
                                    {{ $empleado['tickets_por_prioridad'][$prioridad] ?? 0 }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="{{ $config['bg'] }} h-3 rounded-full transition-all duration-300" style="width: {{ $empleado['total'] > 0 ? (($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100 : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $empleado['total'] > 0 ? round((($empleado['tickets_por_prioridad'][$prioridad] ?? 0) / $empleado['total']) * 100, 1) : 0 }}% del total
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <i class="fas fa-users text-5xl mb-4 text-gray-400"></i>
                <p class="text-lg font-semibold">No hay métricas disponibles para empleados</p>
                <p class="text-sm mt-2">Los empleados aparecerán aquí cuando tengan tickets asignados</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Variables globales para almacenar las instancias de gráficas
let chartEstado, chartResueltos, chartTendencias, chartPrioridad;

function inicializarGraficas() {
    // Verificar que los elementos existan
    if (!document.getElementById('chartEstado')) {
        return;
    }

    // Destruir gráficas existentes si ya están creadas
    if (chartEstado) chartEstado.destroy();
    if (chartResueltos) chartResueltos.destroy();
    if (chartTendencias) chartTendencias.destroy();
    if (chartPrioridad) chartPrioridad.destroy();

    // Datos para las gráficas
    const distribucionEstado = @json($metricasProductividad['distribucion_estado']);
    const resueltosPorDia = @json($metricasProductividad['resueltos_por_dia']);
    const tendenciasSemanales = @json($metricasProductividad['tendencias_semanales']);
    const ticketsPorPrioridad = @json($metricasProductividad['tickets_por_prioridad']);

    // Gráfica de distribución por estado (Doughnut)
    const ctxEstado = document.getElementById('chartEstado').getContext('2d');
    chartEstado = new Chart(ctxEstado, {
        type: 'doughnut',
        data: {
            labels: Object.keys(distribucionEstado),
            datasets: [{
                data: Object.values(distribucionEstado),
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',  // Rojo para Pendiente
                    'rgba(251, 191, 36, 0.8)', // Amarillo para En progreso
                    'rgba(34, 197, 94, 0.8)'   // Verde para Cerrado
                ],
                borderColor: [
                    'rgba(239, 68, 68, 1)',
                    'rgba(251, 191, 36, 1)',
                    'rgba(34, 197, 94, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
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

    // Gráfica de tickets resueltos por día (Line)
    const ctxResueltos = document.getElementById('chartResueltosPorDia').getContext('2d');
    const fechas = Object.keys(resueltosPorDia);
    const valores = Object.values(resueltosPorDia);
    
    // Formatear fechas para mostrar solo día/mes
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
                borderColor: 'rgba(34, 197, 94, 1)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Gráfica de tendencias semanales (Bar)
    const ctxTendencias = document.getElementById('chartTendenciasSemanales').getContext('2d');
    const semanas = Object.keys(tendenciasSemanales);
    const creados = semanas.map(semana => tendenciasSemanales[semana].creados);
    const resueltos = semanas.map(semana => tendenciasSemanales[semana].resueltos);

    chartTendencias = new Chart(ctxTendencias, {
        type: 'bar',
        data: {
            labels: semanas,
            datasets: [
                {
                    label: 'Creados',
                    data: creados,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Resueltos',
                    data: resueltos,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 1
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
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Gráfica de tickets por prioridad (Bar horizontal)
    const ctxPrioridad = document.getElementById('chartPrioridad').getContext('2d');
    chartPrioridad = new Chart(ctxPrioridad, {
        type: 'bar',
        data: {
            labels: Object.keys(ticketsPorPrioridad),
            datasets: [{
                label: 'Tickets',
                data: Object.values(ticketsPorPrioridad),
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',  // Alta
                    'rgba(251, 191, 36, 0.8)', // Media
                    'rgba(34, 197, 94, 0.8)'   // Baja
                ],
                borderColor: [
                    'rgba(239, 68, 68, 1)',
                    'rgba(251, 191, 36, 1)',
                    'rgba(34, 197, 94, 1)'
                ],
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
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
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
        // Verificar que no se hayan inicializado ya
        if (!chartEstado) {
            inicializarGraficas();
            inicializado = true;
        }
    }
    
    // Inicializar gráficas de empleados si hay canvas visibles
    const canvasEmpleado = document.querySelector('[id^="chartEmpleado"]');
    if (canvasEmpleado && isElementVisible(canvasEmpleado)) {
        inicializarGraficasEmpleados();
        inicializado = true;
    }
    
    return inicializado;
}

// Intentar inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Intentar múltiples veces para asegurar que Alpine.js haya renderizado
    let intentos = 0;
    const maxIntentos = 10;
    
    const intervalo = setInterval(function() {
        intentos++;
        if (inicializarCuandoVisible() || intentos >= maxIntentos) {
            clearInterval(intervalo);
        }
    }, 200);
    
    // También intentar inmediatamente
    setTimeout(inicializarCuandoVisible, 100);
});

// Observar cuando el tab cambie usando MutationObserver
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('[x-data*="tab"]');
    if (container) {
        const observer = new MutationObserver(function() {
            // Intentar inicializar gráficas cuando el tab cambie
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
    
    // Observar cambios en el atributo x-show del div de productividad
    const productividadDiv = document.querySelector('[x-show*="tab === 2"]');
    if (productividadDiv) {
        const productividadObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const style = window.getComputedStyle(productividadDiv);
                    if (style.display !== 'none' && !productividadDiv.hasAttribute('x-cloak')) {
                        // El tab de productividad está visible
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
    
    // También escuchar eventos de Alpine.js cuando cambie el tab
    if (window.Alpine) {
        // Esperar a que Alpine esté listo
        setTimeout(function() {
            inicializarCuandoVisible();
        }, 300);
    }
    
    // Intentar múltiples veces para asegurar que se inicialicen
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

// Función para inicializar gráficas de empleados
function inicializarGraficasEmpleados() {
    try {
        const metricasEmpleados = @json($metricasProductividad['metricas_por_empleado']);
        
        if (!metricasEmpleados || metricasEmpleados.length === 0) {
            console.log('No hay métricas de empleados disponibles');
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
            
            if (!canvas) {
                console.log('Canvas no encontrado:', canvasId);
                return;
            }
            
            // Verificar si el canvas está visible
            if (!isElementVisible(canvas)) {
                console.log('Canvas no visible:', canvasId);
                return;
            }
            
            // Verificar si ya existe una gráfica para este canvas
            const chartKey = 'chartEmpleado' + empleado.empleado_id;
            if (window[chartKey]) {
                // Verificar que sea una instancia válida de Chart antes de destruir
                if (window[chartKey] instanceof Chart && typeof window[chartKey].destroy === 'function') {
                    try {
                        window[chartKey].destroy();
                    } catch (e) {
                        console.log('Error al destruir gráfica anterior:', e);
                    }
                }
                window[chartKey] = null;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.log('No se pudo obtener el contexto del canvas:', canvasId);
                return;
            }
    
            // Obtener meses en orden (de más antiguo a más reciente)
            const meses = Object.keys(empleado.tickets_por_mes).reverse();
            // Convertir a español
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
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 2,
                                borderRadius: 4
                            },
                            {
                                label: 'Tickets Cerrados',
                                data: cerrados,
                                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                borderColor: 'rgba(34, 197, 94, 1)',
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
                                    precision: 0
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
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
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
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
                console.log('Gráfica inicializada para empleado:', empleado.empleado_id);
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
    // Destruir todas las gráficas de empleados existentes
    const metricasEmpleados = @json($metricasProductividad['metricas_por_empleado']);
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

