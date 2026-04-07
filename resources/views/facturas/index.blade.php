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

        <div class="relative w-full max-w-xl bg-gray-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden shadow-2xl">

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

                {{-- Datos detectados del XML/PDF --}}
                <div id="seccionParsed" class="hidden rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div id="parsedHeader" class="px-4 py-2.5 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2 bg-slate-50 dark:bg-slate-800">
                        <i id="parsedHeaderIcon" class="fas fa-check-circle text-indigo-500 text-xs"></i>
                        <span id="parsedHeaderText" class="text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Datos detectados</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-x-4 gap-y-2 bg-slate-50/50 dark:bg-slate-800/30">
                        <div><p class="text-[10px] font-medium text-slate-400 mb-0.5">Emisor</p><p id="parsedEmisor" class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">—</p></div>
                        <div><p class="text-[10px] font-medium text-slate-400 mb-0.5">UUID</p><p id="parsedUuid" class="text-xs font-mono text-indigo-600 dark:text-indigo-400 truncate">—</p></div>
                        <div><p class="text-[10px] font-medium text-slate-400 mb-0.5">Total</p><p id="parsedTotal" class="text-sm font-bold text-emerald-600 dark:text-emerald-400">—</p></div>
                        <div><p class="text-[10px] font-medium text-slate-400 mb-0.5">Mes</p><p id="parsedMes" class="text-sm text-slate-600 dark:text-slate-300">—</p></div>
                        <div><p class="text-[10px] font-medium text-slate-400 mb-0.5">Año</p><p id="parsedAnio" class="text-sm text-slate-600 dark:text-slate-300">—</p></div>
                        <div><p class="text-[10px] font-medium text-slate-400 mb-0.5">Conceptos</p><p id="parsedConceptos" class="text-sm text-slate-600 dark:text-slate-300">—</p></div>
                    </div>
                </div>

                {{-- Conceptos del XML con selector de insumo --}}
                <div id="seccionConceptos" class="hidden rounded-lg border border-violet-200 dark:border-violet-800/40 overflow-hidden">
                    <div class="px-4 py-3 bg-violet-50 dark:bg-violet-900/20 border-b border-violet-200 dark:border-violet-800/40 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-list text-violet-500 text-sm"></i>
                            <span class="text-xs font-bold text-violet-700 dark:text-violet-300 uppercase tracking-wider">Conceptos detectados</span>
                            <span id="contadorConceptos" class="text-xs text-violet-500 font-medium"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="contadorSinInsumo" class="hidden text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200"><span></span> sin insumo</span>
                            <span id="contadorConInsumo" class="hidden text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-200"><span></span> asignados</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto bg-gray-50 dark:bg-slate-900">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="text-left px-4 py-2.5 font-medium">Descripcion</th>
                                    <th class="text-right px-3 py-2.5 font-medium w-12">Cant.</th>
                                    <th class="text-right px-3 py-2.5 font-medium w-24">Unitario</th>
                                    <th class="text-right px-3 py-2.5 font-medium w-24">Importe</th>
                                    <th class="text-left px-3 py-2.5 font-medium w-48">Insumo</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyConceptos" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                        </table>
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

                    {{--
                        El insumo se muestra como select cuando NO hay XML/conceptos cargados.
                        Cuando el XML carga conceptos, este campo se convierte en informativo
                        mostrando el insumo detectado automaticamente.
                        El selector se ocultara con JS cuando haya conceptos en el XML.
                    --}}
                    <div id="wrapperInsumoSelect">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Insumo <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="fdInsumoSelect" class="w-full h-10 pl-4 pr-8 appearance-none text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition-all">
                                <option value="">Seleccione insumo</option>
                                @if(isset($insumos)) @foreach($insumos as $insumo) <option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option> @endforeach @endif
                            </select>
                            <i class="fas fa-chevron-down text-xs text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <p id="errFdInsumo" class="hidden mt-1 text-xs text-red-500 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> Seleccione un insumo</p>
                    </div>

                    {{-- Insumo detectado del XML (se muestra en lugar del select cuando hay conceptos) --}}
                    <div id="wrapperInsumoDetectado" class="hidden">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Insumo</label>
                        <div class="flex items-center gap-2 h-10 px-4 rounded-lg border border-emerald-200 dark:border-emerald-700/50 bg-emerald-50 dark:bg-emerald-900/20">
                            <i class="fas fa-magic text-emerald-500 text-xs shrink-0"></i>
                            <span id="textoInsumoDetectado" class="text-sm text-emerald-800 dark:text-emerald-200 font-medium truncate">Detectado del XML</span>
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">Asignado desde los conceptos del XML. Puedes cambiarlo arriba.</p>
                        <input type="hidden" id="fdInsumoSelect">
                    </div>

                    <div class="hidden"><input type="text" id="fdInsumoNombre"></div>

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
        state.conceptosInsumos = {}; state.tieneConceptosXml = false;
        openDropdownIdx = null;
        ['inputXml','inputPdf'].forEach(id => { if ($(id)) $(id).value = ''; });
        ['fdNombre','fdGerencia','fdInsumoNombre','fdImporte','fdCosto','fdMes','fdAnio'].forEach(id => { if ($(id)) $(id).value = ''; });

        // Reset selector de insumo
        const sel = $('fdInsumoSelect');
        if (sel) sel.value = '';
        mostrarSelectorInsumo();

        ['seccionParsed','seccionConceptos','spinnerXml','spinnerPdf','errorXml','errFdNombre','errFdCosto','errFdGerencia','errFdInsumo','avisoPdfExtranjero'].forEach(hide);
        resetZonaXml(); resetZonaPdf();
        $('pdfPreviewContainer').classList.add('hidden');
        $('pdfPreviewFrame').src = '';
        $('tbodyConceptos').innerHTML = '';
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

    function mostrarSelectorInsumo() {
        show('wrapperInsumoSelect');
        hide('wrapperInsumoDetectado');
    }

    function mostrarInsumoDetectado(nombre) {
        hide('wrapperInsumoSelect');
        show('wrapperInsumoDetectado');
        $('textoInsumoDetectado').textContent = nombre || 'Detectado del XML';
        hide('errFdInsumo');
    }

    function autoMatchInsumo(conceptos) {
        if (!conceptos || !conceptos.length) return;
        const matchNombre = conceptos[0].insumoNombre;
        if (!matchNombre) return;

        // Intentar seleccionar en el select normal
        const sel = $('fdInsumoSelect');
        const option = Array.from(sel.options).find(o => o.text.trim().toLowerCase() === matchNombre.trim().toLowerCase());
        if (option) {
            sel.value = option.value;
            mostrarInsumoDetectado(option.text.trim());
        }
    }

    function poblarCampos(data, origen) {
        const yaHayXml = state.origenParsed === 'xml';
        if (origen === 'pdf' && yaHayXml) return;

        state.parsed = data; state.origenParsed = origen; state.conceptosInsumos = {};

        if (data.emisor && !$('fdNombre').value.trim()) $('fdNombre').value = data.emisor;
        if (data.total) {
            const t = parseFloat(data.total);
            if (!isNaN(t)) { $('fdImporte').value = t.toFixed(2); if (!$('fdCosto').value) $('fdCosto').value = t.toFixed(2); }
        }
        if (data.mes) $('fdMes').value = data.mes;
        if (data.anio) $('fdAnio').value = data.anio;

        if (data.conceptos && data.conceptos.length > 0) {
            $('fdInsumoNombre').value = data.conceptos[0].nombre ?? '';
            state.tieneConceptosXml = true;
            autoMatchInsumo(data.conceptos);
            data.conceptos.forEach((c, i) => { if (c.insumoNombre) state.conceptosInsumos[i] = c.insumoNombre; });
        } else {
            state.tieneConceptosXml = false;
            mostrarSelectorInsumo();
        }

        $('parsedEmisor').textContent = data.emisor || '—';
        $('parsedUuid').textContent = data.uuid ? data.uuid.toUpperCase() : (origen === 'pdf' ? '(PDF extranjero)' : '—');
        $('parsedTotal').textContent = data.total ? '$' + parseFloat(data.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) + ' ' + (data.moneda ?? 'MXN') : '—';
        $('parsedMes').textContent = data.mes ? (mesesNombres[data.mes] ?? String(data.mes)) : '—';
        $('parsedAnio').textContent = data.anio ? String(data.anio) : '—';
        $('parsedConceptos').textContent = data.conceptos ? data.conceptos.length + ' concepto(s)' : '—';

        $('parsedHeaderText').textContent = origen === 'xml' ? 'Datos detectados del XML' : 'Datos extraidos del PDF';
        show('seccionParsed');

        renderConceptos(data.conceptos ?? []);
        if (origen === 'pdf' && !yaHayXml) show('avisoPdfExtranjero');
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.dd-insumo-modal').forEach(d => d.classList.add('hidden'));
        openDropdownIdx = null;
    }

    function updateContadores() {
        const conceptos = state.parsed?.conceptos ?? [];
        if (!conceptos.length) return;
        let conMatch = 0, sinMatch = 0;
        conceptos.forEach((c, i) => { (state.conceptosInsumos[i] || c.insumoNombre) ? conMatch++ : sinMatch++; });
        const elSin = $('contadorSinInsumo'); const elCon = $('contadorConInsumo');
        if (sinMatch > 0) { elSin.querySelector('span').textContent = sinMatch; elSin.classList.remove('hidden'); } else { elSin.classList.add('hidden'); }
        if (conMatch > 0) { elCon.querySelector('span').textContent = conMatch; elCon.classList.remove('hidden'); } else { elCon.classList.add('hidden'); }
    }

    function setConceptoInsumo(idx, nombre) {
        state.conceptosInsumos[idx] = nombre;
        // Si es el primer concepto, actualizar el insumo principal del formulario
        if (idx === 0 && nombre) {
            const sel = $('fdInsumoSelect');
            const option = Array.from(sel.options).find(o => o.text.trim().toLowerCase() === nombre.trim().toLowerCase());
            if (option) { sel.value = option.value; mostrarInsumoDetectado(option.text.trim()); }
        }
        closeAllDropdowns();
        renderConceptos(state.parsed?.conceptos ?? []);
    }

    function removeConceptoInsumo(idx) {
        delete state.conceptosInsumos[idx];
        if (state.parsed?.conceptos?.[idx]) state.parsed.conceptos[idx].insumoNombre = null;
        if (idx === 0) mostrarSelectorInsumo();
        closeAllDropdowns();
        renderConceptos(state.parsed?.conceptos ?? []);
    }

    function renderConceptos(conceptos) {
        const tbody = $('tbodyConceptos');
        tbody.innerHTML = '';
        if (!conceptos.length) { hide('seccionConceptos'); return; }

        $('contadorConceptos').textContent = '(' + conceptos.length + ')';

        conceptos.forEach((c, idx) => {
            const tr = document.createElement('tr');
            const tieneMatch = !!(state.conceptosInsumos[idx] || c.insumoNombre);
            const matchLabel = state.conceptosInsumos[idx] || c.insumoNombre || '';
            const costo = parseFloat(c.costo ?? 0);
            const importe = parseFloat(c.importe ?? 0);
            const cant = c.cantidad ?? 1;
            const nombre = (c.nombre ?? '—').length > 50 ? (c.nombre ?? '').substring(0, 50) + '…' : (c.nombre ?? '—');

            tr.className = tieneMatch
                ? 'bg-gray-50 dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                : 'bg-amber-50/30 dark:bg-amber-950/10 hover:bg-amber-50/50';

            let selectorHtml;
            if (tieneMatch) {
                selectorHtml = `
                    <div class="relative">
                        <button type="button" onclick="window.__toggleDD(${idx})"
                            class="w-full flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-left bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-colors dark:bg-emerald-900/20 dark:border-emerald-700/50">
                            <i class="fas fa-check-circle text-emerald-500 text-[9px] shrink-0"></i>
                            <span class="text-[11px] font-medium text-emerald-800 dark:text-emerald-200 truncate">${matchLabel.length > 20 ? matchLabel.substring(0,20)+'…' : matchLabel}</span>
                            <i class="fas fa-pen text-[8px] text-emerald-400 ml-auto shrink-0"></i>
                        </button>
                        <div class="dd-insumo-modal hidden absolute top-full left-0 z-[99999] mt-1 w-52 rounded-lg border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden" id="dd-m-${idx}"></div>
                    </div>`;
            } else {
                selectorHtml = `
                    <div class="relative">
                        <button type="button" onclick="window.__toggleDD(${idx})"
                            class="w-full flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-left bg-amber-50 border-2 border-dashed border-amber-300 hover:bg-amber-100 transition-colors dark:bg-amber-950/20 dark:border-amber-700/50">
                            <i class="fas fa-plus-circle text-amber-500 text-[9px] shrink-0"></i>
                            <span class="text-[11px] font-medium text-amber-700 dark:text-amber-300">Asignar insumo</span>
                        </button>
                        <div class="dd-insumo-modal hidden absolute top-full left-0 z-[99999] mt-1 w-52 rounded-lg border border-slbg-gray-50ate-200 dark:border-slate-700  dark:bg-slate-900 shadow-xl overflow-hidden" id="dd-m-${idx}"></div>
                    </div>`;
            }

            tr.innerHTML = `
                <td class="px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded flex items-center justify-center shrink-0 ${tieneMatch ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-amber-100 dark:bg-amber-900/30'}">
                            <i class="fas ${tieneMatch ? 'fa-check text-emerald-600' : 'fa-exclamation text-amber-600'}" style="font-size:7px"></i>
                        </div>
                        <span class="text-slate-800 dark:text-slate-200">${nombre}</span>
                    </div>
                </td>
                <td class="px-3 py-2.5 text-right text-slate-600 dark:text-slate-300">${cant}</td>
                <td class="px-3 py-2.5 text-right text-slate-600 dark:text-slate-300 font-mono">${costo > 0 ? '$' + costo.toLocaleString('es-MX', {minimumFractionDigits:2}) : '—'}</td>
                <td class="px-3 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200 font-mono">${importe > 0 ? '$' + importe.toLocaleString('es-MX', {minimumFractionDigits:2}) : '—'}</td>
                <td class="px-3 py-2.5">${selectorHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        show('seccionConceptos');
        updateContadores();
    }

    function buildDropdown(idx) {
        const dd = document.getElementById('dd-m-' + idx);
        if (!dd) return;
        const tieneMatch = !!(state.conceptosInsumos[idx]);
        let html = `<div style="padding:6px;border-bottom:1px solid #f1f5f9">
            <div style="position:relative">
                <input type="text" id="dds-${idx}" placeholder="Buscar..."
                    style="width:100%;height:30px;padding:0 8px 0 26px;font-size:11px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;outline:none"
                    autocomplete="off">
                <i class="fas fa-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:9px;color:#94a3b8;pointer-events:none"></i>
            </div>
        </div>`;

        if (tieneMatch) {
            html += `<button type="button" onclick="window.__removeDD(${idx})"
                style="display:block;width:100%;padding:8px 12px;text-align:left;font-size:11px;color:#ef4444;background:none;border:none;cursor:pointer;border-bottom:1px solid #f1f5f9">
                <i class="fas fa-times-circle" style="margin-right:4px"></i> Quitar insumo</button>`;
        }

        html += `<div id="ddl-${idx}" style="max-height:160px;overflow-y:auto">`;
        INSUMOS_CATALOGO.slice(0, 15).forEach(ins => {
            const n = (ins.nombre || '').replace(/'/g, "\\'");
            html += `<button type="button" onclick="window.__setDD(${idx}, '${n}')"
                style="display:block;width:100%;padding:7px 12px;text-align:left;font-size:11px;color:#334155;background:none;border:none;cursor:pointer;border-bottom:1px solid #f8fafc"
                onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'"
                >${ins.nombre}</button>`;
        });
        html += '</div>';
        dd.innerHTML = html;

        setTimeout(() => {
            const si = document.getElementById('dds-' + idx);
            if (!si) return;
            si.focus();
            si.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();
                const listEl = document.getElementById('ddl-' + idx);
                const filtered = term ? INSUMOS_CATALOGO.filter(i => (i.nombre||'').toLowerCase().includes(term)).slice(0,15) : INSUMOS_CATALOGO.slice(0,15);
                if (!filtered.length) {
                    listEl.innerHTML = '<div style="padding:12px;text-align:center;font-size:11px;color:#94a3b8">Sin resultados</div>';
                    return;
                }
                listEl.innerHTML = filtered.map(ins => {
                    const n = (ins.nombre || '').replace(/'/g, "\\'");
                    return `<button type="button" onclick="window.__setDD(${idx}, '${n}')"
                        style="display:block;width:100%;padding:7px 12px;text-align:left;font-size:11px;color:#334155;background:none;border:none;cursor:pointer;border-bottom:1px solid #f8fafc"
                        onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'"
                        >${ins.nombre}</button>`;
                }).join('');
            });
        }, 30);
    }

    window.__toggleDD = function (idx) {
        const dd = document.getElementById('dd-m-' + idx);
        if (!dd) return;
        const wasOpen = !dd.classList.contains('hidden');
        closeAllDropdowns();
        if (!wasOpen) { buildDropdown(idx); dd.classList.remove('hidden'); openDropdownIdx = idx; }
    };
    window.__setDD = setConceptoInsumo;
    window.__removeDD = removeConceptoInsumo;

    document.addEventListener('click', function (e) {
        if (openDropdownIdx === null) return;
        const dd = document.getElementById('dd-m-' + openDropdownIdx);
        if (dd && !dd.contains(e.target) && !e.target.closest('[onclick]')) closeAllDropdowns();
    });

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
        // Solo validar insumo si el selector esta visible (no hay conceptos XML)
        if (!state.tieneConceptosXml && !$('fdInsumoSelect').value) { show('errFdInsumo'); ok = false; }
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
        if (Object.keys(state.conceptosInsumos).length > 0) {
            form.append('conceptos_insumos', JSON.stringify(state.conceptosInsumos));
        }

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
        $('inputXml').addEventListener('change', async function () { await handleXmlFile(this.files[0]); });
        setupDragDrop('zonaXml', async file => { $('inputXml').value = ''; await handleXmlFile(file); });
        $('inputPdf').addEventListener('change', async function () { await handlePdfFile(this.files[0]); });
        setupDragDrop('zonaPdf', async file => { await handlePdfFile(file); });
    });

})();
</script>

@stack('facturas_scripts')
@endpush