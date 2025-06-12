<div class='btn-group'>
    @can('ver-reportes')
    <a href="{{ route('reportes.show', $id) }}" class='btn btn-outline-success btn-xs'>
        <i class="fas fa-laptop-medical"></i>
    </a>
    @endcan
    @can('editar-reportes')
    <a href="{{ route('reportes.edit', $id) }}" class='btn btn-outline-danger btn-xs'>
        <i class="fas fa-exchange-alt"></i>
    </a>
    @endcan
    @can('borrar-reportes')
    <a href="{{ route('reportes.destroy', $id) }}" class='btn btn-outline-dark btn-xs'>
        <i class="fas fa-print"></i>
    </a>
    @endcan
</div>