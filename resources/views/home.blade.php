@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Título del Dashboard -->

    <!-- Estadísticas Principales -->

    
    <!-- Dos columnas: izquierda (líneas naranjas), derecha (empleados + equipos). Columna derecha más ancha. -->
    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-4 mb-8">
        <!-- Columna izquierda: Líneas (parte superior) -->
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg p-5 flex flex-col min-h-0">
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-2 right-2 text-white/30"><i class="fas fa-phone text-3xl"></i></div>
            <div class="relative flex-1 flex flex-col">
                <p class="text-sm font-semibold">Líneas que puedes asignar</p>
                <p class="text-xs opacity-90 mt-0.5">Libres + referenciadas</p>
                <p class="text-3xl font-bold mt-2">{{ $stats['inventario']['lineas']['disponibles'] }}</p>
                <div class="w-12 h-0.5 bg-orange-400 mt-1 rounded-full"></div>
                <div class="mt-4 flex items-center justify-between py-2.5 px-3 bg-white/15 rounded-lg backdrop-blur-sm">
                    <span class="text-sm font-medium">Asignadas a persona física</span>
                    <span class="text-lg font-bold">{{ $stats['inventario']['lineas']['asignadas'] }}</span>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Empleados (misma altura que tarjeta naranja superior) -->
        <div class="bg-blue-500 rounded-xl p-5 text-white shadow-lg flex flex-col min-h-0">
            <div class="flex justify-between items-start flex-1">
                <div>
                    <p class="text-sm font-medium mb-1">Total de empleados activos</p>
                    <p class="text-3xl font-bold">{{ $stats['empleados']['activos'] }}</p>
                </div>
                <div class="bg-blue-400/80 rounded-full p-3 shrink-0">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Columna izquierda: Líneas (parte inferior - Sin asignar / Referenciadas) -->
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg p-5 flex flex-col min-h-0">
            <div class="absolute bottom-0 left-0 w-28 h-28 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
            <div class="relative flex-1 flex flex-col gap-3">
                <div class="grid grid-cols-2 gap-2">
                    <div class="py-4 px-3 bg-white/15 rounded-lg backdrop-blur-sm text-center">
                        <p class="text-xs font-medium opacity-90">Sin asignar</p>
                        <p class="text-2xl font-bold mt-0.5">{{ $stats['inventario']['lineas']['libres'] }}</p>
                    </div>
                    <div class="py-4 px-3 bg-white/15 rounded-lg backdrop-blur-sm text-center">
                        <p class="text-xs font-medium opacity-90">Referenciadas</p>
                        <p class="text-2xl font-bold mt-0.5">{{ $stats['inventario']['lineas']['referenciados'] }}</p>
                    </div>
                </div>
                @if($stats['inventario']['lineas']['referenciados'] > 0)
                    <p class="text-xs opacity-85 italic">Referenciadas = asignadas pero no a persona física</p>
                @endif
            </div>
        </div>

        <!-- Columna derecha: Equipos (misma altura que tarjeta naranja inferior) -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg flex flex-col min-h-0">
            <p class="text-green-100 text-sm font-medium mb-3">Equipos asignados en inventario</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 flex-1">
                @forelse($stats['equipos_por_categoria']->take(3) as $equipo)
                <div class="flex items-center justify-between p-3 bg-green-500/80 dark:bg-green-700 rounded-lg border border-white/30">
                    <div class="flex items-center">
                        <i class="fas fa-desktop text-white mr-3"></i>
                        <span class="text-green-100 font-medium">{{ Str::limit($equipo->CategoriaEquipo, 15) }}</span>
                    </div>
                    <span class="font-bold text-xl text-green-100">{{ $equipo->total_inventario }}</span>
                </div>
                @empty
                <p class="text-green-100 text-center py-4 text-sm col-span-3">No hay equipos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Información Adicional Compacta -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 mb-6 lg:mb-8">
        <!-- Insumos por Licencia con Paginación AJAX -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
            <h3 class="text-lg font-semibold text-[#101D49] dark:text-gray-300 mb-3 flex items-center">
                <i class="fas fa-certificate text-yellow-500 mr-2"></i>
                Licencias asignadas
            </h3>
            
            @include('partials.insumos-licencia', ['stats' => $stats])
        </div>

        <!-- Estadísticas por Gerencia Compacta -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
            <h3 class="text-lg font-semibold text-[#101D49] dark:text-gray-300 mb-3 flex items-center">
                <i class="fas fa-building text-blue-500 mr-2"></i>
                Gerencias con mayor numero de empleados activos
            </h3>
            <div class="space-y-2">
                @forelse($stats['estadisticas_gerencia'] as $gerencia)
                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-[#101D49] dark:text-white text-sm">{{ $gerencia->NombreGerencia }}</p>
                        <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium px-2 py-1 rounded-full">
                            {{ $gerencia->empleados_activos }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Resumen Organizacional Compacto -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-gray-300 mb-3 lg:mb-4 flex items-center">
            <i class="fas fa-sitemap text-green-500 mr-2"></i>
            Resumen Organizacional
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-hard-hat text-orange-500 mr-3"></i>
                    <span class="text-[#101D49] dark:text-gray-300 font-medium">Obras</span>
                </div>
                <span class="font-bold text-xl text-[#101D49] dark:text-white">{{ $stats['organizacion']['obras'] }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-building text-blue-500 mr-3"></i>
                    <span class="text-[#101D49] dark:text-gray-300 font-medium">Gerencias</span>
                </div>
                <span class="font-bold text-xl text-[#101D49] dark:text-white">{{ $stats['organizacion']['gerencias'] }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-industry text-purple-500 mr-3"></i>
                    <span class="text-[#101D49] dark:text-gray-300 font-medium">Unidades</span>
                </div>
                <span class="font-bold text-xl text-[#101D49] dark:text-white">{{ $stats['organizacion']['unidades_negocio'] }}</span>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="mb-4">
        <h2 class="text-xl font-bold text-[#101D49] dark:text-gray-300 mb-3">Accesos Rápidos</h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 lg:gap-4">
        <!-- Inventarios -->
        @if(auth()->user()->can('ver-inventario') or auth()->user()->can('transferir-inventario') or auth()->user()->can('cartas-inventario') or auth()->user()->can('asignar-inventario'))
        <a href="/inventarios" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-blue-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Inventarios</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Gestión</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Empleados -->
        @if(auth()->user()->can('ver-empleados') or auth()->user()->can('crear-empleados') or auth()->user()->can('editar-empleados') or auth()->user()->can('borrar-empleados'))
        <a href="/empleados" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-green-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Empleados</div>
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
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-purple-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-laptop text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Equipos</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Catálogo</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Reportes -->
        @if(auth()->user()->can('ver-reportes') or auth()->user()->can('crear-reportes') or auth()->user()->can('editar-reportes') or auth()->user()->can('borrar-reportes') or auth()->user()->can('exportar-reportes') or auth()->user()->can('ver-reportes-especificos'))
        <a href="/reportes" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-orange-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Reportes</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Generar</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Reportes Específicos -->
        @if(auth()->user()->can('ver-reportes-especificos') or auth()->user()->can('exportar-reportes-especificos'))
        <a href="/reportes-especificos" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-green-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-download text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Reportes Específicos</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Descargar</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
        @endif

        @if(auth()->user()->can('ver-usuarios') or auth()->user()->can('crear-usuarios') or auth()->user()->can('editar-usuarios') or auth()->user()->can('borrar-usuarios'))
        <!-- Usuarios -->
        <a href="/usuarios" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-indigo-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-cog text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Usuarios</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Gestión</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
    @endif

    @if(auth()->user()->can('ver-rol') or auth()->user()->can('crear-rol') or auth()->user()->can('editar-rol') or auth()->user()->can('borrar-rol'))
        <!-- Roles -->
        <a href="/roles" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-red-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Roles</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Permisos</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
    @endif

        <!-- Presupuestos -->
        <a href="/presupuesto" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-teal-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Presupuestos</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Informes</div>
                </div>
                <div class="flex justify-center">
                        <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>

        <!-- Auditoría -->
        @if(auth()->user()->can('ver-informe') or auth()->user()->can('buscar-informe'))
        <a href="/informe" class="group block no-underline">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
                <div class="flex justify-center">
                    <div class="bg-gray-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                        <i class="fas fa-history text-xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Informes</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Registro</div>
                </div>
                <div class="flex justify-center">
                    <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
                </div>
            </div>
        </a>
    @endif
</div>
@endsection