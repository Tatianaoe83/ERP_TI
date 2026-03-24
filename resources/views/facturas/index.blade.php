@extends('layouts.app')

@section('content')
<style>
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background-color: #475569; }

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

            {{-- Botón Nueva Factura --}}
            <button type="button" id="btnAbrirFacturaDirecta"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-lg shadow-emerald-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0">
                <i class="fas fa-plus"></i> Nueva Factura
            </button>

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

    {{-- ── MODAL NUEVA FACTURA DIRECTA ─────────────────────────────────────── --}}
    <div id="modalFacturaDirecta"
        class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm px-4">

        <div class="relative w-full max-w-2xl bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden">

            {{-- HEADER --}}
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4 bg-gray-50 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                        <i class="fas fa-file-invoice text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 leading-tight">Subir Factura Directa</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sin solicitud asociada — se registrará como N/A</p>
                    </div>
                </div>
                <button type="button" id="btnCerrarFacturaDirecta"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 bg-gray-50 dark:bg-slate-900">

                {{-- ZONA DE ARCHIVOS --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- XML --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                            Factura XML <span class="text-red-500">*</span>
                        </label>
                        <label id="zonaXml"
                            class="flex flex-col items-center justify-center gap-2 h-28 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50/40 dark:hover:bg-indigo-900/10 transition-all duration-200 group">
                            <input type="file" id="inputXml" accept=".xml,text/xml,application/xml" class="hidden">
                            <div id="iconoXml" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/30 transition-colors">
                                <i class="fas fa-file-code text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 transition-colors text-lg"></i>
                            </div>
                            <div class="text-center">
                                <p id="textoXml" class="text-xs font-semibold text-slate-600 dark:text-slate-300">Subir XML</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">.xml · CFDI 3.3 / 4.0</p>
                            </div>
                        </label>
                        <div id="spinnerXml" class="hidden mt-2 flex items-center gap-2 text-xs text-indigo-500 font-medium">
                            <i class="fas fa-spinner fa-spin"></i> Procesando XML...
                        </div>
                        <p id="errorXml" class="hidden mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </p>
                    </div>

                    {{-- PDF --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                            Factura PDF <span class="text-slate-400 font-normal">(opcional)</span>
                        </label>
                        <label id="zonaPdf"
                            class="flex flex-col items-center justify-center gap-2 h-28 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 cursor-pointer hover:border-emerald-400 dark:hover:border-emerald-500 hover:bg-emerald-50/40 dark:hover:bg-emerald-900/10 transition-all duration-200 group">
                            <input type="file" id="inputPdf" accept=".pdf,application/pdf" class="hidden">
                            <div id="iconoPdf" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/30 transition-colors">
                                <i class="fas fa-file-pdf text-slate-400 dark:text-slate-500 group-hover:text-emerald-500 transition-colors text-lg"></i>
                            </div>
                            <div class="text-center">
                                <p id="textoPdf" class="text-xs font-semibold text-slate-600 dark:text-slate-300">Subir PDF</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">.pdf · representación impresa</p>
                            </div>
                        </label>
                    </div>
                        <div id="pdfPreviewContainer" class="hidden sm:col-span-2 mt-4 w-full h-80 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <iframe id="pdfPreviewFrame" class="w-full h-full" src=""></iframe>
                    </div>
                </div>

                {{-- DATOS PARSEADOS DEL XML --}}
                <div id="seccionParsed" class="hidden rounded-xl border border-indigo-200 dark:border-indigo-700/40 bg-indigo-50/60 dark:bg-indigo-950/20 overflow-hidden">
                    <div class="px-4 py-3 bg-indigo-100/70 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-700/40 flex items-center gap-2">
                        <i class="fas fa-check-circle text-indigo-500 dark:text-indigo-400 text-sm"></i>
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Datos detectados del XML</span>
                    </div>
                    <div class="px-4 py-4 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Emisor</p>
                            <p id="parsedEmisor" class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">UUID</p>
                            <p id="parsedUuid" class="text-xs font-mono text-slate-600 dark:text-slate-400 truncate">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Total</p>
                            <p id="parsedTotal" class="text-sm font-bold text-emerald-700 dark:text-emerald-400">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Mes</p>
                            <p id="parsedMes" class="text-sm text-slate-700 dark:text-slate-300">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Año</p>
                            <p id="parsedAnio" class="text-sm text-slate-700 dark:text-slate-300">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Conceptos</p>
                            <p id="parsedConceptos" class="text-sm text-slate-700 dark:text-slate-300">—</p>
                        </div>
                    </div>
                </div>

                {{-- CAMPOS MANUALES --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Nombre / Descripción <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="fdNombre" placeholder="Ej: Factura Microsoft Office – Oct 2025"
                            class="w-full h-11 px-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                        <p id="errFdNombre" class="hidden mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Campo requerido</p>
                    </div>

                    {{-- NUEVO: Select Gerencia --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Gerencia <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="fdGerencia"
                                class="w-full h-11 pl-4 pr-10 appearance-none text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                                <option value="">— Seleccione Gerencia —</option>
                                {{-- Asegúrate de pasar la variable $gerencias desde tu controlador --}}
                                @if(isset($gerencias))
                                    @foreach($gerencias as $gerencia)
                                        <option value="{{ $gerencia->id }}">{{ $gerencia->nombre ?? $gerencia->NombreGerencia }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <p id="errFdGerencia" class="hidden mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Seleccione una gerencia</p>
                    </div>

                    {{-- NUEVO: Select Insumos (Cortes) --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Insumo (Cortes) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="fdInsumoSelect"
                                class="w-full h-11 pl-4 pr-10 appearance-none text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                                <option value="">— Seleccione Insumo —</option>
                                {{-- Asegúrate de pasar la variable $insumos desde tu controlador --}}
                                @if(isset($insumos))
                                    @foreach($insumos as $insumo)
                                        <option value="{{ $insumo->id }}">{{ $insumo->nombre ?? $insumo->NombreInsumo }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <p id="errFdInsumo" class="hidden mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Seleccione un insumo</p>
                    </div>

                    {{-- El campo original de texto libre Insumo lo ocultamos o lo dejamos como 'Insumo Alternativo' --}}
                    <div class="hidden">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Insumo Manual</label>
                        <input type="text" id="fdInsumoNombre" placeholder="Nombre del insumo (opcional)"
                            class="w-full h-11 px-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Importe</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-mono">$</span>
                            <input type="number" id="fdImporte" step="0.01" min="0" placeholder="0.00"
                                class="w-full h-11 pl-7 pr-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Costo <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-mono">$</span>
                            <input type="number" id="fdCosto" step="0.01" min="0" placeholder="0.00"
                                class="w-full h-11 pl-7 pr-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                        </div>
                        <p id="errFdCosto" class="hidden mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Campo requerido</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Mes</label>
                        <div class="relative">
                            <select id="fdMes"
                                class="w-full h-11 pl-4 pr-10 appearance-none text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                                <option value="">— Sin mes —</option>
                                <option value="1">Enero</option><option value="2">Febrero</option>
                                <option value="3">Marzo</option><option value="4">Abril</option>
                                <option value="5">Mayo</option><option value="6">Junio</option>
                                <option value="7">Julio</option><option value="8">Agosto</option>
                                <option value="9">Septiembre</option><option value="10">Octubre</option>
                                <option value="11">Noviembre</option><option value="12">Diciembre</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Año</label>
                        <input type="number" id="fdAnio" min="2000" max="2099" placeholder="{{ date('Y') }}"
                            class="w-full h-11 px-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <i class="fas fa-info-circle text-slate-400 text-sm"></i>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Esta factura <span class="font-semibold text-slate-700 dark:text-slate-300">no estará asociada a ninguna solicitud</span> — el campo SolicitudID quedará en <code class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[11px] font-mono">N/A</code>.
                    </p>
                </div>

            </div>{{-- /body --}}

            {{-- FOOTER --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 flex items-center justify-end gap-3">
                <button type="button" id="btnCancelarFacturaDirecta"
                    class="px-4 py-2.5 text-sm font-medium rounded-xl border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btnGuardarFacturaDirecta"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0">
                    <i class="fas fa-save"></i>
                    <span id="textoGuardar">Guardar Factura</span>
                </button>
            </div>

        </div>
    </div>
    {{-- /MODAL --}}

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
    const TABS   = ['facturas', 'historial'];

    window.switchTab = function (tab) {
        TABS.forEach(t => {
            document.getElementById('tab-' + t).className = BASE + (t === tab ? ACTIVE : NORMAL);
        });
        TABS.forEach(t => {
            const el = document.getElementById('content-' + t);
            if (t === tab) { el.classList.add('active'); }
            else           { el.classList.remove('active'); }
        });
        if (tab === 'historial') {
            setTimeout(() => {
                if (typeof window.initComparativa === 'function') window.initComparativa();
                if (typeof Highcharts !== 'undefined') {
                    Highcharts.charts.forEach(chart => { if (chart) chart.reflow(); });
                }
            }, 80);
        }
    };
})();

// ── Modal Factura Directa ─────────────────────────────────────────────────────
(function () {
    const state = { xmlFile: null, pdfFile: null, parsed: null };

    const mesesNombres = {
        1:'Enero',2:'Febrero',3:'Marzo',4:'Abril',5:'Mayo',6:'Junio',
        7:'Julio',8:'Agosto',9:'Septiembre',10:'Octubre',11:'Noviembre',12:'Diciembre'
    };

    const $    = id => document.getElementById(id);
    const show = id => $(id).classList.remove('hidden');
    const hide = id => $(id).classList.add('hidden');

    function abrirModal() {
        resetModal();
        $('modalFacturaDirecta').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal() {
        $('modalFacturaDirecta').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function resetModal() {
        state.xmlFile = null;
        state.pdfFile = null;
        state.parsed  = null;
        $('inputXml').value       = '';
        $('inputPdf').value       = '';
        $('fdNombre').value       = '';
        // Reseteo de nuevos selects y el input oculto
        $('fdGerencia').value     = '';
        $('fdInsumoSelect').value = '';
        $('fdInsumoNombre').value = ''; 
        $('fdImporte').value      = '';
        $('fdCosto').value        = '';
        $('fdMes').value          = '';
        $('fdAnio').value         = '';
        hide('seccionParsed'); hide('spinnerXml'); hide('errorXml');
        hide('errFdNombre');   hide('errFdCosto');
        hide('errFdGerencia'); hide('errFdInsumo'); // Ocultar mensajes de error nuevos
        resetZonaXml(); resetZonaPdf();
        $('pdfPreviewContainer').classList.add('hidden'); // Ocultar previsualización del PDF
        $('pdfPreviewFrame').src = '';
    }

    function resetZonaXml() {
        $('textoXml').textContent = 'Subir XML';
        $('iconoXml').innerHTML   = '<i class="fas fa-file-code text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 transition-colors text-lg"></i>';
        $('zonaXml').classList.remove('border-indigo-400','bg-indigo-50/40','border-red-400','bg-red-50/40');
        $('zonaXml').classList.add('border-slate-300');
    }

    function resetZonaPdf() {
        $('textoPdf').textContent = 'Subir PDF';
        $('iconoPdf').innerHTML   = '<i class="fas fa-file-pdf text-slate-400 dark:text-slate-500 group-hover:text-emerald-500 transition-colors text-lg"></i>';
        $('zonaPdf').classList.remove('border-emerald-400','bg-emerald-50/40');
        $('zonaPdf').classList.add('border-slate-300');
    }

    async function parsearXml(file) {
        hide('errorXml');
        show('spinnerXml');
        const formData = new FormData();
        formData.append('xml', file);
        formData.append('_token', '{{ csrf_token() }}');
        try {
            const res  = await fetch('{{ route("facturas.parsear-xml") }}', {
                method: 'POST', body: formData,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (!res.ok || data.error) throw new Error(data.error ?? 'Error al procesar el XML.');
            state.parsed = data;
            poblarDesdeParsed(data);
        } catch (e) {
            $('errorXml').querySelector('span').textContent = e.message;
            show('errorXml');
            $('zonaXml').classList.add('border-red-400','bg-red-50/40');
            $('zonaXml').classList.remove('border-indigo-400','bg-indigo-50/40');
        } finally {
            hide('spinnerXml');
        }
    }

    function poblarDesdeParsed(data) {
        if (data.emisor && !$('fdNombre').value) $('fdNombre').value = data.emisor;
        if (data.total) {
            $('fdImporte').value = parseFloat(data.total).toFixed(2);
            if (!$('fdCosto').value) $('fdCosto').value = parseFloat(data.total).toFixed(2);
        }
        if (data.mes)  $('fdMes').value  = data.mes;
        if (data.anio) $('fdAnio').value = data.anio;
        // Si quieres que el insumo parseado se guarde en el campo oculto "InsumoNombre"
        if (data.conceptos && data.conceptos.length > 0 && !$('fdInsumoNombre').value) {
            $('fdInsumoNombre').value = data.conceptos[0].nombre ?? '';
        }
        $('parsedEmisor').textContent    = data.emisor    || '—';
        $('parsedUuid').textContent      = data.uuid      ? data.uuid.toUpperCase() : '—';
        $('parsedTotal').textContent     = data.total     ? '$' + parseFloat(data.total).toLocaleString('es-MX', {minimumFractionDigits:2}) + ' ' + (data.moneda ?? 'MXN') : '—';
        $('parsedMes').textContent       = data.mes       ? (mesesNombres[data.mes] ?? data.mes) : '—';
        $('parsedAnio').textContent      = data.anio      || '—';
        $('parsedConceptos').textContent = data.conceptos ? data.conceptos.length + ' concepto(s)' : '—';
        show('seccionParsed');
    }

    function validar() {
        let ok = true;
        hide('errFdNombre'); hide('errFdCosto');
        hide('errFdGerencia'); hide('errFdInsumo');

        if (!$('fdNombre').value.trim()) { show('errFdNombre'); ok = false; }
        if (!$('fdGerencia').value)      { show('errFdGerencia'); ok = false; }
        if (!$('fdInsumoSelect').value)  { show('errFdInsumo'); ok = false; }
        if (!$('fdCosto').value || parseFloat($('fdCosto').value) < 0) { show('errFdCosto'); ok = false; }
        
        return ok;
    }

    async function guardar() {
        if (!validar()) return;
        const btn = $('btnGuardarFacturaDirecta');
        btn.disabled = true;
        $('textoGuardar').textContent = 'Guardando...';

        const form = new FormData();
        form.append('_token',       '{{ csrf_token() }}');
        form.append('Nombre',       $('fdNombre').value.trim());
        form.append('GerenciaID',   $('fdGerencia').value);       
        
        form.append('InsumoNombre', $('fdInsumoSelect').value); 
        
        form.append('Importe',      $('fdImporte').value  || '');
        form.append('Costo',        $('fdCosto').value);
        form.append('Mes',          $('fdMes').value      || '');
        form.append('Anio',         $('fdAnio').value     || '');
        
        if (state.parsed) {
            form.append('UUID',   state.parsed.uuid   ?? '');
            form.append('Emisor', state.parsed.emisor ?? '');
        }
        if (state.xmlFile) form.append('archivo_xml', state.xmlFile);
        if (state.pdfFile) form.append('archivo_pdf', state.pdfFile);

        try {
            const res  = await fetch('{{ route("facturas.storeDirecta") }}', {
                method: 'POST', body: form,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message ?? 'Error al guardar la factura.');

            cerrarModal();

            if (window.Swal) {
                Swal.fire({ icon:'success', title:'¡Factura guardada!',
                    text:'La factura fue registrada correctamente.',
                    timer:3000, showConfirmButton:false, toast:true, position:'top-end' });
            }
            
            if (window.jQuery && window.jQuery('#facturasTable').length) {
                window.jQuery('#facturasTable').DataTable().ajax.reload(null, false);
            }
            
        } catch (e) {
            if (window.Swal) { Swal.fire({ icon:'error', title:'Error', text:e.message }); }
            else { alert('Error: ' + e.message); }
        } finally {
            btn.disabled = false;
            $('textoGuardar').textContent = 'Guardar Factura';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('btnAbrirFacturaDirecta').addEventListener('click', abrirModal);
        $('btnCerrarFacturaDirecta').addEventListener('click', cerrarModal);
        $('btnCancelarFacturaDirecta').addEventListener('click', cerrarModal);
        $('btnGuardarFacturaDirecta').addEventListener('click', guardar);

        $('modalFacturaDirecta').addEventListener('click', function (e) {
            if (e.target === this) cerrarModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !$('modalFacturaDirecta').classList.contains('hidden')) cerrarModal();
        });

        $('inputXml').addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;
            state.xmlFile = file;
            $('textoXml').textContent = file.name.length > 22 ? file.name.substring(0,22)+'…' : file.name;
            $('iconoXml').innerHTML   = '<i class="fas fa-check-circle text-indigo-500 text-lg"></i>';
            $('zonaXml').classList.remove('border-slate-300','border-red-400','bg-red-50/40');
            $('zonaXml').classList.add('border-indigo-400','bg-indigo-50/40');
            hide('errorXml');
            await parsearXml(file);
        });

        $('inputPdf').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            
            state.pdfFile = file;
            $('textoPdf').textContent = file.name.length > 22 ? file.name.substring(0,22)+'…' : file.name;
            $('iconoPdf').innerHTML   = '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>';
            $('zonaPdf').classList.remove('border-slate-300');
            $('zonaPdf').classList.add('border-emerald-400','bg-emerald-50/40');

            // -- Magia para previsualizar el PDF --
            const fileURL = URL.createObjectURL(file);
            $('pdfPreviewFrame').src = fileURL;
            $('pdfPreviewContainer').classList.remove('hidden');
        });
    });
})();
</script>

{{-- JS específico de cada tab (se apila desde los partials) --}}
@stack('facturas_scripts')

@endpush