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

    /* Budget Card Styles */
    .budget-card {
        background: #f9fafb;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    .dark .budget-card {
        background: #0f172a;
        border-color: #334155;
    }

    .budget-card__header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .dark .budget-card__header {
        background: #1e293b;
        border-bottom-color: #334155;
    }

    .budget-card__title-section h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        line-height: 1.3;
    }
    .dark .budget-card__title-section h3 {
        color: #f1f5f9;
    }

    .budget-card__title-section p {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0.25rem 0 0;
    }
    .dark .budget-card__title-section p {
        color: #94a3b8;
    }

    .budget-card__actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .budget-search {
        width: 280px;
        height: 2.5rem;
        padding: 0 1rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all 0.2s;
        outline: none;
    }
    .budget-search:focus {
        border-color: #6366f1;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .budget-search::placeholder {
        color: #94a3b8;
    }
    .budget-search:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .dark .budget-search {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }
    .dark .budget-search:focus {
        background: #1e293b;
        border-color: #6366f1;
    }

    .budget-counter {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        background: #f1f5f9;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
    }
    .dark .budget-counter {
        background: #334155;
        color: #cbd5e1;
    }

    .budget-card__body {
        position: relative;
        min-height: 300px;
    }

    .budget-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 2rem;
        text-align: center;
    }

    .budget-empty-state__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        border-radius: 50%;
        background: #f1f5f9;
        margin-bottom: 1rem;
    }
    .dark .budget-empty-state__icon {
        background: #1e293b;
    }

    .budget-empty-state__icon i {
        font-size: 1.75rem;
        color: #cbd5e1;
    }
    .dark .budget-empty-state__icon i {
        color: #475569;
    }

    .budget-empty-state h4 {
        font-size: 1rem;
        font-weight: 700;
        color: #334155;
        margin: 0 0 0.5rem;
    }
    .dark .budget-empty-state h4 {
        color: #e2e8f0;
    }

    .budget-empty-state p {
        font-size: 0.875rem;
        color: #64748b;
        max-width: 32rem;
        margin: 0;
        line-height: 1.5;
    }
    .dark .budget-empty-state p {
        color: #94a3b8;
    }

    .budget-table-wrapper {
        overflow-x: auto;
        overflow-y: visible;
    }

    .budget-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        min-width: 1200px;
    }

    .budget-table thead th {
        padding: 0.875rem 1rem;
        text-align: right;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .dark .budget-table thead th {
        background: #0f172a;
        color: #94a3b8;
        border-bottom-color: #334155;
    }

    .budget-table thead th.text-left {
        text-align: left;
    }

    .budget-table thead th.sticky-col {
        position: sticky;
        left: 0;
        z-index: 10;
        box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.08);
    }

    .budget-table thead th.total-col {
        font-size: 0.8125rem;
        font-weight: 800;
        color: #4f46e5;
        background: #eef2ff;
    }
    .dark .budget-table thead th.total-col {
        background: #1e1b4b;
        color: #a5b4fc;
    }

    .budget-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s;
    }
    .dark .budget-table tbody tr {
        border-bottom-color: #1e293b;
    }

    .budget-table tbody tr:hover {
        background: #f8fafc;
    }
    .dark .budget-table tbody tr:hover {
        background: #1e293b;
    }

    .budget-table tbody td {
        padding: 0.875rem 1rem;
        text-align: right;
        color: #475569;
        white-space: nowrap;
    }
    .dark .budget-table tbody td {
        color: #cbd5e1;
    }

    .budget-table tbody td.text-left {
        text-align: left;
    }

    .budget-table tbody td.sticky-col {
        position: sticky;
        left: 0;
        background: #ffffff;
        z-index: 1;
        box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.08);
        font-weight: 600;
        color: #1e293b;
    }
    .dark .budget-table tbody td.sticky-col {
        background: #0f172a;
        color: #f1f5f9;
    }

    .budget-table tbody tr:hover td.sticky-col {
        background: #f8fafc;
    }
    .dark .budget-table tbody tr:hover td.sticky-col {
        background: #1e293b;
    }

    .budget-table tbody td.total-col {
        font-weight: 700;
        font-size: 0.9375rem;
        color: #4f46e5;
        background: #f8fafc;
    }
    .dark .budget-table tbody td.total-col {
        background: #1e293b;
        color: #a5b4fc;
    }

    .budget-amount {
        font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, 'Courier New', monospace;
    }

    .budget-amount-empty {
        color: #cbd5e1;
        opacity: 0.5;
    }
    .dark .budget-amount-empty {
        color: #475569;
    }

    .budget-card__footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .dark .budget-card__footer {
        background: #1e293b;
        border-top-color: #334155;
    }

    .budget-card__footer-note {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0;
    }
    .dark .budget-card__footer-note {
        color: #94a3b8;
    }

    .btn-save-budget {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.5rem;
        border: none;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #ffffff;
        background: #10b981;
        box-shadow: 0 2px 8px 0 rgba(16, 185, 129, 0.2);
        cursor: pointer;
        transition: all 0.2s;
        outline: none;
    }
    .btn-save-budget:hover:not(:disabled) {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px 0 rgba(16, 185, 129, 0.3);
    }
    .btn-save-budget:active:not(:disabled) {
        transform: translateY(0);
    }
    .btn-save-budget:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .budget-no-results {
        padding: 3rem 1.5rem;
        text-align: center;
        font-size: 0.875rem;
        color: #94a3b8;
        font-style: italic;
    }
    .dark .budget-no-results {
        color: #64748b;
    }

    .budget-info-banner {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 1.25rem;
        margin-bottom: 1.25rem;
        border-radius: 0.75rem;
        border-left: 4px solid #6366f1;
        background: #eef2ff;
        font-size: 0.85rem;
        color: #4338ca;
    }
    .dark .budget-info-banner {
        background: #1e1b4b;
        border-left-color: #818cf8;
        color: #a5b4fc;
    }

    .budget-info-banner__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.15);
        flex-shrink: 0;
    }
    .dark .budget-info-banner__icon {
        background: rgba(129, 140, 248, 0.2);
    }

    .budget-info-banner__icon i {
        font-size: 0.875rem;
        color: #6366f1;
    }
    .dark .budget-info-banner__icon i {
        color: #818cf8;
    }

    .budget-info-banner__content {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        flex: 1;
    }

    .budget-info-banner__title {
        font-weight: 700;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin: 0;
    }

    .budget-info-banner__text {
        font-size: 0.8125rem;
        margin: 0;
        opacity: 0.9;
        line-height: 1.4;
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
                @can('generar-cortes')
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
                @endcan
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
        <div class="budget-info-banner">
            <div class="budget-info-banner__content">
                <p class="budget-info-banner__title">Tipos de empleados incluidos</p>
                <p class="budget-info-banner__text">Este presupuesto solo toma en cuenta empleados tipo <strong>FÍSICA</strong> y <strong>EXTRAORDINARIO</strong>.</p>
            </div>
        </div>

        <div class="budget-card">
            <div class="budget-card__header">
                <div class="budget-card__title-section">
                    <h3>Datos del presupuesto</h3>
                    <p>Desglose mensual por insumo</p>
                </div>

                <div class="budget-card__actions">
                    <input
                        type="text"
                        id="budget-search"
                        class="budget-search"
                        placeholder="Buscar insumo..."
                        autocomplete="off"
                        disabled
                    >
                    <span class="budget-counter">
                        <i class="fas fa-list-ul"></i>
                        <span id="budget-counter-text">0 insumos</span>
                    </span>
                </div>
            </div>

            <div class="budget-card__body">
                <div id="tabla-placeholder" class="budget-empty-state">
                    <div class="budget-empty-state__icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <h4>Sin presupuesto generado</h4>
                    <p>Selecciona una gerencia y pulsa <strong>Generar presupuesto</strong> para ver el desglose mensual y poder guardarlo.</p>
                </div>

                <div id="tabla-container" class="budget-table-wrapper" style="display: none;">
                    <table id="tabla" class="budget-table">
                        <thead>
                            <tr>
                                <th class="text-left sticky-col">Insumo</th>
                                <th>Enero</th>
                                <th>Febrero</th>
                                <th>Marzo</th>
                                <th>Abril</th>
                                <th>Mayo</th>
                                <th>Junio</th>
                                <th>Julio</th>
                                <th>Agosto</th>
                                <th>Septiembre</th>
                                <th>Octubre</th>
                                <th>Noviembre</th>
                                <th>Diciembre</th>
                                <th class="total-col">Total año</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-body">
                        </tbody>
                    </table>

                    <div id="tabla-no-results" class="budget-no-results" style="display: none;">
                        <i class="fas fa-search text-lg mb-2"></i>
                        <p>No se encontraron insumos que coincidan con tu búsqueda.</p>
                    </div>
                </div>
            </div>

            <div class="budget-card__footer">
                <p class="budget-card__footer-note">
                    <i class="fas fa-info-circle"></i>
                    Costo total (año) = suma anual de todos los meses.
                </p>

                <button
                    type="button"
                    id="guardar"
                    class="btn-save-budget"
                    disabled
                >
                    <i class="fas fa-save"></i>
                    <span>Guardar presupuesto</span>
                </button>
            </div>
        </div>
    </div>

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

    let budgetData = [];
    let filteredData = [];

    function renderBudgetTable(data) {
        budgetData = data || [];
        filteredData = [...budgetData];

        const tbody = $('#tabla-body');
        tbody.empty();

        if (!budgetData.length) {
            $('#tabla-placeholder').show();
            $('#tabla-container').hide();
            $('#guardar').prop('disabled', true);
            $('#budget-search').prop('disabled', true).val('');
            updateCounter(0);
            return;
        }

        $('#tabla-placeholder').hide();
        $('#tabla-container').show();
        $('#guardar').prop('disabled', false);
        $('#budget-search').prop('disabled', false);

        applySearchFilter();
    }

    function applySearchFilter() {
        const searchTerm = $('#budget-search').val().toLowerCase().trim();
        const tbody = $('#tabla-body');
        tbody.empty();

        filteredData = budgetData.filter(row => {
            const nombre = decodeHtmlFully(String(row.NombreInsumo || '')).toLowerCase();
            return nombre.includes(searchTerm);
        });

        if (!filteredData.length) {
            $('#tabla-no-results').show();
            tbody.parent().css('min-height', '0');
        } else {
            $('#tabla-no-results').hide();
            filteredData.forEach(row => {
                const nombreInsumo = escapeHtml(decodeHtmlFully(row.NombreInsumo || ''));
                let tr = '<tr>';
                
                // Columna Insumo (sticky)
                tr += `<td class="text-left sticky-col">${nombreInsumo}</td>`;

                // Columnas de meses
                MESES.forEach((mes, i) => {
                    const v = costoPorMesNum(row.MontosPorMes, i + 1);
                    if (v > 0) {
                        tr += `<td><span class="budget-amount">${currencyFmt.format(v)}</span></td>`;
                    } else {
                        tr += `<td><span class="budget-amount-empty">-</span></td>`;
                    }
                });

                // Columna Total año
                const total = (row.MontosPorMes || []).reduce((acc, mp) => acc + (Number(mp.Costo) || 0), 0);
                if (total > 0) {
                    tr += `<td class="total-col"><span class="budget-amount">${currencyFmt.format(total)}</span></td>`;
                } else {
                    tr += `<td class="total-col"><span class="budget-amount-empty">-</span></td>`;
                }

                tr += '</tr>';
                tbody.append(tr);
            });
        }

        updateCounter(filteredData.length);
    }

    function updateCounter(count) {
        const text = count === 1 ? '1 insumo' : `${count} insumos`;
        $('#budget-counter-text').text(text);
    }

    $('#budget-search').on('input', function() {
        applySearchFilter();
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
        
        // Cargar datos
        try {
            const res = await fetch(
                '{{ route("cortes.ver") }}?gerenciaID=' + encodeURIComponent(gid),
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const json = await res.json();
            
            if (!json || !Array.isArray(json.data)) {
                renderBudgetTable([]);
                return;
            }

            const data = json.data.map(r => ({
                NombreInsumo : r.NombreInsumo,
                MontosPorMes : r.MontosPorMes || [],
                Distintos    : r.Distintos || [],
            }));

            renderBudgetTable(data);
            $('#budget-search').val('');
        } catch (e) {
            console.error('Error al cargar datos:', e);
            uiToast('error', 'Error al cargar datos de la tabla');
            renderBudgetTable([]);
        }
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
        budgetData.forEach(row => {
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