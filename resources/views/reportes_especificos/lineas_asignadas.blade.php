@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#101D49] dark:text-white mb-2">Listado de Líneas Asignadas</h1>
            <p class="text-gray-600 dark:text-gray-300">Reporte de líneas telefónicas asignadas a empleados y obras</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('reportes-especificos.export-lineas-asignadas', $filtros) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <i class="fas fa-download mr-2"></i>Descargar PDF
            </a>
            <a href="{{ route('reportes-especificos.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-white mb-4">
            <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
        </h3>
        
        <form method="GET" action="{{ route('reportes-especificos.lineas-asignadas') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Empleado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Empleado</label>
                <select name="empleado_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los empleados</option>
                    @foreach(\App\Models\Empleados::whereNull('deleted_at')->get() as $empleado)
                        <option value="{{ $empleado->EmpleadoID }}" {{ $filtros['empleado_id'] == $empleado->EmpleadoID ? 'selected' : '' }}>
                            {{ $empleado->NombreEmpleado }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Línea -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Línea</label>
                <select name="linea_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todas las líneas</option>
                    @foreach(\App\Models\LineasTelefonicas::whereNull('deleted_at')->get() as $linea)
                        <option value="{{ $linea->LineaID }}" {{ $filtros['linea_id'] == $linea->LineaID ? 'selected' : '' }}>
                            {{ $linea->Numero }} - {{ $linea->Tipo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Estatus -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estatus</label>
                <select name="estatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los estatus</option>
                    <option value="Activo" {{ $filtros['estatus'] == 'Activo' ? 'selected' : '' }}>Activo</option>
                    <option value="Inactivo" {{ $filtros['estatus'] == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    <option value="Suspendido" {{ $filtros['estatus'] == 'Suspendido' ? 'selected' : '' }}>Suspendido</option>
                    <option value="Cancelado" {{ $filtros['estatus'] == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>

            <!-- Fecha Desde -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Botones -->
            <div class="md:col-span-2 lg:col-span-4 flex space-x-3">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i>Aplicar Filtros
                </button>
                <a href="{{ route('reportes-especificos.lineas-asignadas') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Limpiar Filtros
                </a>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-[#101D49] dark:text-white">
                    Resultados del Reporte
                </h3>
                <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-3 py-1 rounded-full text-sm font-medium">
                    {{ $resultado->count() }} registros encontrados
                </span>
            </div>

            @if($resultado->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Empleado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Línea</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Obra</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Asignación</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($resultado as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                         {{ $item->empleado_nombre }}
                                     </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->linea_numero }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->linea_tipo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->obra_nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($item->FechaAsignacion)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $estatusColors = [
                                                'Activo' => 'bg-green-100 text-green-800',
                                                'Inactivo' => 'bg-gray-100 text-gray-800',
                                                'Suspendido' => 'bg-yellow-100 text-yellow-800',
                                                'Cancelado' => 'bg-red-100 text-red-800'
                                            ];
                                            $colorClass = $estatusColors[$item->Estatus] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                            {{ $item->Estatus }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->Observaciones ?? 'Sin observaciones' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-phone text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No se encontraron resultados</h3>
                    <p class="text-gray-500 dark:text-gray-400">Intenta ajustar los filtros de búsqueda para encontrar líneas asignadas.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
