@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#101D49] dark:text-white mb-2">Listado de Equipos Asignados</h1>
            <p class="text-gray-600 dark:text-gray-300">Inventario completo de equipos de cómputo asignados a empleados</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('reportes-especificos.export-equipos-asignados', $filtros) }}" 
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
        
        <form method="GET" action="{{ route('reportes-especificos.equipos-asignados') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Empleado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Empleado</label>
                <select name="empleado_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los empleados</option>
                     @foreach(\App\Models\Empleados::whereNull('deleted_at')->get() as $empleado)
                         <option value="{{ $empleado->EmpleadoID }}" {{ $filtros['empleado_id'] == $empleado->EmpleadoID ? 'selected' : '' }}>
                             {{ $empleado->NombreEmpleado }}
                         </option>
                     @endforeach
                </select>
            </div>

            <!-- Equipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Equipo</label>
                <select name="equipo_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los equipos</option>
                    @foreach(\App\Models\Equipos::whereNull('deleted_at')->get() as $equipo)
                        <option value="{{ $equipo->EquipoID }}" {{ $filtros['equipo_id'] == $equipo->EquipoID ? 'selected' : '' }}>
                            {{ $equipo->Nombre }} - {{ $equipo->Marca }} {{ $equipo->Modelo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Gerencia -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gerencia</label>
                <select name="gerencia_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todas las gerencias</option>
                    @foreach(\App\Models\Gerencia::whereNull('deleted_at')->get() as $gerencia)
                        <option value="{{ $gerencia->GerenciaID }}" {{ $filtros['gerencia_id'] == $gerencia->GerenciaID ? 'selected' : '' }}>
                            {{ $gerencia->Nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Estatus -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estatus</label>
                <select name="estatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los estatus</option>
                    <option value="Activo" {{ $filtros['estatus'] == 'Activo' ? 'selected' : '' }}>Activo</option>
                    <option value="Inactivo" {{ $filtros['estatus'] == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    <option value="Mantenimiento" {{ $filtros['estatus'] == 'Mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="Dañado" {{ $filtros['estatus'] == 'Dañado' ? 'selected' : '' }}>Dañado</option>
                </select>
            </div>

            <!-- Fecha Desde -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Botones -->
            <div class="md:col-span-2 lg:col-span-4 flex space-x-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i>Aplicar Filtros
                </button>
                <a href="{{ route('reportes-especificos.equipos-asignados') }}" 
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
                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium">
                    {{ $resultado->count() }} registros encontrados
                </span>
            </div>

            @if($resultado->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Empleado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gerencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Marca/Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Serie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Asignación</th>
                              
                                
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($resultado as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                         {{ $item->empleado_nombre }}
                                     </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->GerenciaEquipo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->Marca }} {{ $item->Modelo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $item->NumSerie }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($item->FechaAsignacion)->format('d/m/Y') }}
                                    </td>
                                  
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-laptop text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No se encontraron resultados</h3>
                    <p class="text-gray-500 dark:text-gray-400">Intenta ajustar los filtros de búsqueda para encontrar equipos asignados.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
