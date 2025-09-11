@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Título del Dashboard -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-[#101D49] dark:text-white mb-2">Dashboard ERP TI</h1>
        <p class="text-gray-600 dark:text-gray-300">Resumen ejecutivo y accesos rápidos del sistema</p>
    </div>

    <!-- Estadísticas Principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
        <!-- Empleados Activos -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-xs lg:text-sm font-medium">Empleados Activos</p>
                    <p class="text-2xl lg:text-3xl font-bold">{{ $stats['empleados']['activos'] }}</p>
                    <p class="text-blue-100 text-xs">de {{ $stats['empleados']['total'] }} total</p>
                </div>
                <div class="bg-blue-400 bg-opacity-30 rounded-full p-2 lg:p-3">
                    <i class="fas fa-users text-lg lg:text-2xl"></i>
                </div>
                </div>
            </div>

        <!-- Equipos Disponibles -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-xs lg:text-sm font-medium">Total de Equipos</p>
                    <p class="text-2xl lg:text-3xl font-bold">{{ $stats['inventario']['equipos']['total'] }}</p>
                    <p class="text-green-100 text-xs">{{ $stats['inventario']['equipos']['asignados'] }} asignados</p>
                </div>
                <div class="bg-green-400 bg-opacity-30 rounded-full p-2 lg:p-3">
                    <i class="fas fa-laptop text-lg lg:text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Insumos Disponibles -->
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-xs lg:text-sm font-medium">Total de Insumos</p>
                    <p class="text-2xl lg:text-3xl font-bold">{{ $stats['inventario']['insumos']['total'] }}</p>
                    <p class="text-purple-100 text-xs">{{ $stats['inventario']['insumos']['asignados'] }} asignados</p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 rounded-full p-2 lg:p-3">
                    <i class="fas fa-box text-lg lg:text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Líneas Disponibles -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-xs lg:text-sm font-medium">Líneas Disponibles</p>
                    <p class="text-2xl lg:text-3xl font-bold">{{ $stats['inventario']['lineas']['disponibles'] }}</p>
                    <p class="text-orange-100 text-xs">{{ $stats['inventario']['lineas']['asignadas'] }} asignadas</p>
                </div>
                <div class="bg-orange-400 bg-opacity-30 rounded-full p-2 lg:p-3">
                    <i class="fas fa-phone text-lg lg:text-2xl"></i>
                </div>
            </div>
                </div>
            </div>

    <!-- Información Adicional Compacta -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 mb-6 lg:mb-8">
        <!-- Top Empleados con Inventario -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg">
            <h3 class="text-lg font-semibold text-[#101D49] dark:text-white mb-3 flex items-center">
                <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                Top Empleados con mayor asignacion en inventario
            </h3>
            <div class="space-y-2">
                @forelse($stats['empleados_con_inventario'] as $index => $empleado)
                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center mr-3">
                            {{ $index + 1 }}
                        </span>
            <div>
                            <p class="font-medium text-[#101D49] dark:text-white text-sm">{{ Str::limit($empleado->NombreEmpleado, 20) }}</p>
                           
                        </div>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full text-xs font-medium">
                        {{ $empleado->total_inventario }}
                    </div>
                </div>
                @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>

        <!-- Estadísticas por Gerencia Compacta -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg">
            <h3 class="text-lg font-semibold text-[#101D49] dark:text-white mb-3 flex items-center">
                <i class="fas fa-building text-blue-500 mr-2"></i>
                Gerencias Principales con mayor numero de empleados
            </h3>
            <div class="space-y-2">
                @forelse($stats['estadisticas_gerencia'] as $gerencia)
                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-[#101D49] dark:text-white text-sm">{{ Str::limit($gerencia->NombreGerencia, 25) }}</p>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ $gerencia->empleados_activos }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">/</span>
                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $gerencia->total_empleados }}</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-1">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $gerencia->total_empleados > 0 ? ($gerencia->empleados_activos / $gerencia->total_empleados) * 100 : 0 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>
            </div>

    <!-- Resumen Organizacional Compacto -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 lg:p-5 shadow-lg mb-6 lg:mb-8">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-white mb-3 lg:mb-4 flex items-center">
            <i class="fas fa-sitemap text-green-500 mr-2"></i>
            Resumen Organizacional
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-hard-hat text-orange-500 mr-3"></i>
                    <span class="text-[#101D49] dark:text-white font-medium">Obras</span>
                </div>
                <span class="font-bold text-xl text-[#101D49] dark:text-white">{{ $stats['organizacion']['obras'] }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-building text-blue-500 mr-3"></i>
                    <span class="text-[#101D49] dark:text-white font-medium">Gerencias</span>
                </div>
                <span class="font-bold text-xl text-[#101D49] dark:text-white">{{ $stats['organizacion']['gerencias'] }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-industry text-purple-500 mr-3"></i>
                    <span class="text-[#101D49] dark:text-white font-medium">Unidades</span>
                </div>
                <span class="font-bold text-xl text-[#101D49] dark:text-white">{{ $stats['organizacion']['unidades_negocio'] }}</span>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="mb-4">
        <h2 class="text-xl font-bold text-[#101D49] dark:text-white mb-3">Accesos Rápidos</h2>
            </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 lg:gap-4">
        <!-- Inventarios -->
        @if(auth()->user()->can('ver-inventarios') or auth()->user()->can('crear-inventarios') or auth()->user()->can('editar-inventarios') or auth()->user()->can('borrar-inventarios'))
        <a href="/inventarios" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600">
                <div class="flex justify-center">
                    <div class="bg-blue-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Inventarios</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Gestión</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Empleados -->
        @if(auth()->user()->can('ver-empleados') or auth()->user()->can('crear-empleados') or auth()->user()->can('editar-empleados') or auth()->user()->can('borrar-empleados'))
        <a href="/empleados" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600">
                <div class="flex justify-center">
                    <div class="bg-green-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Empleados</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Gestión</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
            </div>
        </div>
    </a>
    @endif

        <!-- Equipos -->
        @if(auth()->user()->can('ver-equipos') or auth()->user()->can('crear-equipos') or auth()->user()->can('editar-equipos') or auth()->user()->can('borrar-equipes'))
        <a href="/equipos" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600">
                <div class="flex justify-center">
                    <div class="bg-purple-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-laptop text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Equipos</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Catálogo</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Reportes -->
        @if(auth()->user()->can('ver-reportes') or auth()->user()->can('crear-reportes') or auth()->user()->can('editar-reportes') or auth()->user()->can('borrar-reportes'))
        <a href="/reportes" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600">
                <div class="flex justify-center">
                    <div class="bg-orange-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Reportes</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Generar</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Reportes Específicos -->
        @if(auth()->user()->can('ver-reportes') or auth()->user()->can('exportar-reportes'))
        <a href="/reportes-especificos" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600">
                <div class="flex justify-center">
                    <div class="bg-green-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-download text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Reportes Específicos</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Descargar</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        @if(auth()->user()->can('ver-usuarios') or auth()->user()->can('crear-usuarios') or auth()->user()->can('editar-usuarios') or auth()->user()->can('borrar-usuarios'))
        <!-- Usuarios -->
        <a href="/usuarios" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600">
                <div class="flex justify-center">
                    <div class="bg-indigo-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-cog text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Usuarios</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Gestión</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
            </div>
        </div>
    </a>
    @endif

    @if(auth()->user()->can('ver-rol') or auth()->user()->can('crear-rol') or auth()->user()->can('editar-rol') or auth()->user()->can('borrar-rol'))
        <!-- Roles -->
        <a href="/roles" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-600">
                <div class="flex justify-center">
                    <div class="bg-red-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-xl"></i>
                </div>
            </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Roles</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Permisos</div>
            </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
            </div>
        </div>
    </a>
    @endif

        <!-- Presupuestos -->
        <a href="/presupuesto" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-teal-300 dark:hover:border-teal-600">
                <div class="flex justify-center">
                    <div class="bg-teal-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Presupuestos</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Informes</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
                </div>
            </div>
        </a>

        <!-- Auditoría -->
        @if(auth()->user()->can('ver-auditoria') or auth()->user()->can('crear-auditoria') or auth()->user()->can('editar-auditoria') or auth()->user()->can('borrar-auditoria'))
        <a href="/auditoria" class="group block no-underline">
            <div class="bg-white dark:bg-gray-800 h-[140px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500">
                <div class="flex justify-center">
                    <div class="bg-gray-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-history text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-white mb-1">Auditoría</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Registro</div>
            </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-white text-sm"></i>
            </div>
        </div>
    </a>
        </div>
        @endif
</div>
@endsection