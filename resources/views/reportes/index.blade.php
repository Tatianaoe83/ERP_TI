@extends('layouts.app')

@push('styles')
<style>
.export-reporte-menu { display: none; }
.export-reporte-menu.is-open,
.export-reporte-menu.show { display: block; }
.dark .export-reporte-menu {
    background: #1f2937;
    border-color: #374151;
}
.dark .export-reporte-menu .dropdown-item { color: #e5e7eb; }
.dark .export-reporte-menu .dropdown-item:hover {
    background: #374151;
    color: #fff;
}
</style>
@endpush

@section('content')
@include('flash::message')

<x-index-page
    title="Reporteador"
    icon="fa-book"
    :create-url="route('reportes.create')"
    create-permission="crear-reportes"
    create-label="+ Nuevo reporte"
>
    <x-slot name="headerActions">
        @can('ver-reportes-especificos')
        <a href="{{ route('reportes-especificos.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-chart-line"></i> Reportes específicos
        </a>
        @endcan
    </x-slot>

    @include('reportes.table')
</x-index-page>

<div class="modal fade" id="modalDescargandoReporte" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center py-4 px-3">
                <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;">
                    <span class="visually-hidden">Generando...</span>
                </div>
                <p class="fw-semibold mb-1" id="modalDescMensajeReporte">Generando archivo...</p>
                <p class="text-muted small mb-0">La descarga iniciará automáticamente.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('third_party_scripts')
<script>
(function () {
    if (window.__reporteExportMenuReady) return;
    window.__reporteExportMenuReady = true;

    var openMenu = null;

    function closeExportMenus() {
        document.querySelectorAll('.export-reporte-menu.is-open').forEach(function (menu) {
            menu.classList.remove('is-open', 'show');
            menu.style.display = '';
            menu.style.position = '';
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.zIndex = '';
            var homeId = menu.getAttribute('data-home');
            var home = homeId ? document.getElementById(homeId) : null;
            if (home && menu.parentNode !== home) home.appendChild(menu);
            else if (!home && menu.parentNode === document.body) menu.remove();
        });
        document.querySelectorAll('[data-export-toggle][aria-expanded="true"]').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
        openMenu = null;
    }

    function openExportMenu(btn) {
        var wrap = btn.closest('.export-reporte');
        if (!wrap) return;
        var menu = wrap.querySelector('.export-reporte-menu');
        if (!menu) return;

        var alreadyOpen = menu.classList.contains('is-open');
        closeExportMenus();
        if (alreadyOpen) return;

        menu.setAttribute('data-home', wrap.id);
        document.body.appendChild(menu);

        var rect = btn.getBoundingClientRect();
        menu.classList.add('is-open', 'show');
        menu.style.display = 'block';
        menu.style.position = 'fixed';
        menu.style.zIndex = '2000';
        menu.style.minWidth = '180px';
        menu.style.top = (rect.bottom + 4) + 'px';
        menu.style.left = 'auto';
        menu.style.right = Math.max(8, window.innerWidth - rect.right) + 'px';
        btn.setAttribute('aria-expanded', 'true');
        openMenu = menu;
    }

    function showDownloadModal(label) {
        if (window.AppDownload) {
            window.AppDownload.show(label || 'archivo');
            return {
                modal: { hide: function () { window.AppDownload.hide(); } },
                msgEl: { set textContent(v) { window.AppDownload.setMessage(v); } }
            };
        }
        var modalEl = document.getElementById('modalDescargandoReporte');
        var msgEl = document.getElementById('modalDescMensajeReporte');
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
        if (msgEl) msgEl.textContent = 'Generando ' + (label || 'archivo') + '...';
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        return { modal: modal, msgEl: msgEl };
    }

    function watchDownload(token, ui) {
        var poll = setInterval(function () {
            if (document.cookie.split(';').some(function (c) { return c.trim().indexOf(token + '=') === 0; })) {
                clearInterval(poll);
                document.cookie = token + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                if (ui && ui.msgEl) ui.msgEl.textContent = '¡Descarga lista!';
                setTimeout(function () { if (ui && ui.modal) ui.modal.hide(); }, 800);
            }
        }, 500);
        setTimeout(function () {
            clearInterval(poll);
            if (ui && ui.modal) ui.modal.hide();
        }, 180000);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-export-toggle]');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            openExportMenu(btn);
            return;
        }

        var pdfLink = e.target.closest('a.export-direct');
        if (pdfLink && pdfLink.closest('.export-reporte-menu')) {
            e.preventDefault();
            closeExportMenus();
            var ui = showDownloadModal(pdfLink.getAttribute('data-label') || 'PDF');
            var token = 'dl_' + (pdfLink.getAttribute('data-id') || '0') + '_' + Date.now();
            var url = new URL(pdfLink.href, window.location.origin);
            url.searchParams.set('downloadToken', token);
            window.location.href = url.toString();
            watchDownload(token, ui);
            return;
        }

        if (!e.target.closest('.export-reporte-menu')) {
            closeExportMenus();
        }
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form.export-form');
        if (!form || !form.closest('.export-reporte-menu')) return;
        e.preventDefault();
        closeExportMenus();

        var ui = showDownloadModal(form.getAttribute('data-label') || 'Excel');
        var id = form.getAttribute('data-id') || '0';
        var token = 'dl_' + id + '_' + Date.now();
        var url = new URL(form.action, window.location.origin);
        url.searchParams.set('downloadToken', token);

        var iframeName = 'dl_iframe_reporte';
        var iframe = document.getElementById(iframeName);
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = iframeName;
            iframe.name = iframeName;
            iframe.style.display = 'none';
            document.body.appendChild(iframe);
        }

        var tmp = form.cloneNode(true);
        tmp.action = url.toString();
        tmp.target = iframeName;
        tmp.style.display = 'none';
        document.body.appendChild(tmp);
        tmp.submit();
        tmp.remove();
        watchDownload(token, ui);
    }, true);

    window.addEventListener('scroll', closeExportMenus, true);
    window.addEventListener('resize', closeExportMenus);
})();
</script>
@endpush
