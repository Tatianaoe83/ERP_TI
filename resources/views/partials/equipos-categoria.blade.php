<div class="space-y-2">
    @forelse($stats['equipos_por_categoria'] as $index => $equipo)
    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <div class="flex items-center">
            <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center mr-3">
                {{ $index + 1 }}
            </span>
            <div>
                <p class="font-medium text-[#101D49] dark:text-white text-sm">{{ $equipo->CategoriaEquipo }}</p>
            </div>
        </div>
        <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full text-xs font-medium">
            {{ $equipo->total_inventario }}
        </div>
    </div>
    @empty
    <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay datos disponibles</p>
    @endforelse
</div>
