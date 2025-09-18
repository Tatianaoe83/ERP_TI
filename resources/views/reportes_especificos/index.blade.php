@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Título del Dashboard -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-[#101D49] dark:text-white mb-2">Reportes Específicos</h1>
        <p class="text-gray-600 dark:text-gray-300">Reportes predefinidos para descarga con opciones de filtrado</p>
    </div>

    <!-- Cards de Reportes Específicos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Estatus de Licencias Asignadas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                        <i class="fas fa-certificate text-blue-600 dark:text-blue-400 text-2xl"></i>
                    </div>
                    <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
                        Licencias
                    </span>
                </div>
                <h3 class="text-xl font-semibold text-[#101D49] dark:text-gray-400 mb-2">
                    Estatus de Licencias Asignadas
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4 text-sm">
                    Reporte detallado del estado de las licencias de software asignadas a empleados
                </p>
                <div class="flex space-x-2">
                    <a href="{{ route('reportes-especificos.estatus-licencias') }}" 
                       class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-eye mr-2"></i>Ver Reporte
                    </a>
                    <a href="{{ route('reportes-especificos.export-estatus-licencias') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Equipos Asignados -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                        <i class="fas fa-laptop text-green-600 dark:text-green-400 text-2xl"></i>
                    </div>
                    <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium">
                        Equipos
                    </span>
                </div>
                <h3 class="text-xl font-semibold text-[#101D49] dark:text-gray-400 mb-2">
                    Listado de Equipos Asignados
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4 text-sm">
                    Inventario completo de equipos de cómputo asignados a empleados
                </p>
                <div class="flex space-x-2">
                    <a href="{{ route('reportes-especificos.equipos-asignados') }}" 
                       class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-center text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-eye mr-2"></i>Ver Reporte
                    </a>
                    <a href="{{ route('reportes-especificos.export-equipos-asignados') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Líneas Asignadas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
                        <i class="fas fa-phone text-purple-600 dark:text-purple-400 text-2xl"></i>
                    </div>
                    <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-3 py-1 rounded-full text-sm font-medium">
                        Líneas
                    </span>
                </div>
                <h3 class="text-xl font-semibold text-[#101D49] dark:text-gray-400 mb-2">
                    Listado de Líneas Asignadas
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4 text-sm">
                    Reporte de líneas telefónicas asignadas a empleados y obras
                </p>
                <div class="flex space-x-2">
                    <a href="{{ route('reportes-especificos.lineas-asignadas') }}" 
                       class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-center text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-eye mr-2"></i>Ver Reporte
                    </a>
                    <a href="{{ route('reportes-especificos.export-lineas-asignadas') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <div class="flex items-start">
            <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg mr-4">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">
                    Información sobre los Reportes
                </h4>
                <ul class="text-blue-700 dark:text-blue-300 text-sm space-y-1">
                    <li>• Todos los reportes incluyen opciones de filtrado por empleado y fechas</li>
                    <li>• Los reportes se pueden descargar directamente en formato PDF</li>
                    <li>• Los datos se actualizan en tiempo real según la información del sistema</li>
                    <li>• Los filtros aplicados se mantienen al descargar el reporte</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Botón de regreso -->
    <div class="mt-8 text-center">
        <a href="{{ route('reportes.index') }}" 
           class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a Reportes Generales
        </a>
    </div>
</div>
@endsection
