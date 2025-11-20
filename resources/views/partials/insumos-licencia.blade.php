<div id="insumos-licencia-content">
    <div class="space-y-2 mb-4">
        @forelse($stats['insumos_por_licencia'] as $index => $insumo)
        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex items-center">
                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center mr-3">
                    {{ ($stats['insumos_por_licencia']->currentPage() - 1) * $stats['insumos_por_licencia']->perPage() + $index + 1 }}
                </span>
                <div>
                    <p class="font-medium text-[#101D49] dark:text-white text-sm">{{ $insumo->NombreInsumo }}</p>
                </div>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full text-xs font-medium">
                {{ $insumo->total_inventario }}
            </div>
        </div>
        @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No hay datos disponibles</p>
        @endforelse
    </div>
    
    <!-- Paginador AJAX -->
    @if($stats['insumos_por_licencia']->hasPages())
    <div class="flex justify-center mt-4" id="insumos-pagination">
        {{ $stats['insumos_por_licencia']->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Interceptar clicks en los enlaces de paginación
    document.addEventListener('click', function(e) {
        if (e.target.closest('#insumos-pagination a')) {
            e.preventDefault();
            
            const url = e.target.closest('a').href;
            const loadingIndicator = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-blue-500"></i> Cargando...</div>';
            
            // Mostrar indicador de carga
            document.getElementById('insumos-licencia-content').innerHTML = loadingIndicator;
            
            // Realizar petición AJAX
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('insumos-licencia-content').innerHTML = data;
                
                // Re-ejecutar el script para los nuevos enlaces
                setTimeout(function() {
                    const script = document.getElementById('insumos-licencia-content').querySelector('script');
                    if (script) {
                        eval(script.innerHTML);
                    }
                }, 100);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('insumos-licencia-content').innerHTML = '<div class="text-center py-4 text-red-500">Error al cargar los datos</div>';
            });
        }
    });
});
</script>
