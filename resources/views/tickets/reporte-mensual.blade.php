@extends('layouts.app')

@section('content')
<div x-data="{ tab: 1 }" class="px-2">
    
    <!-- Encabezado con selector de mes/año y botón de exportar -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Reporte Mensual de Tickets</h1>
        
        <div class="flex items-center gap-4">
            <!-- Selector de mes y año -->
            <form method="GET" action="{{ route('tickets.reporte-mensual') }}" class="flex items-center gap-2">
                <select name="mes" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create($anio, $i, 1)->locale('es')->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
                
                <select name="anio" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @for($i = now()->year; $i >= now()->year - 5; $i--)
                        <option value="{{ $i }}" {{ $anio == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
                
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Consultar
                </button>
            </form>
            
            <!-- Botón de exportar a Excel -->
            <a href="{{ route('tickets.exportar-reporte-mensual-excel', ['mes' => $mes, 'anio' => $anio]) }}" 
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-file-excel mr-2"></i>Exportar a Excel
            </a>
        </div>
    </div>

    <!-- Pestañas -->
    <div class="flex justify-start mb-4">
        <div
            class="relative grid grid-cols-2 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1 shadow-sm w-full max-w-md"
            role="tablist">
            <div
                class="absolute top-1 bottom-1 rounded-md bg-gradient-to-r from-blue-500 to-blue-700 shadow-md transition-all duration-300 ease-in-out"
                :style="`left:${(tab-1)*100/2}%; width:${100/2}%`"></div>

            <button
                @click="tab = 1"
                :class="tab === 1 ? 'text-white' : 'text-gray-600 hover:text-gray-800'"
                class="relative z-10 block rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fas fa-chart-bar text-xs"></i>
                    <span>Resumen</span>
                </span>
            </button>

            <button
                @click="tab = 2"
                :class="tab === 2 ? 'text-white' : 'text-gray-600 hover:text-gray-800'"
                class="relative z-10 block rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fas fa-list text-xs"></i>
                    <span>Tickets</span>
                </span>
            </button>
        </div>
    </div>

    <!-- Contenido de las pestañas -->
    <div class="mt-4">
        <!-- Pestaña 1: Resumen -->
        <div
            x-show="tab === 1"
            x-transition.opacity
            x-cloak
            class="space-y-6">
            
            <!-- Métricas generales -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <p class="text-sm font-medium text-gray-600">Total de Tickets</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $resumen['total_tickets'] }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <p class="text-sm font-medium text-gray-600">Tickets Cerrados</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $resumen['tickets_cerrados'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $resumen['porcentaje_cumplimiento'] }}% del total</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <p class="text-sm font-medium text-gray-600">Promedio Tiempo Respuesta</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($resumen['promedio_tiempo_respuesta'], 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">horas</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                    <p class="text-sm font-medium text-gray-600">Promedio Tiempo Resolución</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($resumen['promedio_tiempo_resolucion'], 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">horas</p>
                </div>
            </div>

            <!-- Incidencias por Gerencia -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Incidencias por Gerencia</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gerencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Incidencias</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resueltos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable de Resolución</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($resumen['incidencias_por_gerencia'] as $gerenciaData)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $gerenciaData['gerencia'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ $gerenciaData['total'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ $gerenciaData['resueltos'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @foreach($gerenciaData['por_responsable'] as $responsable => $cantidad)
                                        <div class="mb-1">{{ $responsable }}: <span class="font-semibold">{{ $cantidad }}</span></div>
                                    @endforeach
                                    @if(empty($gerenciaData['por_responsable']))
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totales por Empleado -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Totales por Empleado</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cerrados</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">En Progreso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendientes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($resumen['totales_por_empleado'] as $empleado)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $empleado['empleado'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ $empleado['total'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ $empleado['cerrados'] }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $empleado['en_progreso'] }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ $empleado['pendientes'] }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Tickets -->
        <div
            x-show="tab === 2"
            x-transition.opacity
            x-cloak>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Lista de Tickets</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"># Ticket</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Inicio Progreso</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Fin Progreso</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempo Respuesta</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempo Resolución</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridad</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gerencia</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado Creador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado Resolutor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clasificación</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tertipo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tickets as $ticket)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ticket->TicketID }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $ticket->Descripcion }}">{{ Str::limit($ticket->Descripcion, 50) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->FechaInicioProgreso ? $ticket->FechaInicioProgreso->format('d/m/Y H:i') : '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->FechaFinProgreso ? $ticket->FechaFinProgreso->format('d/m/Y H:i') : '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->tiempo_respuesta ? number_format($ticket->tiempo_respuesta, 2) . 'h' : '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->tiempo_resolucion ? number_format($ticket->tiempo_resolucion, 2) . 'h' : '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $ticket->Prioridad == 'Alta' ? 'bg-red-100 text-red-800' : ($ticket->Prioridad == 'Media' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                        {{ $ticket->Prioridad }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $ticket->Estatus == 'Cerrado' ? 'bg-green-100 text-green-800' : ($ticket->Estatus == 'En progreso' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $ticket->Estatus }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($ticket->empleado && $ticket->empleado->gerencia)
                                        {{ $ticket->empleado->gerencia->NombreGerencia ?? 'Sin gerencia' }}
                                    @else
                                        Sin gerencia
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin empleado' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin responsable' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($ticket->tipoticket)
                                        {{ $ticket->tipoticket->NombreTipo ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($ticket->tipoticket && $ticket->tipoticket->subtipoid)
                                        {{ $ticket->tipoticket->subtipoid->NombreSubtipo ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($ticket->tipoticket && $ticket->tipoticket->subtipoid && $ticket->tipoticket->subtipoid->tertipoid)
                                        {{ $ticket->tipoticket->subtipoid->tertipoid->NombreTertipo ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="15" class="px-4 py-4 text-center text-sm text-gray-500">No hay tickets disponibles para este mes</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

