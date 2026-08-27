<x-index-actions
    :show-url="route('reportes.show', $id)"
    show-permission="ver-reportes"
    :edit-url="route('reportes.edit', $id)"
    edit-permission="editar-reportes"
    :destroy-route="['reportes.destroy', $id]"
    destroy-permission="borrar-reportes"
    confirm-title="¿Deseas borrar este reporte?"
    success-title="Reporte borrado"
>
    @can('exportar-reportes')
    <div class="dropdown export-reporte" id="export-wrap-{{ $id }}" style="position: relative;">
        <button class="index-action index-action--edit" type="button"
            data-export-toggle
            title="Exportar"
            aria-expanded="false"
            aria-haspopup="true">
            <i class="fas fa-download"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm export-reporte-menu"
            data-export-id="{{ $id }}">
            <li>
                <a href="{{ route('reportes.exportPdf', $id) }}"
                   class="dropdown-item d-flex align-items-center gap-2 export-direct"
                   data-label="PDF" data-id="{{ $id }}">
                    <i class="fas fa-file-pdf text-danger"></i> PDF
                    <small class="text-muted ms-auto">máx. 500 filas</small>
                </a>
            </li>
            <li>
                <form action="{{ route('reportes.exportExcel', $id) }}" method="POST"
                      class="w-100 export-form" data-label="Excel" data-id="{{ $id }}">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                        <i class="fas fa-file-excel text-success"></i> Excel
                        <small class="text-muted ms-auto">completo</small>
                    </button>
                </form>
            </li>
        </ul>
    </div>
    @endcan
</x-index-actions>
