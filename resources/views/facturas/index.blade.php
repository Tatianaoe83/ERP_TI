@extends('layouts.app')

@section('content')
<style>
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background-color: #475569; }

    /*
     * Los tabs se ocultan con visibility+height en lugar de display:none,
     * para que Highcharts pueda medir las dimensiones aunque el tab no esté activo.
     * Esto evita que las gráficas salgan con ancho 0 al cambiar de tab.
     */
    .tab-content {
        visibility: hidden;
        height: 0;
        overflow: hidden;
        opacity: 0;
        transition: opacity 0.25s ease-in-out;
    }
    .tab-content.active {
        visibility: visible;
        height: auto;
        overflow: visible;
        opacity: 1;
    }

    #facturasTable_wrapper .dataTables_processing {
        background: transparent;
        color: #6366f1;
        font-size: 0.8rem;
    }

    .insumo-select-wrap select {
        min-width: 160px;
        max-width: 220px;
    }
</style>

<div class="w-full mx-auto max-w-7xl">

    {{-- Header --}}
    <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <i class="fas fa-file-invoice text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Gestión de Facturas</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Administra y visualiza el historial.</p>
            </div>
        </div>

        <div class="flex p-1 bg-gray-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <button onclick="switchTab('facturas')" id="tab-facturas"
                class="px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2 bg-indigo-600 text-white shadow-md">
                <i class="fas fa-receipt"></i> Facturas
            </button>
            <button onclick="switchTab('historial')" id="tab-historial"
                class="px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">
                <i class="fas fa-history"></i> Comparativa
            </button>
        </div>
    </div>

    {{-- Tab Facturas --}}
    <div id="content-facturas" class="tab-content active">
        @include('facturas.table')
    </div>

    {{-- Tab Historial / Comparativa --}}
    <div id="content-historial" class="tab-content">
        @include('facturas.tabla_historial')
    </div>

</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
(function () {
    const BASE   = 'px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2 ';
    const NORMAL = 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700';
    const ACTIVE = 'bg-indigo-600 text-white shadow-md';

    // Tabs disponibles
    const TABS = ['facturas', 'historial'];

    window.switchTab = function (tab) {
        // Estilos de botones
        TABS.forEach(t => {
            document.getElementById('tab-' + t).className = BASE + (t === tab ? ACTIVE : NORMAL);
        });

        // Mostrar / ocultar contenido
        TABS.forEach(t => {
            const el = document.getElementById('content-' + t);
            if (t === tab) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        // Si abrimos el tab de comparativa, cargar datos (si aún no se cargaron)
        // y reflotar las gráficas para que recalculen su ancho real.
        if (tab === 'historial') {
            // Dar tiempo al CSS de transición para que el contenedor sea visible
            setTimeout(() => {
                if (typeof window.initComparativa === 'function') {
                    window.initComparativa();
                }
                if (typeof Highcharts !== 'undefined') {
                    Highcharts.charts.forEach(chart => {
                        if (chart) chart.reflow();
                    });
                }
            }, 80);
        }
    };
})();
</script>

{{-- JS específico de cada tab (se apila desde los partials) --}}
@stack('facturas_scripts')

@endpush