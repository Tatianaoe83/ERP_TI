<div class="index-actions">
    @if($activo ?? true)
        @can('asignar-inventario')
        <a href="{{ route('inventarios.edit', $id) }}" class="index-action index-action--edit" title="Asignar inventario">
            <i class="fas fa-laptop-medical"></i>
        </a>
        @endcan
        @can('transferir-inventario')
        <a href="{{ route('inventarios.transferir', $id) }}" class="index-action index-action--warning" title="Transferir inventario">
            <i class="fas fa-exchange-alt"></i>
        </a>
        @endcan
        @can('cartas-inventario')
        <a href="{{ route('inventarios.cartas', $id) }}" class="index-action index-action--view" title="Cartas responsivas">
            <i class="fas fa-print"></i>
        </a>
        @endcan
    @else
        <span class="text-muted small">—</span>
    @endif
</div>
