@php
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = $meses[$statsCompras['mes']] ?? '';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="dashboard-card dashboard-card-orange relative overflow-hidden rounded-xl text-white shadow-lg p-5">
        <div class="absolute top-0 right-0 w-16 h-16 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <p class="text-sm font-semibold">Solicitudes activas</p>
        <p class="text-xs opacity-90 mt-0.5">Pendientes, en proceso y pausadas</p>
        <p class="text-3xl font-bold mt-2">{{ $statsCompras['activos'] }}</p>
    </div>

    <div class="dashboard-card dashboard-card-blue rounded-xl p-5 text-white shadow-lg">
        <p class="text-sm font-medium mb-1">Atendidas en {{ $nombreMes }}</p>
        <p class="text-3xl font-bold">{{ $statsCompras['atendidos_mes'] }}</p>
        <p class="text-xs opacity-90 mt-1">{{ $statsCompras['anio'] }}</p>
    </div>

    <div class="dashboard-card dashboard-card-green rounded-xl p-5 text-white shadow-lg">
        <p class="text-sm font-medium mb-1">Nuevas en {{ $nombreMes }}</p>
        <p class="text-3xl font-bold">{{ $statsCompras['creados_mes'] }}</p>
        <p class="text-xs opacity-90 mt-1">Solicitudes registradas</p>
    </div>

    <div class="dashboard-card rounded-xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
        <p class="text-sm font-medium mb-1">Total histórico</p>
        <p class="text-3xl font-bold">{{ $statsCompras['total'] }}</p>
        <p class="text-xs opacity-90 mt-1">Mantenimientos de compras</p>
    </div>
</div>

<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-6 lg:mb-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-gray-300 flex items-center mb-0">
            <i class="fas fa-tasks text-indigo-500 mr-2"></i>
            Estado de solicitudes
        </h3>
        @can('ver-compras')
            <a href="{{ route('tickets-mantenimiento.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-300 hover:underline">
                Ver tablero
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg text-center">
            <i class="fas fa-clock text-yellow-500 mb-2"></i>
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente</p>
            <p class="font-bold text-2xl text-[#101D49] dark:text-white">{{ $statsCompras['por_estatus']['Pendiente'] ?? 0 }}</p>
        </div>
        <div class="p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg text-center">
            <i class="fas fa-spinner text-blue-500 mb-2"></i>
            <p class="text-xs text-gray-500 dark:text-gray-400">En proceso</p>
            <p class="font-bold text-2xl text-[#101D49] dark:text-white">{{ $statsCompras['por_estatus']['En proceso'] ?? 0 }}</p>
        </div>
        <div class="p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg text-center">
            <i class="fas fa-pause text-orange-500 mb-2"></i>
            <p class="text-xs text-gray-500 dark:text-gray-400">Pausado</p>
            <p class="font-bold text-2xl text-[#101D49] dark:text-white">{{ $statsCompras['por_estatus']['Pausado'] ?? 0 }}</p>
        </div>
        <div class="p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg text-center">
            <i class="fas fa-check-circle text-green-500 mb-2"></i>
            <p class="text-xs text-gray-500 dark:text-gray-400">Atendido</p>
            <p class="font-bold text-2xl text-[#101D49] dark:text-white">{{ $statsCompras['por_estatus']['Atendido'] ?? 0 }}</p>
        </div>
        <div class="p-4 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg text-center">
            <i class="fas fa-times-circle text-red-500 mb-2"></i>
            <p class="text-xs text-gray-500 dark:text-gray-400">Cancelado</p>
            <p class="font-bold text-2xl text-[#101D49] dark:text-white">{{ $statsCompras['por_estatus']['Cancelado'] ?? 0 }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 mb-6">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-gray-300 mb-3 flex items-center">
            <i class="fas fa-tags text-purple-500 mr-2"></i>
            Categorías con más solicitudes activas
        </h3>
        <div class="space-y-2">
            @forelse($statsCompras['por_categoria'] as $categoria)
            <div class="flex items-center justify-between p-3 dark:bg-gray-800 rounded-lg">
                <span class="text-sm font-medium text-[#101D49] dark:text-white">{{ $categoria->Categoria ?: 'Sin categoría' }}</span>
                <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 text-xs font-bold px-2.5 py-1 rounded-full">
                    {{ $categoria->total }}
                </span>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay solicitudes activas por categoría</p>
            @endforelse
        </div>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-[#101D49] dark:text-gray-300 mb-3 flex items-center">
            <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>
            Prioridad de solicitudes activas
        </h3>
        <div class="space-y-2">
            @forelse($statsCompras['por_prioridad'] as $prioridad)
            <div class="flex items-center justify-between p-3 dark:bg-gray-800 rounded-lg">
                <span class="text-sm font-medium text-[#101D49] dark:text-white">{{ $prioridad->Prioridad ?: 'Sin prioridad' }}</span>
                <span class="bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200 text-xs font-bold px-2.5 py-1 rounded-full">
                    {{ $prioridad->total }}
                </span>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay solicitudes activas por prioridad</p>
            @endforelse
        </div>
    </div>
</div>

<div class="mb-4">
    <h2 class="text-xl font-bold text-[#101D49] dark:text-gray-300 mb-3">Accesos Rápidos</h2>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
    @can('ver-compras')
    <a href="{{ route('tickets-mantenimiento.index') }}" class="group block no-underline">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
            <div class="flex justify-center">
                <div class="bg-indigo-600 h-[50px] w-[50px] text-white p-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wrench text-xl"></i>
                </div>
            </div>
            <div class="text-center">
                <div class="text-sm font-semibold text-[#101D49] dark:text-gray-300 mb-1">Mantenimientos</div>
                <div class="text-xs text-gray-600 dark:text-gray-300">Tablero de compras</div>
            </div>
            <div class="flex justify-center">
                <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 text-[#101D49] dark:text-gray-300 text-sm"></i>
            </div>
        </div>
    </a>
    @endcan
</div>
