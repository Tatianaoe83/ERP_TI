<div class='btn-group'>
    @can('ver-reportes')
    <a href="{{ route('reportes.show', $id) }}" class='btn btn-outline-primary btn-xs'>
        <i class="fas fa-eye"></i>
    </a>
    @endcan
    @can('editar-reportes')
    <a href="{{ route('reportes.edit', $id) }}" class='btn btn-outline-secondary btn-xs'>
        <i class="fas fa-edit"></i>
    </a>
    @endcan

    <div class="dropdown" style="position: relative;">
        <button class="btn btn-outline-info btn-xs dropdown-toggle" type="button"
            id="dropdownExportar{{ $id }}"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-download me-1"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm"
            aria-labelledby="dropdownExportar{{ $id }}"
            style="min-width: 180px; z-index: 1050;">
            @can('exportar-reportes')
            <li>
                {{-- PDF: link GET directo, el navegador descarga sin iframe --}}
                <a href="{{ route('reportes.exportPdf', $id) }}"
                   class="dropdown-item d-flex align-items-center gap-2 export-direct"
                   data-label="PDF" data-id="{{ $id }}">
                    <i class="fas fa-file-pdf text-danger"></i> PDF
                    <small class="text-muted ms-auto">máx. 500 filas</small>
                </a>
            </li>
            <li>
                {{-- Excel: POST via iframe --}}
                <form action="{{ route('reportes.exportExcel', $id) }}" method="POST"
                      class="w-100 export-form" data-label="Excel">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                        <i class="fas fa-file-excel text-success"></i> Excel
                        <small class="text-muted ms-auto">completo</small>
                    </button>
                </form>
            </li>
            @endcan
        </ul>
    </div>

    @can('borrar-reportes')
    <form action="{{ route('reportes.destroy', $id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-xs btn-outline-danger btn-flat show_confirm">
            <i class="fa fa-trash"></i>
        </button>
    </form>
    @endcan
</div>

{{-- Modal de carga --}}
<div class="modal fade" id="modalDescargando{{ $id }}" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center py-4 px-3">
                <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;">
                    <span class="visually-hidden">Generando...</span>
                </div>
                <p class="fw-semibold mb-1" id="modalDescMensaje{{ $id }}">Generando archivo...</p>
                <p class="text-muted small mb-0">La descarga iniciará automáticamente.</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const group = document.getElementById('dropdownExportar{{ $id }}')?.closest('.btn-group');
    if (!group) return;

    // PDF via GET — window.location descarga directo
    group.querySelectorAll('a.export-direct').forEach(function (link) {
        if (link.dataset.ready) return;
        link.dataset.ready = '1';

        link.addEventListener('click', function (e) {
            e.preventDefault();

            const modal = new bootstrap.Modal(document.getElementById('modalDescargando{{ $id }}'));
            const msgEl = document.getElementById('modalDescMensaje{{ $id }}');
            const token = 'dl_{{ $id }}_' + Date.now();

            msgEl.textContent = 'Generando PDF...';
            modal.show();

            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('downloadToken', token);
            window.location.href = url.toString();

            const poll = setInterval(function () {
                if (document.cookie.split(';').some(c => c.trim().startsWith(token + '='))) {
                    clearInterval(poll);
                    document.cookie = token + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    msgEl.textContent = '¡Descarga lista!';
                    setTimeout(function () { modal.hide(); }, 800);
                }
            }, 500);

            setTimeout(function () { clearInterval(poll); modal.hide(); }, 180000);
        });
    });

    // Excel via POST — iframe oculto
    group.querySelectorAll('form.export-form').forEach(function (form) {
        if (form.dataset.ready) return;
        form.dataset.ready = '1';

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const modal = new bootstrap.Modal(document.getElementById('modalDescargando{{ $id }}'));
            const msgEl = document.getElementById('modalDescMensaje{{ $id }}');
            const label = form.dataset.label || 'archivo';
            const token = 'dl_{{ $id }}_' + Date.now();

            msgEl.textContent = 'Generando ' + label + '...';
            modal.show();

            const url = new URL(form.action, window.location.origin);
            url.searchParams.set('downloadToken', token);

            const iframeName = 'dl_iframe_{{ $id }}';
            let iframe = document.getElementById(iframeName);
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id   = iframeName;
                iframe.name = iframeName;
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
            }

            const tmp = form.cloneNode(true);
            tmp.action = url.toString();
            tmp.target = iframeName;
            tmp.style.display = 'none';
            document.body.appendChild(tmp);
            tmp.submit();
            tmp.remove();

            const poll = setInterval(function () {
                if (document.cookie.split(';').some(c => c.trim().startsWith(token + '='))) {
                    clearInterval(poll);
                    document.cookie = token + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    msgEl.textContent = '¡Descarga lista!';
                    setTimeout(function () { modal.hide(); }, 800);
                }
            }, 500);

            setTimeout(function () { clearInterval(poll); modal.hide(); }, 180000);
        });
    });
})();
</script>

<script>
    document.querySelectorAll('.show_confirm').forEach(function(btn) {
        btn.addEventListener('click', function(event) {
            var form = this.closest('form');
            event.preventDefault();
            swal.fire({
                title: '¿Deseas borrar este reporte?',
                icon: 'warning',
                showDenyButton: true,
                confirmButtonText: 'Confirmar',
                denyButtonText: 'Cerrar',
            }).then(function (willDelete) {
                if (willDelete.isConfirmed) {
                    swal.fire({ title: 'Reporte borrado', icon: 'success' })
                        .then(function () { form.submit(); });
                } else if (willDelete.isDenied) {
                    swal.fire('Cambios no generados');
                }
            });
        });
    });
</script>