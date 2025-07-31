<div class='btn-group'>
    @can('asignar-inventario')
    <a href="{{ route('inventarios.edit', $id) }}" class='btn btn-outline-success btn-xs'>
        <i class="fas fa-laptop-medical"></i>
    </a>
    @endcan
    @can('transferir-inventario')
    <a href="{{ route('inventarios.transferir', $id) }}" class='btn btn-outline-danger btn-xs'>
        <i class="fas fa-exchange-alt"></i>
    </a>
    @endcan
    @can('cartas-inventario')
    <a href="{{ route('inventarios.cartas', $id) }}" class='btn btn-outline-secondary btn-xs'>
        <i class="fas fa-print"></i>
    </a>
    @endcan
</div>