@extends('layouts.app')

@section('content')
<style>
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background-color: #475569; }

    .tab-content { visibility: hidden; height: 0; overflow: hidden; opacity: 0; transition: opacity 0.2s ease; }
    .tab-content.active { visibility: visible; height: auto; overflow: visible; opacity: 1; }

    #facturasTable_wrapper .dataTables_processing { background: transparent; color: #6366f1; font-size: 0.8rem; }

    .zona-archivo { transition: border-color 0.15s, background-color 0.15s; }
    .zona-archivo.dragging { box-shadow: 0 0 0 3px rgba(99,102,241,0.25); }
    .zona-archivo-xml.tiene-archivo { border-color: #818cf8; background-color: rgba(238,242,255,0.5); }
    .dark .zona-archivo-xml.tiene-archivo { background-color: rgba(49,46,129,0.15); }
    .zona-archivo-pdf.tiene-archivo { border-color: #34d399; background-color: rgba(236,253,245,0.5); }
    .dark .zona-archivo-pdf.tiene-archivo { background-color: rgba(6,78,59,0.12); }
</style>

<div class="w-full mx-auto max-w-7xl">

    <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 flex items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/40">
                <i class="fas fa-file-invoice text-indigo-500 text-lg"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Gestion de Facturas</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Administra y visualiza el historial.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="btnAbrirFacturaDirecta"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition-colors">
                <i class="fas fa-plus"></i> Nueva Factura
            </button>
            <div class="flex p-1 bg-gray-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <button onclick="switchTab('facturas')" id="tab-facturas"
                    class="px-5 py-2 text-sm font-semibold rounded-lg transition-all flex items-center gap-2 bg-indigo-600 text-white">
                    <i class="fas fa-receipt"></i> Facturas
                </button>
                <button onclick="switchTab('historial')" id="tab-historial"
                    class="px-5 py-2 text-sm font-semibold rounded-lg transition-all flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                    <i class="fas fa-history"></i> Comparativa
                </button>
            </div>
        </div>
    </div>

    <div id="content-facturas" class="tab-content active">
        @include('facturas.table')
    </div>
    <div id="content-historial" class="tab-content">
        @include('facturas.tabla_historial')
    </div>

    {{-- MODAL NUEVA FACTURA --}}
    <div id="modalFacturaDirecta"
        class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/75 backdrop-blur-sm px-4">

        <div class="relative w-full max-w-3xl bg-gray-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden shadow-2xl">

            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center">
                        <i class="fas fa-file-invoice text-indigo-500 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Subir Factura</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sin solicitud asociada</p>
                    </div>
                </div>
                <button type="button" id="btnCerrarFacturaDirecta"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                <div class="flex items-start gap-2.5 px-4 py-3 rounded-lg bg-violet-50 dark:bg-violet-950/30 border border-violet-200 dark:border-violet-800/50">
                    <i class="fas fa-info-circle text-violet-500 text-sm mt-0.5 shrink-0"></i>
                    <p class="text-xs text-violet-700 dark:text-violet-300">
                        Sube primero el <strong>XML</strong> para validar el CFDI y autocompletar los campos.
                        El <strong>PDF</strong> es opcional y sirve como respaldo visual.
                    </p>
                </div>

                {{-- Archivos --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">XML del CFDI</label>
                        <label id="zonaXml"
                            class="zona-archivo zona-archivo-xml flex flex-col items-center justify-center gap-2 h-24 rounded-lg border-2 border-dashed border-violet-300 dark:border-violet-700 bg-violet-50/40 dark:bg-violet-950/10 cursor-pointer hover:border-violet-400 hover:bg-violet-50 dark:hover:border-violet-500 transition-all group">
                            <input type="file" id="inputXml" accept=".xml,text/xml,application/xml" class="hidden">
                            <div id="iconoXml" class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                                <i class="fas fa-file-code text-violet-500 dark:text-violet-400"></i>
                            </div>
                            <div class="text-center">
                                <p id="textoXml" class="text-xs font-semibold text-violet-600 dark:text-violet-400">Subir XML</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500">.xml · CFDI 3.3 / 4.0</p>
                            </div>
                        </label>
                        <div id="spinnerXml" class="hidden mt-1.5 flex items-center gap-1.5 text-xs text-violet-500">
                            <i class="fas fa-spinner fa-spin"></i> Validando...
                        </div>
                        <p id="errorXml" class="hidden mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">PDF <span class="font-normal normal-case tracking-normal text-slate-400">(opcional)</span></label>
                        <label id="zonaPdf"
                            class="zona-archivo zona-archivo-pdf flex flex-col items-center justify-center gap-2 h-24 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 dark:hover:border-emerald-500 transition-all group">
                            <input type="file" id="inputPdf" accept=".pdf,application/pdf" class="hidden">
                            <div id="iconoPdf" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                                <i class="fas fa-file-pdf text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
                            </div>
                            <div class="text-center">
                                <p id="textoPdf" class="text-xs font-semibold text-slate-500 dark:text-slate-400 group-hover:text-emerald-600 transition-colors">Subir PDF</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500">.pdf · representacion impresa</p>
                            </div>
                        </label>
                        <div id="spinnerPdf" class="hidden mt-1.5 flex items-center gap-1.5 text-xs text-emerald-500">
                            <i class="fas fa-spinner fa-spin"></i> Extrayendo datos...
                        </div>
                        <p id="avisoPdfExtranjero" class="hidden mt-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-globe mr-1"></i>PDF procesado como proveedor extranjero
                        </p>
                    </div>

                    <div id="pdfPreviewContainer" class="hidden col-span-2 w-full h-64 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <iframe id="pdfPreviewFrame" class="w-full h-full" src=""></iframe>
                    </div>
                </div>



                {{-- Formulario principal --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Nombre / Descripcion <span class="text-red-500">*</span></label>
                        <input type="text" id="fdNombre" placeholder="Ej: Factura Microsoft Office – Oct 2025"
                            class="w-full h-10 px-4 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-600 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                        <p id="errFdNombre" class="hidden mt-1 text-xs text-red-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> Campo requerido</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Gerencia <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="fdGerencia" class="w-full h-10 pl-4 pr-8 appearance-none text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                                <option value="">Seleccione gerencia</option>
                                @if(isset($gerencias)) @foreach($gerencias as $gerencia) <option value="{{ $gerencia->id }}">{{ $gerencia->nombre }}</option> @endforeach @endif
                            </select>
                            <i class="fas fa-chevron-down text-xs text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <p id="errFdGerencia" class="hidden mt-1 text-xs text-red-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> Seleccione una gerencia</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Insumo <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="fdInsumoSelect" class="w-full h-10 pl-4 pr-8 appearance-none text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                                <option value="">Seleccione insumo</option>
                            </select>
                            <i class="fas fa-chevron-down text-xs text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <p id="errFdInsumo" class="hidden mt-1 text-xs text-red-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> Seleccione un insumo</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Importe</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-mono">$</span>
                            <input type="number" id="fdImporte" step="0.01" min="0" placeholder="0.00"
                                class="w-full h-10 pl-7 pr-4 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Costo <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-mono">$</span>
                            <input type="number" id="fdCosto" step="0.01" min="0" placeholder="0.00"
                                class="w-full h-10 pl-7 pr-4 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                        </div>
                        <p id="errFdCosto" class="hidden mt-1 text-xs text-red-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> Campo requerido</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Mes</label>
                        <div class="relative">
                            <select id="fdMes" class="w-full h-10 pl-4 pr-8 appearance-none text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                                <option value="">Sin mes</option>
                                <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                                <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                                <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                                <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                            </select>
                            <i class="fas fa-chevron-down text-xs text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Año</label>
                        <input type="number" id="fdAnio" min="2000" max="2099" placeholder="{{ date('Y') }}"
                            class="w-full h-10 px-4 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                    </div>
                </div>

                <div class="flex items-start gap-2.5 px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <i class="fas fa-info-circle text-slate-400 text-xs mt-0.5 shrink-0"></i>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Esta factura no estara asociada a ninguna solicitud, el campo quedara en
                        <code class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-[11px] font-mono">N/A</code>.
                    </p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3 shrink-0">
                <button type="button" id="btnCancelarFacturaDirecta"
                    class="h-10 px-5 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btnGuardarFacturaDirecta"
                    class="h-10 inline-flex items-center gap-2 px-5 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save text-xs"></i><span id="textoGuardar">Guardar Factura</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
(function () {
    const BASE   = 'px-5 py-2 text-sm font-semibold rounded-lg transition-all flex items-center gap-2 ';
    const NORMAL = 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200';
    const ACTIVE = 'bg-indigo-600 text-white';
    const TABS   = ['facturas', 'historial'];
    window.switchTab = function (tab) {
        TABS.forEach(t => {
            document.getElementById('tab-' + t).className = BASE + (t === tab ? ACTIVE : NORMAL);
            const el = document.getElementById('content-' + t);
            t === tab ? el.classList.add('active') : el.classList.remove('active');
        });
        if (tab === 'historial') {
            setTimeout(() => {
                if (typeof window.initComparativa === 'function') window.initComparativa();
                if (typeof Highcharts !== 'undefined') Highcharts.charts.forEach(c => { if (c) c.reflow(); });
            }, 80);
        }
    };
})();

(function () {

    const INSUMOS_CATALOGO = @json($insumos ?? []);

    const state = {
        xmlFile: null,
        pdfFile: null,
        parsed: null,
        origenParsed: null,
        conceptosInsumos: {},
        tieneConceptosXml: false,
    };

    const mesesNombres = {1:'Enero',2:'Febrero',3:'Marzo',4:'Abril',5:'Mayo',6:'Junio',7:'Julio',8:'Agosto',9:'Septiembre',10:'Octubre',11:'Noviembre',12:'Diciembre'};
    const $ = id => document.getElementById(id);
    const show = id => $(id) && $(id).classList.remove('hidden');
    const hide = id => $(id) && $(id).classList.add('hidden');

    let openDropdownIdx = null;

    function abrirModal() { resetModal(); $('modalFacturaDirecta').classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
    function cerrarModal() { $('modalFacturaDirecta').classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }

    function resetModal() {
        state.xmlFile = null; state.pdfFile = null; state.parsed = null; state.origenParsed = null;
        ['inputXml','inputPdf'].forEach(id => { if ($(id)) $(id).value = ''; });
        ['fdNombre','fdGerencia','fdImporte','fdCosto','fdMes','fdAnio','fdInsumoSelect'].forEach(id => { if ($(id)) $(id).value = ''; });
        ['spinnerXml','spinnerPdf','errorXml','errFdNombre','errFdCosto','errFdGerencia','errFdInsumo','avisoPdfExtranjero'].forEach(hide);
        // Habilitar el campo año nuevamente
        if ($('fdAnio')) $('fdAnio').disabled = false;
        resetZonaXml(); resetZonaPdf();
        $('pdfPreviewContainer').classList.add('hidden');
        $('pdfPreviewFrame').src = '';
        // Cargar insumos vacíos
        const sel = $('fdInsumoSelect');
        if (sel) sel.innerHTML = '<option value="">Seleccione insumo</option>';
    }

    function resetZonaXml() {
        $('zonaXml').classList.remove('tiene-archivo');
        $('textoXml').textContent = 'Subir XML';
        $('iconoXml').innerHTML = '<i class="fas fa-file-code text-violet-500 dark:text-violet-400"></i>';
    }

    function resetZonaPdf() {
        $('zonaPdf').classList.remove('tiene-archivo');
        $('textoPdf').textContent = 'Subir PDF';
        $('iconoPdf').innerHTML = '<i class="fas fa-file-pdf text-slate-400 group-hover:text-emerald-500 transition-colors"></i>';
    }

    async function cargarInsumosPorGerencia(gerenciaID) {
        if (!gerenciaID) {
            $('fdInsumoSelect').innerHTML = '<option value="">Seleccione insumo</option>';
            return;
        }

        try {
            const res = await fetch('{{ route("facturas.getInsumosPorGerencia") }}?gerenciaID=' + gerenciaID, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const insumos = data.data || [];
            let html = '<option value="">Seleccione insumo</option>';
            insumos.forEach(nombre => {
                html += `<option value="${nombre}">${nombre}</option>`;
            });
            $('fdInsumoSelect').innerHTML = html;
        } catch (e) {
            console.error('Error cargando insumos:', e);
        }
    }

    function poblarCampos(data, origen) {
        const yaHayXml = state.origenParsed === 'xml';
        if (origen === 'pdf' && yaHayXml) return;

        state.parsed = data; state.origenParsed = origen;

        if (data.emisor && !$('fdNombre').value.trim()) $('fdNombre').value = data.emisor;
        if (data.total) {
            const t = parseFloat(data.total);
            if (!isNaN(t)) { $('fdImporte').value = t.toFixed(2); if (!$('fdCosto').value) $('fdCosto').value = t.toFixed(2); }
        }
        if (data.mes) $('fdMes').value = data.mes;
        if (data.anio) $('fdAnio').value = data.anio;
        
        // Deshabilitar el año si viene del XML
        if (origen === 'xml' && data.anio) {
            $('fdAnio').disabled = true;
        }
    }

    async function parsearXml(file) {
        hide('errorXml'); show('spinnerXml');
        const form = new FormData();
        form.append('xml', file);
        form.append('_token', '{{ csrf_token() }}');
        try {
            const res = await fetch('{{ route("facturas.parsearXml") }}', { method: 'POST', body: form, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!res.ok || data.error) throw new Error(data.error ?? 'No se pudo procesar el XML.');
            poblarCampos(data, 'xml');
            $('zonaXml').classList.add('tiene-archivo');
            $('textoXml').textContent = file.name.length > 20 ? file.name.substring(0, 20) + '…' : file.name;
            $('iconoXml').innerHTML = '<i class="fas fa-check-circle text-violet-500"></i>';
        } catch (e) {
            $('errorXml').querySelector('span').textContent = e.message;
            show('errorXml');
            $('zonaXml').classList.remove('tiene-archivo');
        } finally { hide('spinnerXml'); }
    }

    async function parsearPdf(file) {
        show('spinnerPdf');
        const form = new FormData();
        form.append('pdf', file);
        form.append('_token', '{{ csrf_token() }}');
        try {
            const res = await fetch('{{ route("facturas.previsualizarPdf") }}', { method: 'POST', body: form, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (res.ok && !data.error) poblarCampos(data, 'pdf');
        } catch (_) {} finally { hide('spinnerPdf'); }
    }

    function validar() {
        let ok = true;
        ['errFdNombre','errFdCosto','errFdGerencia','errFdInsumo'].forEach(hide);
        if (!$('fdNombre').value.trim()) { show('errFdNombre'); ok = false; }
        if (!$('fdGerencia').value) { show('errFdGerencia'); ok = false; }
        if (!$('fdInsumoSelect').value) { show('errFdInsumo'); ok = false; }
        if (!$('fdCosto').value || parseFloat($('fdCosto').value) < 0) { show('errFdCosto'); ok = false; }
        return ok;
    }

    async function guardar() {
        if (!validar()) return;
        const btn = $('btnGuardarFacturaDirecta');
        btn.disabled = true;
        $('textoGuardar').textContent = 'Guardando...';

        const form = new FormData();
        form.append('_token', '{{ csrf_token() }}');
        form.append('Nombre', $('fdNombre').value.trim());
        form.append('GerenciaID', $('fdGerencia').value);
        form.append('InsumoNombre', $('fdInsumoSelect').value);
        form.append('Importe', $('fdImporte').value || '');
        form.append('Costo', $('fdCosto').value);
        form.append('Mes', $('fdMes').value || '');
        form.append('Anio', $('fdAnio').value || '');

        if (state.parsed) {
            form.append('UUID', state.parsed.uuid ?? '');
            form.append('Emisor', state.parsed.emisor ?? '');
        }
        if (state.xmlFile) form.append('archivo_xml', state.xmlFile);
        if (state.pdfFile) form.append('archivo_pdf', state.pdfFile);

        try {
            const res = await fetch('{{ route("facturas.storeDirecta") }}', { method: 'POST', body: form, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message ?? 'No se pudo guardar la factura.');
            cerrarModal();
            if (window.Swal) Swal.fire({ icon: 'success', title: 'Factura guardada', text: 'La factura fue registrada correctamente.', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end', background: '#f9fafb', color: '#1e293b' });
            if (window.jQuery && window.jQuery('#facturasTable').length) window.jQuery('#facturasTable').DataTable().ajax.reload(null, false);
        } catch (e) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: e.message, background: '#f9fafb', color: '#1e293b', confirmButtonColor: '#e11d48' });
        } finally {
            btn.disabled = false;
            $('textoGuardar').textContent = 'Guardar Factura';
        }
    }

    function setupDragDrop(zonaId, onFile) {
        const zona = $(zonaId); if (!zona) return;
        ['dragenter','dragover'].forEach(ev => zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.add('dragging'); }));
        ['dragleave','dragend'].forEach(ev => zona.addEventListener(ev, () => zona.classList.remove('dragging')));
        zona.addEventListener('drop', e => { e.preventDefault(); zona.classList.remove('dragging'); const f = e.dataTransfer?.files?.[0]; if (f) onFile(f); });
    }

    async function handleXmlFile(file) { if (!file) return; state.xmlFile = file; await parsearXml(file); }
    async function handlePdfFile(file) {
        if (!file) return;
        state.pdfFile = file;
        $('textoPdf').textContent = file.name.length > 20 ? file.name.substring(0, 20) + '…' : file.name;
        $('iconoPdf').innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i>';
        $('zonaPdf').classList.add('tiene-archivo');
        $('pdfPreviewFrame').src = URL.createObjectURL(file);
        $('pdfPreviewContainer').classList.remove('hidden');
        await parsearPdf(file);
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('btnAbrirFacturaDirecta').addEventListener('click', abrirModal);
        $('btnCerrarFacturaDirecta').addEventListener('click', cerrarModal);
        $('btnCancelarFacturaDirecta').addEventListener('click', cerrarModal);
        $('btnGuardarFacturaDirecta').addEventListener('click', guardar);
        $('modalFacturaDirecta').addEventListener('click', e => { if (e.target === $('modalFacturaDirecta')) cerrarModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !$('modalFacturaDirecta').classList.contains('hidden')) cerrarModal(); });
        
        // Cargar insumos cuando cambia la gerencia
        $('fdGerencia').addEventListener('change', async function () {
            await cargarInsumosPorGerencia(this.value);
        });

        $('inputXml').addEventListener('change', async function () { await handleXmlFile(this.files[0]); });
        setupDragDrop('zonaXml', async file => { $('inputXml').value = ''; await handleXmlFile(file); });
        $('inputPdf').addEventListener('change', async function () { await handlePdfFile(this.files[0]); });
        setupDragDrop('zonaPdf', async file => { await handlePdfFile(file); });
    });

})();
</script>

@stack('facturas_scripts')
@endpush