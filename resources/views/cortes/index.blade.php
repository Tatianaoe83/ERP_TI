@extends('layouts.app')

@section('content')
<style>
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background-color: #475569; }

    .swal2-popup {
        font-family: inherit !important;
        border-radius: 1rem !important;
    }
    .swal2-title {
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        color: #1e293b !important;
    }
    .dark .swal2-title { color: #f1f5f9 !important; }
    .swal2-html-container {
        font-size: 0.875rem !important;
        line-height: 1.6 !important;
        color: #475569 !important;
    }
    .swal2-confirm, .swal2-cancel, .swal2-deny {
        border-radius: 0.75rem !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        padding: 0.6rem 1.4rem !important;
        transition: all 0.2s !important;
    }
    .swal2-confirm:focus, .swal2-cancel:focus { box-shadow: none !important; }
    .swal2-icon { margin-bottom: 0.75rem !important; }
    .swal2-icon.swal2-warning { border-color: #f59e0b !important; color: #f59e0b !important; }
    .swal2-icon.swal2-question { border-color: #6366f1 !important; color: #6366f1 !important; }
    .swal2-icon.swal2-success { border-color: #10b981 !important; }
    .swal2-icon.swal2-success [class^=swal2-success-line] { background: #10b981 !important; }
    .swal2-icon.swal2-success .swal2-success-ring { border-color: #10b981 !important; }
    .swal2-timer-progress-bar { background: #4f46e5 !important; }
    .swal2-backdrop-show { backdrop-filter: blur(4px) !important; }

    #ui-toast-wrapper {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        pointer-events: none;
    }
    .ui-toast {
        pointer-events: all;
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.65rem 1rem 0.65rem 0.875rem;
        border-radius: 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        box-shadow: 0 4px 24px 0 rgba(0,0,0,0.13);
        border: 1.5px solid transparent;
        min-width: 220px;
        max-width: 340px;
        opacity: 0;
        transform: translateX(30px);
        transition: opacity 0.25s ease, transform 0.25s ease;
        position: relative;
        overflow: hidden;
        background: #fff;
    }
    .dark .ui-toast { background: #1e293b; }
    .ui-toast.show { opacity: 1; transform: translateX(0); }
    .ui-toast.hide { opacity: 0; transform: translateX(30px); }
    .ui-toast.info  { border-color: #c7d2fe; color: #4338ca; }
    .ui-toast.error { border-color: #fecaca; color: #dc2626; }
    .dark .ui-toast.info  { border-color: #3730a3; color: #a5b4fc; background: #1e1b4b; }
    .dark .ui-toast.error { border-color: #7f1d1d; color: #fca5a5; background: #1c0f0f; }
    .ui-toast-bar {
        position: absolute;
        bottom: 0; left: 0;
        height: 3px;
        border-radius: 0 0 0.875rem 0.875rem;
        width: 100%;
        transform-origin: left;
        animation: toastBar 3.5s linear forwards;
    }
    .ui-toast.info  .ui-toast-bar { background: #6366f1; }
    .ui-toast.error .ui-toast-bar { background: #ef4444; }
    @keyframes toastBar {
        from { transform: scaleX(1); }
        to   { transform: scaleX(0); }
    }

    .resultado-stat-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem 0.75rem;
        border-radius: 0.875rem;
        border: 1.5px solid transparent;
        text-align: center;
        gap: 0.25rem;
    }
    .resultado-stat-card .stat-num {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
    }
    .resultado-stat-card .stat-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.8;
        line-height: 1.3;
    }
    .stat-ok    { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
    .stat-warn  { background: #fffbeb; border-color: #fde68a; color: #b45309; }
    .stat-err   { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

    .error-grupo-card {
        border-radius: 0.75rem;
        border: 1.5px solid #fecaca;
        background: #fef2f2;
        overflow: hidden;
    }
    .error-grupo-header {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.625rem 0.875rem;
        cursor: pointer;
        user-select: none;
    }
    .error-grupo-header:hover { background: rgba(0,0,0,0.03); }
    .error-grupo-body {
        border-top: 1px solid #fecaca;
        padding: 0.5rem 0.875rem 0.625rem;
        display: none;
    }
    .error-grupo-body.open { display: block; }
    .error-chip {
        display: inline-block;
        padding: 0.18rem 0.55rem;
        border-radius: 99px;
        font-size: 0.68rem;
        font-weight: 600;
        background: #fee2e2;
        color: #991b1b;
        margin: 0.15rem 0.2rem 0.15rem 0;
    }
    .toggle-arrow {
        margin-left: auto;
        transition: transform 0.2s;
        color: #ef4444;
        opacity: 0.7;
    }
    .error-grupo-header.open .toggle-arrow { transform: rotate(180deg); }

    /* Tabla principal «Datos del Presupuesto»: misma lógica visual que modal Presupuesto guardado (primera columna fija) */
    #tabla_wrapper table.dataTable thead th:first-child,
    #tabla_wrapper table.dataTable tbody td:first-child {
        position: sticky;
        left: 0;
        box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.08);
    }
    #tabla_wrapper table.dataTable thead th:first-child {
        z-index: 12;
        background-color: #f8fafc;
    }
    .dark #tabla_wrapper table.dataTable thead th:first-child {
        background-color: #020617;
    }
    #tabla_wrapper table.dataTable tbody td:first-child {
        z-index: 2;
        background-color: #f9fafb;
    }
    .dark #tabla_wrapper table.dataTable tbody td:first-child {
        background-color: #0f172a;
    }
</style>

<div id="ui-toast-wrapper"></div>

<div class="w-full mx-auto bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">

    <div class="px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-950">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Generar Presupuesto Oficial</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Gestión visual de costos por gerencia.</p>
            </div>
        </div>
    </div>

    <div class="px-6 md:px-8 py-5 border-b border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/30">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">Filtros y acciones</p>
        <div class="flex flex-col lg:flex-row lg:items-end gap-4 lg:gap-6">
            <div class="flex flex-wrap items-end gap-4 flex-1">
                <div class="w-full sm:w-40 group">
                    <label for="anioCorte" class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 ml-1">Año</label>
                    <div class="relative">
                        <select name="anioCorte" id="anioCorte"
                            class="w-full h-11 pl-4 pr-10 appearance-none cursor-pointer rounded-xl outline-none transition-all duration-200
                                   bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700
                                   text-slate-700 dark:text-slate-200 text-sm font-medium
                                   hover:border-indigo-400 dark:hover:border-indigo-500
                                   focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                            @foreach($years as $y)
                            <option value="{{ $y }}" {{ $y == $anioConsulta ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="w-full sm:flex-1 sm:min-w-[200px] sm:max-w-sm group">
                    <label for="gerenciaID" class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 ml-1">Gerencia</label>
                    <div class="relative">
                        <select name="gerenciaID" id="gerenciaID"
                            class="w-full h-11 pl-4 pr-10 appearance-none cursor-pointer rounded-xl outline-none transition-all duration-200
                                   bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700
                                   text-slate-700 dark:text-slate-200 text-sm font-medium
                                   hover:border-indigo-400 dark:hover:border-indigo-500
                                   focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                            <option value="" disabled selected>Selecciona una opción...</option>
                            @foreach($gerencia as $g)
                            <option value="{{ $g->GerenciaID }}">{{ $g->NombreGerencia }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 items-center lg:pb-0.5">
                <button type="button" id="generarTodos"
                    title="Genera y guarda el presupuesto de TODAS las gerencias activas"
                    class="h-11 px-5 flex items-center justify-center gap-2 rounded-xl text-sm font-bold border-2 border-violet-400 dark:border-violet-600 text-violet-700 dark:text-violet-300 bg-gray-50 dark:bg-slate-900 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all duration-200">
                    <i class="fas fa-layer-group"></i> <span>Generar Todos</span>
                </button>
                <button type="button" id="enviar"
                    title="Genera el presupuesto de la gerencia seleccionada"
                    class="h-11 px-6 flex items-center justify-center gap-2 rounded-xl text-sm font-bold text-white shadow-lg shadow-indigo-500/20 dark:shadow-indigo-900/40 transition-all duration-200
                           bg-indigo-600 hover:bg-indigo-500 hover:-translate-y-0.5 active:translate-y-0 active:shadow-md">
                    <i class="fas fa-calculator"></i> <span>Generar Presupuesto</span>
                </button>
            </div>
        </div>
    </div>

    <div class="px-6 md:px-8 py-5 border-b border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-950/50">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">
            Estado por gerencia ({{ $anioConsulta }})
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 overflow-hidden min-h-[120px] flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20 shrink-0 flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Con Presupuesto</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm">({{ count($gerenciasConCorte) }})</span>
                    <span class="ml-auto text-[10px] text-emerald-600 dark:text-emerald-500 italic flex items-center gap-1">
                        <i class="fas fa-hand-pointer text-[9px]"></i> Click para ver
                    </span>
                </div>
                <div class="p-3 flex-1 overflow-y-auto custom-scroll min-h-0">
                    @forelse($gerenciasConCorte as $g)
                        <button type="button"
                            class="ver-corte-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                   bg-emerald-100 dark:bg-emerald-800/40 text-emerald-800 dark:text-emerald-200
                                   text-xs font-medium mr-1.5 mb-1
                                   hover:bg-emerald-200 dark:hover:bg-emerald-700/50 hover:shadow-sm active:scale-95
                                   transition-all duration-150 cursor-pointer border border-transparent
                                   hover:border-emerald-300 dark:hover:border-emerald-600"
                            data-gerencia-id="{{ $g->GerenciaID }}"
                            data-gerencia-nombre="{{ $g->NombreGerencia }}"
                            title="Ver presupuesto guardado de {{ $g->NombreGerencia }}">
                            <i class="fas fa-folder-open text-[10px] opacity-60"></i>
                            {{ $g->NombreGerencia }}
                        </button>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">Ninguna gerencia con presupuesto en este año.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 overflow-hidden min-h-[120px] flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-amber-50 dark:bg-amber-900/20 shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Sin presupuesto</span>
                    <span class="ml-2 text-slate-500 dark:text-slate-400 text-sm">({{ count($gerenciasSinCorte) }})</span>
                </div>
                <div class="p-3 flex-1 overflow-y-auto custom-scroll min-h-0">
                    @forelse($gerenciasSinCorte as $g)
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-800/40 text-amber-800 dark:text-amber-200 text-xs font-medium mr-1.5 mb-1">{{ $g->NombreGerencia }}</span>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">Todas las gerencias tienen presupuesto.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 md:px-8 py-5">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">Datos del Presupuesto</p>
    </div>
    <div class="relative w-full custom-scroll overflow-x-auto bg-gray-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
        <table id="tabla" class="w-full text-left border-collapse min-w-[780px]">
            <thead class="bg-gray-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800"></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900"></tbody>
        </table>

        <div id="tabla-placeholder" class="py-24 text-center bg-gray-50 dark:bg-slate-900 transition-colors">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 dark:bg-slate-800 mb-4">
                <i class="fas fa-search-dollar text-3xl text-slate-300 dark:text-slate-600"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-700 dark:text-slate-200">Esperando datos</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">Selecciona una gerencia y pulsa <strong class="text-slate-600 dark:text-slate-300">Generar presupuesto</strong> para ver el desglose por mes (igual que «Ver presupuesto guardado»).</p>
        </div>

        <div id="guardar-en-footer" class="hidden">
            <button type="button" id="guardar"
                class="h-10 px-5 flex items-center justify-center gap-2 rounded-xl text-sm font-bold text-white shadow-lg shadow-emerald-500/20 dark:shadow-emerald-900/40 transition-all duration-200
                       bg-emerald-600 hover:bg-emerald-500 hover:-translate-y-0.5 active:translate-y-0 active:shadow-md">
                <i class="fas fa-save"></i> <span>Guardar</span>
            </button>
        </div>
    </div>
    <p class="px-6 md:px-8 py-2.5 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900">
        Costo total (año) = suma anual de todos los meses.
    </p>

    <div id="bloque-corte-guardado" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="modal-corte-guardado-title">
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" id="modal-corte-guardado-backdrop"></div>
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-6xl max-h-[90vh] flex flex-col bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="flex items-center justify-between shrink-0 px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                    <h2 id="modal-corte-guardado-title" class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-folder-open text-indigo-500"></i>
                        <span>Presupuesto guardado</span>
                        <span id="modal-corte-nombre" class="text-slate-400 dark:text-slate-500 font-normal text-base"></span>
                    </h2>
                    <button type="button" id="cerrar-corte-guardado"
                        class="rounded-lg p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition-colors"
                        aria-label="Cerrar">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div id="modal-corte-loading" class="hidden flex-1 flex items-center justify-center py-16">
                    <div class="flex flex-col items-center gap-3">
                        <i class="fas fa-spinner fa-spin text-2xl text-slate-400 dark:text-slate-500"></i>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Cargando presupuesto...</p>
                    </div>
                </div>
                <div id="modal-corte-contenido" class="flex-1 min-h-0 overflow-auto p-4 sm:p-6">
                    <div class="overflow-x-auto custom-scroll -mx-2 sm:mx-0">
                        <table id="tabla-guardados" class="w-full text-left border-collapse min-w-[700px]">
                            <thead id="tabla-guardados-head" class="bg-gray-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800"></thead>
                            <tbody id="tabla-guardados-body" class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900"></tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-4">
                        Costo total (año) = suma anual de todos los meses.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {

    const currencyFmt = new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: 'MXN', maximumFractionDigits: 2
    });

    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio',
                   'Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function uiToast(tipo, mensaje) {
        const wrapper = document.getElementById('ui-toast-wrapper');
        const el = document.createElement('div');
        el.className = `ui-toast ${tipo}`;
        const iconos = { info: 'fa-circle-info', error: 'fa-circle-xmark' };
        el.innerHTML = `
            <i class="fas ${iconos[tipo] || 'fa-circle-info'} text-base shrink-0"></i>
            <span class="flex-1 leading-snug">${mensaje}</span>
            <div class="ui-toast-bar"></div>`;
        wrapper.appendChild(el);
        requestAnimationFrame(() => { requestAnimationFrame(() => { el.classList.add('show'); }); });
        const timer = setTimeout(() => {
            el.classList.remove('show');
            el.classList.add('hide');
            setTimeout(() => el.remove(), 300);
        }, 3500);
        el.addEventListener('click', () => {
            clearTimeout(timer);
            el.classList.remove('show');
            el.classList.add('hide');
            setTimeout(() => el.remove(), 300);
        });
    }

    function mostrarProgreso(titulo, texto) {
        Swal.fire({
            title              : titulo,
            html               : texto,
            allowOutsideClick  : false,
            allowEscapeKey     : false,
            showConfirmButton  : false,
            didOpen            : () => Swal.showLoading()
        });
    }

    function cerrarSwal() { Swal.close(); }

    function presupuestoEstaVencido(anio) {
        const ahora     = new Date();
        const limiteMax = new Date(anio, 5, 30, 23, 59, 59);
        return ahora > limiteMax;
    }

    /**
     * Agrupa los errores por mensaje normalizado.
     * Detecta si todos los errores son del mismo tipo y retorna
     * un array de grupos: { tipo, mensaje, gerencias[] }
     */
    function agruparErrores(errores) {
        const grupos = {};
        errores.forEach(e => {
            const raw = e.msg || 'Error desconocido';
            // Extrae el "tipo" quitando el nombre de gerencia del mensaje
            // Ej: "Plazo vencido: no se puede sobreescribir el presupuesto de 2024." → clave limpia
            const clave = raw
                .replace(/^Plazo vencido:.*/i, 'PLAZO_VENCIDO')
                .replace(/^No se pudo.*/i, 'ERROR_PROCESAMIENTO')
                .trim();

            if (!grupos[clave]) {
                grupos[clave] = { clave, mensaje: raw, gerencias: [] };
            }
            grupos[clave].gerencias.push(e.gerencia);
        });
        return Object.values(grupos);
    }

    function buildResumenHtml(ok, sinDatos, errores, anio) {
        const totalGerencias = ok + sinDatos + errores.length;
        const tieneErrores   = errores.length > 0;
        const tieneOk        = ok > 0;
        const tieneSinDatos  = sinDatos > 0;

        // Stats cards
        let statsHtml = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-bottom:1rem;">';
        statsHtml += `<div class="resultado-stat-card stat-ok">
            <span class="stat-num">${ok}</span>
            <span class="stat-label">Guardadas<br>correctamente</span>
        </div>`;
        statsHtml += `<div class="resultado-stat-card stat-warn">
            <span class="stat-num">${sinDatos}</span>
            <span class="stat-label">Sin datos<br>en sistema</span>
        </div>`;
        statsHtml += `<div class="resultado-stat-card stat-err">
            <span class="stat-num">${errores.length}</span>
            <span class="stat-label">Con<br>error</span>
        </div>`;
        statsHtml += '</div>';

        // Sección de errores agrupados
        let erroresHtml = '';
        if (tieneErrores) {
            const grupos = agruparErrores(errores);
            const todosPlazoVencido = grupos.length === 1 && grupos[0].clave === 'PLAZO_VENCIDO';

            if (todosPlazoVencido) {
                // Mensaje global único, sin listar cada gerencia en el cuerpo
                const g = grupos[0];
                erroresHtml = `
                <div class="error-grupo-card" style="margin-top:0.75rem;">
                    <div class="error-grupo-header" onclick="toggleGrupoError(this)">
                        <i class="fas fa-lock" style="color:#dc2626;font-size:0.85rem;"></i>
                        <div style="flex:1;text-align:left;">
                            <p style="font-size:0.78rem;font-weight:700;color:#991b1b;margin:0;">Plazo vencido — ${g.gerencias.length} gerencia${g.gerencias.length > 1 ? 's' : ''}</p>
                            <p style="font-size:0.7rem;color:#b91c1c;margin:0;opacity:0.85;">El presupuesto de <strong>${anio}</strong> cerró el 30 de junio. No es posible modificarlo.</p>
                        </div>
                        <i class="fas fa-chevron-down toggle-arrow" style="font-size:0.7rem;"></i>
                    </div>
                    <div class="error-grupo-body">
                        <p style="font-size:0.68rem;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.35rem;">Gerencias afectadas</p>
                        <div>${g.gerencias.map(n => `<span class="error-chip">${escapeHtml(n)}</span>`).join('')}</div>
                    </div>
                </div>`;
            } else {
                // Múltiples tipos de error → una tarjeta por grupo
                erroresHtml = '<div style="margin-top:0.75rem;display:flex;flex-direction:column;gap:0.5rem;">';
                grupos.forEach(g => {
                    const labelMap = {
                        'PLAZO_VENCIDO'       : { icon: 'fa-lock',           label: `Plazo vencido — ${g.gerencias.length} gerencia${g.gerencias.length > 1 ? 's' : ''}`, sub: `Presupuesto cerrado el 30 de junio de ${anio}.` },
                        'ERROR_PROCESAMIENTO' : { icon: 'fa-triangle-exclamation', label: `Error de procesamiento — ${g.gerencias.length}`, sub: 'Revisa los datos del sistema.' },
                    };
                    const info = labelMap[g.clave] || { icon: 'fa-circle-xmark', label: `Error (${g.gerencias.length})`, sub: g.mensaje };
                    erroresHtml += `
                    <div class="error-grupo-card">
                        <div class="error-grupo-header" onclick="toggleGrupoError(this)">
                            <i class="fas ${info.icon}" style="color:#dc2626;font-size:0.85rem;"></i>
                            <div style="flex:1;text-align:left;">
                                <p style="font-size:0.78rem;font-weight:700;color:#991b1b;margin:0;">${info.label}</p>
                                <p style="font-size:0.7rem;color:#b91c1c;margin:0;opacity:0.85;">${escapeHtml(info.sub)}</p>
                            </div>
                            <i class="fas fa-chevron-down toggle-arrow" style="font-size:0.7rem;"></i>
                        </div>
                        <div class="error-grupo-body">
                            <div>${g.gerencias.map(n => `<span class="error-chip">${escapeHtml(n)}</span>`).join('')}</div>
                        </div>
                    </div>`;
                });
                erroresHtml += '</div>';
            }
        }

        // Mensaje contextual de éxito parcial
        let contextMsg = '';
        if (tieneOk && tieneErrores) {
            contextMsg = `<p style="font-size:0.72rem;color:#64748b;text-align:center;margin-top:0.75rem;">
                <i class="fas fa-circle-info" style="margin-right:0.25rem;"></i>
                ${ok} gerencia${ok > 1 ? 's fueron guardadas' : ' fue guardada'} correctamente.
            </p>`;
        } else if (!tieneOk && !tieneSinDatos && tieneErrores) {
            contextMsg = `<p style="font-size:0.72rem;color:#dc2626;text-align:center;margin-top:0.75rem;font-weight:600;">
                <i class="fas fa-triangle-exclamation" style="margin-right:0.25rem;"></i>
                Ninguna gerencia pudo ser procesada.
            </p>`;
        }

        return `<div style="text-align:left;">${statsHtml}${erroresHtml}${contextMsg}</div>`;
    }

    function costoPorMesNum(montos, mesNum) {
        const arr = montos || [];
        const hit = arr.find(mp => Number(mp.Mes) === mesNum);
        return hit && !Number.isNaN(Number(hit.Costo)) ? Number(hit.Costo) : 0;
    }

    const thCorte = 'py-2 px-2 sm:px-3 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider';

    const columnasMeses = MESES.map((nombreMes, i) => ({
        data      : null,
        title     : nombreMes,
        className : `${thCorte} text-right border-b border-slate-100 dark:border-slate-800 align-middle whitespace-nowrap`,
        orderable : false,
        render    : function (row) {
            const v = costoPorMesNum(row.MontosPorMes, i + 1);
            return v > 0
                ? `<span class="font-mono text-xs text-slate-600 dark:text-slate-300">${currencyFmt.format(v)}</span>`
                : '<span class="text-slate-300 dark:text-slate-600">-</span>';
        }
    }));

    const table = $('#tabla').DataTable({
        destroy     : true,
        responsive  : false,
        searching   : false,
        processing  : true,
        serverSide  : true,
        paging      : false,
        lengthChange: false,
        info        : false,
        autoWidth   : false,
        scrollX     : true,
        dom         : 'rt<"dt-corte-footer flex flex-col sm:flex-row justify-end items-center gap-3 p-5 border-t border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-950">',
        language: {
            zeroRecords: "<div class='py-8 text-center text-slate-400 dark:text-slate-500 italic'>Sin resultados</div>",
            processing : '<span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando…</span>'
        },
        ajax: {
            url : '{{ route("cortes.ver") }}',
            type: 'GET',
            data: function (d) { d.gerenciaID = $('#gerenciaID').val(); },
            dataSrc: function (json) {
                if ($('#gerenciaID').val()) {
                    $('#tabla-placeholder').hide();
                } else {
                    $('#tabla-placeholder').show();
                }
                if (!json || !Array.isArray(json.data)) return [];
                return json.data.map(r => ({
                    NombreInsumo : r.NombreInsumo,
                    MontosPorMes : r.MontosPorMes || [],
                    Distintos    : r.Distintos || [],
                }));
            },
            error: function (xhr) {
                console.error('Error AJAX:', xhr);
                uiToast('error', 'Error al cargar datos de la tabla');
            }
        },
        columns: [
            {
                data      : 'NombreInsumo',
                title     : 'Insumo',
                className : `${thCorte} text-left border-b border-slate-100 dark:border-slate-800 align-middle`,
                render    : d => `<span class="text-sm font-semibold text-slate-800 dark:text-white">${escapeHtml(decodeHtmlFully(d || ''))}</span>`
            },
            ...columnasMeses,
            {
                data      : null,
                title     : 'Total año',
                orderable : false,
                className : `${thCorte} text-right border-b border-slate-100 dark:border-slate-800 align-middle whitespace-nowrap`,
                render    : function (row) {
                    const total = (row.MontosPorMes || []).reduce((acc, mp) => acc + (Number(mp.Costo) || 0), 0);
                    return total > 0
                        ? `<span class="font-mono text-sm font-bold text-slate-800 dark:text-white">${currencyFmt.format(total)}</span>`
                        : '<span class="text-slate-300 dark:text-slate-600">-</span>';
                }
            }
        ],
        createdRow: function (row) {
            $(row).addClass('border-t border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800/50');
        },
        drawCallback: function () {
            const base     = 'px-3 py-1.5 ml-1.5 rounded-lg border text-xs font-semibold transition-all duration-200 cursor-pointer shadow-sm ';
            const normal   = 'bg-gray-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-indigo-600 dark:hover:text-indigo-400';
            const active   = '!bg-indigo-600 !border-indigo-600 !text-white shadow-indigo-500/30 hover:!bg-indigo-700';
            const disabled = 'opacity-40 cursor-not-allowed shadow-none';
            const $pag = $('.dataTables_paginate .paginate_button');
            if ($pag.length) {
                $pag.addClass(base + normal);
                $('.dataTables_paginate .paginate_button.current').removeClass(normal).addClass(active);
                $('.dataTables_paginate .paginate_button.disabled').addClass(disabled);
            }
        },
        initComplete: function () {
            const $footer = $('.dt-corte-footer');
            if ($footer.length && $('#guardar').length) {
                $('#guardar').appendTo($footer).removeClass('hidden').show();
            }
        }
    });

    $('#enviar').on('click', async function () {
        const gid    = $('#gerenciaID').val();
        const nombre = $('#gerenciaID option:selected').text().trim();
        const anio   = $('#anioCorte').val();

        if (!gid) {
            Swal.fire({
                icon             : 'warning',
                title            : 'Gerencia requerida',
                text             : 'Por favor selecciona una gerencia antes de continuar.',
                confirmButtonColor: '#4f46e5',
                confirmButtonText : 'Entendido'
            });
            return;
        }

        let yaExiste = false;
        try {
            const chk     = await fetch(
                '{{ route("cortes.guardados") }}?anio=' + encodeURIComponent(anio) + '&gerenciaID=' + encodeURIComponent(gid),
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const chkJson = await chk.json().catch(() => ({}));
            yaExiste = Array.isArray(chkJson.data) && chkJson.data.length > 0;
        } catch (_) {}

        if (yaExiste) {
            const result = await Swal.fire({
                icon             : 'warning',
                title            : 'Presupuesto ya generado',
                html             : `
                    <div class="text-left space-y-3">
                        <p class="text-slate-600">La gerencia <strong class="text-slate-800">${escapeHtml(nombre)}</strong> ya tiene un presupuesto generado para <strong>${anio}</strong>.</p>
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 flex gap-2.5 items-start">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 shrink-0"></i>
                            <p class="text-xs text-amber-700">Si continúas, la vista de datos se <strong>regenerará desde el SP</strong>. Los datos guardados no cambiarán hasta que presiones <em>Guardar</em>.</p>
                        </div>
                    </div>`,
                showCancelButton : true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor : '#64748b',
                confirmButtonText : '<i class="fas fa-calculator"></i>&nbsp; Sí, regenerar vista',
                cancelButtonText  : 'Cancelar',
                focusCancel       : true
            });
            if (!result.isConfirmed) return;
        }

        uiToast('info', `Generando presupuesto de ${escapeHtml(nombre)}...`);
        table.ajax.reload(null, false);
    });

    $('#generarTodos').on('click', async function () {
        const anio           = $('#anioCorte').val();
        const totalConCorte  = {{ count($gerenciasConCorte) }};
        const totalSin       = {{ count($gerenciasSinCorte) }};
        const totalGerencias = totalConCorte + totalSin;

        const step1 = await Swal.fire({
            icon             : 'question',
            title            : 'Generar para todas las gerencias',
            html             : `
                <div class="text-left space-y-3">
                    <p class="text-slate-600 dark:text-slate-300">Se procesarán <strong class="text-slate-800 dark:text-white">${totalGerencias} gerencias activas</strong> para el año <strong class="text-slate-800 dark:text-white">${anio}</strong>.</p>
                    <div class="grid grid-cols-2 gap-2 my-2">
                        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-center">
                            <p class="text-2xl font-bold text-emerald-600">${totalConCorte}</p>
                            <p class="text-xs text-emerald-700 mt-0.5">Con presupuesto<br><span class="font-semibold">serán sobreescritas</span></p>
                        </div>
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-center">
                            <p class="text-2xl font-bold text-amber-600">${totalSin}</p>
                            <p class="text-xs text-amber-700 mt-0.5">Sin presupuesto<br><span class="font-semibold">serán generadas</span></p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-red-50 border border-red-200 p-3 flex gap-2.5 items-start">
                        <i class="fas fa-triangle-exclamation text-red-500 mt-0.5 shrink-0"></i>
                        <p class="text-xs text-red-700"><strong>Acción irreversible:</strong> Los presupuestos existentes serán <strong>reemplazados permanentemente</strong> con los datos actuales del SP.</p>
                    </div>
                </div>`,
            showCancelButton : true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor : '#64748b',
            confirmButtonText : 'Continuar &rarr;',
            cancelButtonText  : 'Cancelar',
            focusCancel       : true
        });

        if (!step1.isConfirmed) return;

        const step2 = await Swal.fire({
            icon             : 'warning',
            title            : 'Confirma la acción',
            html             : `
                <div class="text-left space-y-3">
                    <p class="text-slate-600">Esta operación afectará <strong>todas las gerencias</strong> y no se puede deshacer.</p>
                    <p class="text-sm text-slate-500">Para confirmar, escribe <strong class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">CONFIRMAR</strong> en el campo:</p>
                    <input id="swal-confirm-input" type="text" placeholder="Escribe CONFIRMAR aquí"
                        class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 outline-none
                               focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 transition-all duration-200"
                        autocomplete="off" />
                </div>`,
            showCancelButton : true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor : '#64748b',
            confirmButtonText : '<i class="fas fa-layer-group"></i>&nbsp; Generar todos ahora',
            cancelButtonText  : 'Cancelar',
            focusCancel       : true,
            preConfirm: () => {
                const val = document.getElementById('swal-confirm-input').value.trim().toUpperCase();
                if (val !== 'CONFIRMAR') {
                    Swal.showValidationMessage('Debes escribir CONFIRMAR exactamente para continuar.');
                    return false;
                }
                return true;
            }
        });

        if (!step2.isConfirmed) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        mostrarProgreso('Procesando todas las gerencias...', `<p class="text-sm text-slate-500">Esto puede tardar unos momentos. Por favor espera.</p>`);

        try {
            const res  = await fetch('{{ route("cortes.storeAll") }}', {
                method : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept'      : 'application/json'
                },
                body: JSON.stringify({ anio })
            });

            const json = await res.json().catch(() => ({}));
            cerrarSwal();

            if (!res.ok) {
                Swal.fire({
                    icon             : 'error',
                    title            : 'Error del servidor',
                    html             : `<p class="text-sm text-slate-600">${escapeHtml(json.message || `Error HTTP ${res.status}`)}</p>`,
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            const resultados = json.resultados || [];
            const ok         = resultados.filter(r => r.status === 'ok').length;
            const sinDatos   = resultados.filter(r => r.status === 'sin_datos').length;
            const errores    = resultados.filter(r => r.status === 'error');
            const hayErrores = errores.length > 0;

            await Swal.fire({
                icon             : hayErrores ? (ok > 0 ? 'warning' : 'error') : 'success',
                title            : hayErrores ? (ok > 0 ? 'Proceso con advertencias' : 'Sin cambios') : 'Proceso completado',
                html             : buildResumenHtml(ok, sinDatos, errores, anio),
                confirmButtonColor: '#4f46e5',
                confirmButtonText : ok > 0 ? 'Aceptar y recargar' : 'Cerrar',
                width            : '32rem'
            });

            if (ok > 0) location.reload();

        } catch (e) {
            cerrarSwal();
            Swal.fire({
                icon             : 'error',
                title            : 'Error inesperado',
                text             : e?.message || 'Ocurrió un error. Revisa la consola para más detalles.',
                confirmButtonColor: '#4f46e5'
            });
        } finally {
            $btn.prop('disabled', false).html('<i class="fas fa-layer-group"></i> <span>Generar Todos</span>');
        }
    });

    $('#anioCorte').on('change', function () {
        const anio = $(this).val();
        if (!anio) return;

        Swal.fire({
            icon             : 'question',
            title            : `Cambiar al año ${anio}`,
            text             : 'Se recargará la página para mostrar los datos de ese año.',
            showCancelButton : true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor : '#64748b',
            confirmButtonText : `Ver ${anio}`,
            cancelButtonText  : 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = '{{ route("cortes.index") }}?anio=' + anio;
            } else {
                $(this).val('{{ $anioConsulta }}');
            }
        });
    });

    async function abrirCorteGuardado(gid, anio, nombreGerencia) {
        $('#modal-corte-nombre').text(nombreGerencia ? '— ' + nombreGerencia : '');
        $('#modal-corte-loading').removeClass('hidden').addClass('flex');
        $('#modal-corte-contenido').addClass('hidden');
        $('#bloque-corte-guardado').removeClass('hidden');
        document.body.classList.add('overflow-hidden');

        try {
            const res  = await fetch(
                '{{ route("cortes.guardados") }}?anio=' + encodeURIComponent(anio) + '&gerenciaID=' + encodeURIComponent(gid),
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const json = await res.json().catch(() => ({}));
            renderTablaGuardados(json.data || []);
        } catch (e) {
            cerrarModal();
            Swal.fire({
                icon             : 'error',
                title            : 'No se pudo cargar',
                text             : e?.message || 'Error al obtener el presupuesto guardado.',
                confirmButtonColor: '#4f46e5'
            });
            return;
        }

        $('#modal-corte-loading').addClass('hidden').removeClass('flex');
        $('#modal-corte-contenido').removeClass('hidden');
    }

    $(document).on('click', '.ver-corte-badge', async function () {
        const gid    = $(this).data('gerencia-id');
        const nombre = $(this).data('gerencia-nombre');
        const anio   = $('#anioCorte').val();
        $('#gerenciaID').val(gid);
        await abrirCorteGuardado(gid, anio, nombre);
    });

    function renderTablaGuardados(data) {
        const thead = $('#tabla-guardados-head');
        const tbody = $('#tabla-guardados-body');
        thead.empty();
        tbody.empty();

        if (!data.length) {
            thead.html(`<tr>
                <th class="py-3 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insumo</th>
                <th class="py-3 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Info</th>
            </tr>`);
            tbody.append('<tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No hay presupuesto guardado para esta gerencia y año.</td></tr>');
            return;
        }

        const th = 'py-3 px-2 sm:px-3 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider';
        let headHtml = `<tr>
            <th class="${th} text-left sticky left-0 z-10 bg-gray-50 dark:bg-slate-950 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)] px-3 sm:px-4">Insumo</th>`;
        MESES.forEach(m => { headHtml += `<th class="${th} text-right whitespace-nowrap">${m}</th>`; });
        headHtml += `<th class="${th} text-right">Total año</th></tr>`;
        thead.html(headHtml);

        const filtrados = data.filter(row => decodeHtmlFully(String(row.NombreInsumo || '')).trim().toLowerCase() !== 'costo base');
        const porInsumo = {};
        filtrados.forEach(row => {
            const key = decodeHtmlFully(String(row.NombreInsumo || '').trim());
            if (!key) return;
            if (!porInsumo[key]) porInsumo[key] = [];
            porInsumo[key].push(row);
        });

        const nombresOrden = [];
        const seenNombre = new Set();
        filtrados.forEach(r => {
            const k = decodeHtmlFully(String(r.NombreInsumo || '').trim());
            if (!k || seenNombre.has(k)) return;
            seenNombre.add(k);
            nombresOrden.push(k);
        });

        nombresOrden.forEach(nombreInsumo => {
            const variantes = porInsumo[nombreInsumo];
            if (!variantes || !variantes.length) return;

            const acumMes = {};
            MESES.forEach(m => { acumMes[m] = 0; });
            variantes.forEach(row => {
                const meses = row.Meses || {};
                MESES.forEach(m => {
                    const v = meses[m]?.CostoTotal ? Number(meses[m].CostoTotal) : 0;
                    if (!Number.isNaN(v)) acumMes[m] += v;
                });
            });
            const costoTotalAnual = MESES.reduce((acc, m) => acc + acumMes[m], 0);

            const celdasMes = MESES.map(m => {
                const v = acumMes[m];
                return `<td class="py-2 px-2 sm:px-3 text-right text-xs font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">
                    ${v > 0 ? currencyFmt.format(v) : '<span class="text-slate-300 dark:text-slate-600">-</span>'}
                </td>`;
            }).join('');

            const trClass = 'border-t border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800/50';

            tbody.append(`<tr class="${trClass}">
                <td class="py-2 px-3 sm:px-4 text-sm font-semibold text-slate-800 dark:text-white sticky left-0 z-[1] bg-gray-50 dark:bg-slate-900 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">${escapeHtml(decodeHtmlFully(nombreInsumo))}</td>
                ${celdasMes}
                <td class="py-2 px-3 sm:px-4 text-right text-sm font-mono font-bold text-slate-800 dark:text-white whitespace-nowrap">
                    ${costoTotalAnual > 0 ? currencyFmt.format(costoTotalAnual) : '-'}
                </td>
            </tr>`);
        });

        if (!filtrados.length) {
            tbody.empty();
            tbody.append('<tr><td colspan="14" class="py-8 text-center text-slate-500 dark:text-slate-400">No quedan insumos que mostrar (solo «Costo base» u omitidos).</td></tr>');
        }
    }

    function cerrarModal() {
        $('#bloque-corte-guardado').addClass('hidden');
        $('#modal-corte-loading').addClass('hidden').removeClass('flex');
        $('#modal-corte-contenido').removeClass('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    $('#cerrar-corte-guardado').on('click', cerrarModal);
    $('#modal-corte-guardado-backdrop').on('click', cerrarModal);
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$('#bloque-corte-guardado').hasClass('hidden')) cerrarModal();
    });

    $('#guardar').on('click', async function () {
        const gid    = $('#gerenciaID').val();
        const nombre = $('#gerenciaID option:selected').text().trim();
        const anio   = parseInt($('#anioCorte').val(), 10) || new Date().getFullYear();

        if (!gid) {
            Swal.fire({
                icon             : 'error',
                title            : 'Gerencia requerida',
                text             : 'Selecciona una gerencia antes de guardar.',
                confirmButtonColor: '#4f46e5'
            });
            return;
        }

        const payload = [];
        $('#tabla tbody tr').each(function () {
            const row = table.row(this).data();
            if (!row) return;
            if (decodeHtmlFully(String(row.NombreInsumo || '')).trim().toLowerCase() === 'costo base') return;
            for (const mp of (row.MontosPorMes || [])) {
                if (Number(mp.Costo) <= 0) continue;
                const costo = +Number(mp.Costo).toFixed(2);
                payload.push({
                    NombreInsumo: decodeHtml(String(row.NombreInsumo || 'SIN_NOMBRE')),
                    Mes         : mp.Mes,
                    Costo       : costo,
                    Margen      : 0,
                    CostoTotal  : costo,
                    GerenciaID  : Number(gid)
                });
            }
        });

        if (!payload.length) {
            Swal.fire({
                icon             : 'warning',
                title            : 'Sin datos para guardar',
                text             : 'El presupuesto no contiene registros con monto mayor a cero.',
                confirmButtonColor: '#4f46e5'
            });
            return;
        }

        let yaExiste = false;
        try {
            const chk     = await fetch(
                '{{ route("cortes.guardados") }}?anio=' + encodeURIComponent(anio) + '&gerenciaID=' + encodeURIComponent(gid),
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const chkJson = await chk.json().catch(() => ({}));
            yaExiste      = Array.isArray(chkJson.data) && chkJson.data.length > 0;
        } catch (_) {
            yaExiste = true;
        }

        if (presupuestoEstaVencido(anio)) {
            const accionPalabra = yaExiste ? 'modificado' : 'creado';
            const accionVerbo   = yaExiste ? 'sobreescribirse' : 'crearse';
            Swal.fire({
                icon             : 'error',
                title            : 'Plazo vencido',
                html             : `
                    <div class="text-left space-y-3">
                        <p class="text-slate-600">El presupuesto de <strong class="text-slate-800">${escapeHtml(nombre)}</strong> para <strong>${anio}</strong> ya no puede ser ${accionPalabra}.</p>
                        <div class="rounded-xl bg-red-50 border border-red-200 p-3 flex gap-2.5 items-start">
                            <i class="fas fa-lock text-red-500 mt-0.5 shrink-0"></i>
                            <p class="text-xs text-red-700">Los presupuestos solo pueden <strong>${accionVerbo}</strong> dentro de los primeros <strong>6 meses</strong> del año. El plazo límite fue el <strong>30 de junio de ${anio}</strong>.</p>
                        </div>
                    </div>`,
                confirmButtonColor: '#4f46e5',
                confirmButtonText : 'Entendido'
            });
            return;
        }

        const mensajeHtml = yaExiste
            ? `<div class="text-left space-y-3">
                <p class="text-slate-600">Estás a punto de <strong>sobreescribir</strong> el presupuesto guardado de:</p>
                <div class="rounded-xl bg-gray-50 border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <i class="fas fa-building text-slate-400 text-lg"></i>
                    <div>
                        <p class="font-bold text-slate-800">${escapeHtml(nombre)}</p>
                        <p class="text-xs text-slate-500">Año ${anio} · ${payload.length} registros nuevos</p>
                    </div>
                </div>
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 flex gap-2.5 items-start">
                    <i class="fas fa-triangle-exclamation text-red-500 mt-0.5 shrink-0"></i>
                    <p class="text-xs text-red-700">El presupuesto anterior será <strong>eliminado permanentemente</strong> y reemplazado con los datos actuales en pantalla.</p>
                </div>
               </div>`
            : `<div class="text-left space-y-3">
                <p class="text-slate-600">Se guardará el presupuesto de:</p>
                <div class="rounded-xl bg-gray-50 border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <i class="fas fa-building text-slate-400 text-lg"></i>
                    <div>
                        <p class="font-bold text-slate-800">${escapeHtml(nombre)}</p>
                        <p class="text-xs text-slate-500">Año ${anio} · ${payload.length} registros</p>
                    </div>
                </div>
               </div>`;

        const confirmResult = await Swal.fire({
            icon             : yaExiste ? 'warning' : 'question',
            title            : yaExiste ? '¿Sobreescribir presupuesto?' : '¿Guardar presupuesto?',
            html             : mensajeHtml,
            showCancelButton : true,
            confirmButtonColor: yaExiste ? '#16a34a' : '#4f46e5',
            cancelButtonColor : '#64748b',
            confirmButtonText : yaExiste
                ? '<i class="fas fa-save"></i>&nbsp; Sí, sobreescribir'
                : '<i class="fas fa-save"></i>&nbsp; Sí, guardar',
            cancelButtonText  : 'Cancelar',
            focusCancel       : yaExiste
        });

        if (!confirmResult.isConfirmed) return;

        const btn = $(this);
        const txt = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        mostrarProgreso('Guardando presupuesto...', `<p class="text-sm text-slate-500">Guardando ${payload.length} registros para <strong>${escapeHtml(nombre)}</strong>.</p>`);

        try {
            const res = await fetch('{{ route("cortes.store") }}', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body   : JSON.stringify({ rows: payload, anio })
            });

            const contentType = res.headers.get('content-type') || '';
            let data = null, text = '';
            if (contentType.includes('application/json')) {
                data = await res.json().catch(() => null);
            } else {
                text = await res.text().catch(() => '');
            }

            cerrarSwal();

            if (!res.ok) {
                const msg = (data && (data.message || data.error)) || text || `Error HTTP ${res.status}`;
                if (data?.errors && typeof data.errors === 'object') {
                    Swal.fire({
                        icon             : 'error',
                        title            : 'No se pudo guardar',
                        html             : `<div class="text-left text-sm text-red-600">${Object.values(data.errors).flat().map(e => `<p>- ${escapeHtml(e)}</p>`).join('')}</div>`,
                        confirmButtonColor: '#4f46e5'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: msg, confirmButtonColor: '#4f46e5' });
                }
                btn.prop('disabled', false).html(txt);
                return;
            }

            await Swal.fire({
                icon             : 'success',
                title            : yaExiste ? 'Presupuesto sobreescrito' : 'Presupuesto guardado',
                html             : `<p class="text-sm text-slate-600">${escapeHtml(nombre)} · Año ${anio} · ${payload.length} registros guardados correctamente.</p>`,
                timer            : 2200,
                timerProgressBar : true,
                showConfirmButton: false
            });
            location.reload();

        } catch (e) {
            cerrarSwal();
            Swal.fire({ icon: 'error', title: 'Error inesperado', text: e?.message || 'Error al guardar. Revisa tu conexión.', confirmButtonColor: '#4f46e5' });
            btn.prop('disabled', false).html(txt);
        }
    });

});

function toggleGrupoError(header) {
    const body = header.nextElementSibling;
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    header.classList.toggle('open', !isOpen);
}

function decodeHtml(str) {
    const txt = document.createElement('textarea');
    txt.innerHTML = str;
    return txt.value;
}

/** Decodifica entidades HTML repetidas (p. ej. AT&amp;amp;T → AT&T) que vienen del SP o de la BD. */
function decodeHtmlFully(str) {
    let s = String(str ?? '');
    for (let i = 0; i < 6; i++) {
        const next = decodeHtml(s);
        if (next === s) break;
        s = next;
    }
    return s;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
@endpush