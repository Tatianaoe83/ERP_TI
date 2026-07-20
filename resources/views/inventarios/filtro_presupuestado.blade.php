{{-- Barra de filtro por Presupuestado + descarga a Excel del contenido de la pestaña activa --}}
<div class="inventario-filtros d-flex flex-wrap align-items-center w-100 mb-3"
    data-tabla="{{ $tabla }}"
    data-tipo="{{ $tipo }}">

    @if($permitePresupuestado)
    <div class="pill-group d-flex flex-wrap align-items-center">
        <button type="button" class="pill-filtro activo" data-filtro="todos">
            Todos (<span class="conteo-todos">0</span>)
        </button>
        <button type="button" class="pill-filtro" data-filtro="presupuestados">
            Presupuestados (<span class="conteo-si">0</span>)
        </button>
        <button type="button" class="pill-filtro" data-filtro="no_presupuestados">
            Asignados (<span class="conteo-no">0</span>)
        </button>
    </div>
    @endif

    <button type="button" class="btn btn-sm btn-success btn-excel-inventario" style="margin-left:auto;"
        data-url="{{ route('inventarios.exportarAsignados', ['inventario' => $empleadoID, 'tipo' => $tipo]) }}">
        <i class="fas fa-file-excel"></i> Descargar Excel
    </button>
</div>
