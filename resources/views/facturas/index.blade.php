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

    .insumo-select-wrap select { min-width: 160px; max-width: 220px; }

    /* ── Zona de archivos ── */
    .zona-archivo { transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s; }
    .zona-archivo.dragging { box-shadow: 0 0 0 3px rgba(99,102,241,0.3); }
    .zona-archivo-xml.tiene-archivo { border-color: #818cf8; background-color: rgba(238,242,255,0.6); }
    .dark .zona-archivo-xml.tiene-archivo { background-color: rgba(49,46,129,0.2); }
    .zona-archivo-pdf.tiene-archivo { border-color: #34d399; background-color: rgba(236,253,245,0.6); }
    .dark .zona-archivo-pdf.tiene-archivo { background-color: rgba(6,78,59,0.15); }

    @keyframes pulse-badge { 0%,100%{opacity:1} 50%{opacity:0.6} }
    .badge-primero { animation: pulse-badge 2s ease-in-out infinite; }
</style>

<div class="w-full mx-auto max-w-7xl">

    {{-- ═══ Header ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
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

    {{-- ═══ Tabs ═══════════════════════════════════════════════════════════════ --}}
    <div id="content-facturas" class="tab-content active">
        @include('facturas.table')
    </div>
    <div id="content-historial" class="tab-content">
        @include('facturas.tabla_historial')
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MODAL NUEVA FACTURA DIRECTA
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div id="modalFacturaDirecta"
        class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/75 backdrop-blur-sm px-4">

        <div class="relative w-full max-w-2xl bg-gray-50 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden"
            style="box-shadow:0 25px 50px -12px rgba(0,0,0,0.35)">

            {{-- HEADER --}}
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4 bg-gray-50 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center border border-indigo-100 dark:border-indigo-800/40">
                        <i class="fas fa-file-invoice text-indigo-500 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 leading-tight">Subir Factura Directa</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sin solicitud asociada — se registrará como N/A</p>
                    </div>
                </div>
                <button type="button" id="btnCerrarFacturaDirecta"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 bg-gray-50 dark:bg-slate-900">

                {{-- Aviso prioridad XML --}}
                <div id="avisoPrioridadXml"
                    class="flex items-start gap-2.5 px-4 py-3 rounded-xl bg-violet-50 dark:bg-violet-950/30 border border-violet-200 dark:border-violet-800/50">
                    <i class="fas fa-info-circle text-violet-500 text-sm mt-0.5 shrink-0"></i>
                    <p class="text-xs text-violet-700 dark:text-violet-300 leading-relaxed">
                        Sube primero el <strong>XML</strong> — valida el CFDI y rellena los campos automáticamente.
                        El <strong>PDF</strong> se usa como respaldo visual o para proveedores extranjeros (Starlink, AWS, etc.).
                    </p>
                </div>

                {{-- ── ZONAS DE ARCHIVOS ─────────────────────────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- XML --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 text-[9px] font-bold badge-primero">
                                <i class="fas fa-star text-[8px]"></i> PRIMERO
                            </span>
                            Factura XML
                        </label>
                        <label id="zonaXml"
                            class="zona-archivo zona-archivo-xml flex flex-col items-center justify-center gap-2 h-28 rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-700 bg-violet-50/40 dark:bg-violet-950/10 cursor-pointer hover:border-violet-400 hover:bg-violet-50 dark:hover:border-violet-500 dark:hover:bg-violet-900/20 transition-all duration-200 group">
                            <input type="file" id="inputXml" accept=".xml,text/xml,application/xml" class="hidden">
                            <div id="iconoXml" class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center group-hover:bg-violet-200 dark:group-hover:bg-violet-800/50 transition-colors">
                                <i class="fas fa-file-code text-violet-500 dark:text-violet-400 text-base transition-colors"></i>
                            </div>
                            <div class="text-center px-2">
                                <p id="textoXml" class="text-xs font-semibold text-violet-600 dark:text-violet-400 group-hover:text-violet-700 dark:group-hover:text-violet-300 transition-colors">Subir XML</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">.xml · CFDI 3.3 / 4.0</p>
                            </div>
                        </label>
                        <div id="spinnerXml" class="hidden mt-2 flex items-center gap-2 text-xs text-violet-500 font-medium">
                            <i class="fas fa-spinner fa-spin"></i> Validando CFDI...
                        </div>
                        <p id="errorXml" class="hidden mt-2 text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </p>
                    </div>

                    {{-- PDF --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                            Factura PDF
                            <span class="ml-1 font-normal normal-case tracking-normal text-slate-300 dark:text-slate-600">(visual / extranjeros)</span>
                        </label>
                        <label id="zonaPdf"
                            class="zona-archivo zona-archivo-pdf flex flex-col items-center justify-center gap-2 h-28 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 cursor-pointer hover:border-emerald-400 dark:hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-all duration-200 group">
                            <input type="file" id="inputPdf" accept=".pdf,application/pdf" class="hidden">
                            <div id="iconoPdf" class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 transition-colors">
                                <i class="fas fa-file-pdf text-slate-400 dark:text-slate-500 group-hover:text-emerald-500 text-base transition-colors"></i>
                            </div>
                            <div class="text-center px-2">
                                <p id="textoPdf" class="text-xs font-semibold text-slate-500 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-300 transition-colors">Subir PDF</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">.pdf · representación impresa</p>
                            </div>
                        </label>
                        <div id="spinnerPdf" class="hidden mt-2 flex items-center gap-2 text-xs text-emerald-500 font-medium">
                            <i class="fas fa-spinner fa-spin"></i> Extrayendo datos del PDF...
                        </div>
                        <div id="avisoPdfExtranjero" class="hidden mt-2 flex items-center gap-1.5 text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">
                            <i class="fas fa-globe text-[10px]"></i>
                            <span>PDF procesado — datos extraídos como proveedor extranjero</span>
                        </div>
                        <div id="avisoPdfSobreescribe" class="hidden mt-2 flex items-center gap-1.5 text-[10px] text-amber-600 dark:text-amber-400 font-medium">
                            <i class="fas fa-exclamation-triangle text-[10px]"></i>
                            <span>El XML ya está cargado — el PDF se agrega solo como visual</span>
                        </div>
                    </div>

                    {{-- Preview PDF --}}
                    <div id="pdfPreviewContainer" class="hidden sm:col-span-2 mt-2 w-full h-72 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <iframe id="pdfPreviewFrame" class="w-full h-full" src=""></iframe>
                    </div>
                </div>

                {{-- ── PANEL DATOS PARSEADOS ─────────────────────────────── --}}
                <div id="seccionParsed" class="hidden rounded-xl border overflow-hidden">
                    <div id="parsedHeader"
                        class="px-4 py-2.5 border-b flex items-center gap-2 bg-indigo-100/80 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-800/40">
                        <i id="parsedHeaderIcon" class="fas fa-check-circle text-indigo-500 dark:text-indigo-400 text-xs"></i>
                        <span id="parsedHeaderText" class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Datos detectados del XML</span>
                    </div>
                    <div class="px-4 py-4 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800/40 border-t-0 rounded-b-xl">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Emisor</p>
                            <p id="parsedEmisor" class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">UUID</p>
                            <p id="parsedUuid" class="text-xs font-mono text-indigo-600 dark:text-indigo-400 truncate">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Total</p>
                            <p id="parsedTotal" class="text-sm font-bold text-emerald-600 dark:text-emerald-400">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Mes</p>
                            <p id="parsedMes" class="text-sm text-slate-600 dark:text-slate-300">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Año</p>
                            <p id="parsedAnio" class="text-sm text-slate-600 dark:text-slate-300">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Conceptos</p>
                            <p id="parsedConceptos" class="text-sm text-slate-600 dark:text-slate-300">—</p>
                        </div>
                    </div>
                </div>

                {{-- ── TABLA DE CONCEPTOS ────────────────────────────────── --}}
                <div id="seccionConceptos" class="hidden rounded-xl border border-violet-200 dark:border-violet-800/40 overflow-hidden">
                    <div class="px-4 py-2.5 bg-violet-100/60 dark:bg-violet-900/20 border-b border-violet-200 dark:border-violet-800/40 flex items-center gap-2">
                        <i class="fas fa-list text-violet-500 text-xs"></i>
                        <span class="text-[11px] font-bold text-violet-700 dark:text-violet-300 uppercase tracking-wider">Conceptos detectados</span>
                        <span id="contadorConceptos" class="ml-auto text-[10px] text-violet-500 font-medium"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-violet-50/80 dark:bg-violet-950/30 text-violet-600 dark:text-violet-400">
                                    <th class="text-left px-3 py-2 font-semibold">Descripción</th>
                                    <th class="text-right px-3 py-2 font-semibold w-16">Cant.</th>
                                    <th class="text-right px-3 py-2 font-semibold w-28">Val. Unit.</th>
                                    <th class="text-right px-3 py-2 font-semibold w-28">Importe</th>
                                    <th class="text-center px-3 py-2 font-semibold w-28">Catálogo</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyConceptos" class="divide-y divide-violet-100 dark:divide-violet-900/30"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Badge auto-match insumo --}}
                <div id="seccionAutoMatch" class="hidden flex items-start gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/40">
                    <i class="fas fa-magic text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300 leading-relaxed">
                        Insumo seleccionado automáticamente: <strong id="autoMatchNombre"></strong>
                    </p>
                </div>

                {{-- ── CAMPOS MANUALES ───────────────────────────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                            Nombre / Descripción <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="fdNombre" placeholder="Ej: Factura Microsoft Office – Oct 2025"
                            class="w-full h-11 px-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-600 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
                        <p id="errFdNombre" class="hidden mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> Campo requerido
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                            Gerencia <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="fdGerencia"
                                class="w-full h-11 pl-4 pr-10 appearance-none text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
                                <option value="">— Seleccione Gerencia —</option>
                                @if(isset($gerencias))
                                    @foreach($gerencias as $gerencia)
                                        <option value="{{ $gerencia->id }}">{{ $gerencia->nombre }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <p id="errFdGerencia" class="hidden mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> Seleccione una gerencia
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                            Insumo <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            {{-- value = NombreInsumo (string) — igual que el index() del controller --}}
                            <select id="fdInsumoSelect"
                                class="w-full h-11 pl-4 pr-10 appearance-none text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
                                <option value="">— Seleccione Insumo —</option>
                                @if(isset($insumos))
                                    @foreach($insumos as $insumo)
                                        <option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <p id="errFdInsumo" class="hidden mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> Seleccione un insumo
                        </p>
                    </div>

                    <div class="hidden"><input type="text" id="fdInsumoNombre"></div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Importe</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-mono">$</span>
                            <input type="number" id="fdImporte" step="0.01" min="0" placeholder="0.00"
                                class="w-full h-11 pl-7 pr-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-600 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                            Costo <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-mono">$</span>
                            <input type="number" id="fdCosto" step="0.01" min="0" placeholder="0.00"
                                class="w-full h-11 pl-7 pr-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-600 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
                        </div>
                        <p id="errFdCosto" class="hidden mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> Campo requerido
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Mes</label>
                        <div class="relative">
                            <select id="fdMes"
                                class="w-full h-11 pl-4 pr-10 appearance-none text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
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
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Año</label>
                        <input type="number" id="fdAnio" min="2000" max="2099" placeholder="{{ date('Y') }}"
                            class="w-full h-11 px-4 text-sm rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-600 focus:border-indigo-400 focus:ring-0 outline-none transition-all">
                    </div>
                </div>

                {{-- Banner info N/A --}}
                <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    <i class="fas fa-info-circle text-slate-400 text-sm mt-0.5"></i>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Esta factura <span class="font-semibold text-slate-600 dark:text-slate-300">no estará asociada a ninguna solicitud</span> — el campo  quedará en
                        <code class="px-1.5 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[11px] font-mono">N/A</code>.
                    </p>
                </div>

            </div>{{-- /body --}}

            {{-- FOOTER --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 flex items-center justify-end gap-3">
                <button type="button" id="btnCancelarFacturaDirecta"
                    class="h-10 px-5 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btnGuardarFacturaDirecta"
                    class="h-10 inline-flex items-center gap-2 px-5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    style="box-shadow:0 4px 12px rgba(99,102,241,0.3)">
                    <i class="fas fa-save text-xs"></i>
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
            const el = document.getElementById('content-' + t);
            t === tab ? el.classList.add('active') : el.classList.remove('active');
        });
        if (tab === 'historial') {
            setTimeout(() => {
                if (typeof window.initComparativa === 'function') window.initComparativa();
                if (typeof Highcharts !== 'undefined') {
                    Highcharts.charts.forEach(c => { if (c) c.reflow(); });
                }
            }, 80);
        }
    };
})();

(function () {

    const state = {
        xmlFile:      null,
        pdfFile:      null,
        parsed:       null,
        origenParsed: null,
    };

    const mesesNombres = {
        1:'Enero',2:'Febrero',3:'Marzo',4:'Abril',5:'Mayo',6:'Junio',
        7:'Julio',8:'Agosto',9:'Septiembre',10:'Octubre',11:'Noviembre',12:'Diciembre',
    };

    const $    = id => document.getElementById(id);
    const show = id => $(id) && $(id).classList.remove('hidden');
    const hide = id => $(id) && $(id).classList.add('hidden');

    function abrirModal() { resetModal(); $('modalFacturaDirecta').classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
    function cerrarModal() { $('modalFacturaDirecta').classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }

    function resetModal() {
        state.xmlFile = null; state.pdfFile = null; state.parsed = null; state.origenParsed = null;
        ['inputXml','inputPdf'].forEach(id => { if ($(id)) $(id).value = ''; });
        ['fdNombre','fdGerencia','fdInsumoSelect','fdInsumoNombre','fdImporte','fdCosto','fdMes','fdAnio']
            .forEach(id => { if ($(id)) $(id).value = ''; });
        ['seccionParsed','seccionConceptos','seccionAutoMatch','spinnerXml','spinnerPdf','errorXml',
         'errFdNombre','errFdCosto','errFdGerencia','errFdInsumo','avisoPdfExtranjero','avisoPdfSobreescribe']
            .forEach(hide);
        resetZonaXml(); resetZonaPdf();
        $('pdfPreviewContainer').classList.add('hidden');
        $('pdfPreviewFrame').src = '';
        $('tbodyConceptos').innerHTML = '';
    }

    function resetZonaXml() {
        $('zonaXml').classList.remove('tiene-archivo');
        $('textoXml').textContent = 'Subir XML';
        $('iconoXml').innerHTML = '<i class="fas fa-file-code text-violet-500 dark:text-violet-400 text-lg transition-colors"></i>';
    }
    function resetZonaPdf() {
        $('zonaPdf').classList.remove('tiene-archivo');
        $('textoPdf').textContent = 'Subir PDF';
        $('iconoPdf').innerHTML = '<i class="fas fa-file-pdf text-slate-400 dark:text-slate-500 group-hover:text-emerald-500 text-lg transition-colors"></i>';
    }

    function autoMatchInsumo(conceptos) {
        if (!conceptos || !conceptos.length) return;
        const primerConcepto = conceptos[0];
        const matchNombre = primerConcepto.insumoNombre;
        if (!matchNombre) return;

        const sel = $('fdInsumoSelect');
        const option = Array.from(sel.options).find(o =>
            o.value.trim().toLowerCase() === matchNombre.trim().toLowerCase()
            || o.text.trim().toLowerCase() === matchNombre.trim().toLowerCase()
        );
        if (!option) return;

        sel.value = option.value;
        $('autoMatchNombre').textContent = option.text.trim();
        show('seccionAutoMatch');
    }

    function poblarCampos(data, origen) {
        const yaHayXml = state.origenParsed === 'xml';
        if (origen === 'pdf' && yaHayXml) { show('avisoPdfSobreescribe'); return; }
        hide('avisoPdfSobreescribe');

        state.parsed       = data;
        state.origenParsed = origen;

        if (data.emisor && !$('fdNombre').value.trim()) $('fdNombre').value = data.emisor;

        if (data.total) {
            const t = parseFloat(data.total);
            if (!isNaN(t)) {
                $('fdImporte').value = t.toFixed(2);
                if (!$('fdCosto').value) $('fdCosto').value = t.toFixed(2);
            }
        }
        if (data.mes)  $('fdMes').value  = data.mes;
        if (data.anio) $('fdAnio').value = data.anio;

        if (data.conceptos && data.conceptos.length > 0) {
            $('fdInsumoNombre').value = data.conceptos[0].nombre ?? '';
            autoMatchInsumo(data.conceptos);
        }

        $('parsedEmisor').textContent    = data.emisor || '—';
        $('parsedUuid').textContent      = data.uuid ? data.uuid.toUpperCase() : (origen === 'pdf' ? '(PDF extranjero)' : '—');
        $('parsedTotal').textContent     = data.total
            ? '$' + parseFloat(data.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) + ' ' + (data.moneda ?? 'MXN')
            : '—';
        $('parsedMes').textContent       = data.mes  ? (mesesNombres[data.mes]  ?? String(data.mes))  : '—';
        $('parsedAnio').textContent      = data.anio ? String(data.anio) : '—';
        $('parsedConceptos').textContent = data.conceptos ? data.conceptos.length + ' concepto(s)' : '—';

        const isXml      = origen === 'xml';
        const headerBg   = isXml ? 'bg-indigo-100/80 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-800/40'
                                 : 'bg-emerald-100/60 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800/40';
        const iconClass  = isXml ? 'fas fa-check-circle text-indigo-500 dark:text-indigo-400 text-xs'
                                 : 'fas fa-file-pdf text-emerald-500 dark:text-emerald-400 text-xs';
        const headerText = isXml ? 'Datos detectados del XML' : 'Datos extraídos del PDF (proveedor extranjero)';
        const textColor  = isXml ? 'text-indigo-700 dark:text-indigo-300' : 'text-emerald-700 dark:text-emerald-300';

        $('parsedHeader').className       = `px-4 py-2.5 border-b flex items-center gap-2 ${headerBg}`;
        $('parsedHeaderIcon').className   = iconClass;
        $('parsedHeaderText').className   = `text-[11px] font-bold uppercase tracking-wider ${textColor}`;
        $('parsedHeaderText').textContent = headerText;
        show('seccionParsed');

        renderConceptos(data.conceptos ?? []);
    }

    function renderConceptos(conceptos) {
        const tbody = $('tbodyConceptos');
        tbody.innerHTML = '';
        if (!conceptos.length) { hide('seccionConceptos'); return; }

        $('contadorConceptos').textContent = conceptos.length + ' concepto(s)';

        conceptos.forEach(c => {
            const tr = document.createElement('tr');
            tr.className = 'bg-gray-50/70 dark:bg-slate-800/40 hover:bg-violet-50/60 dark:hover:bg-violet-950/20 transition-colors';

            const tieneMatch = !!(c.insumoNombre || c.insumoId);
            const matchLabel = tieneMatch ? (c.insumoNombre || '') : '';
            const badge = tieneMatch
                ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700" title="${matchLabel}">
                       <i class="fas fa-check-circle"></i> Match
                   </span>`
                : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                       <i class="fas fa-question-circle"></i> Sin match
                   </span>`;

            const costo   = parseFloat(c.costo   ?? c.ValorUnitario ?? 0);
            const importe = parseFloat(c.importe  ?? c.Importe       ?? 0);
            const cant    = c.cantidad ?? 1;

            tr.innerHTML = `
                <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200">${c.nombre ?? '—'}</td>
                <td class="px-3 py-2.5 text-right text-slate-500 dark:text-slate-400">${cant}</td>
                <td class="px-3 py-2.5 text-right text-slate-600 dark:text-slate-300 font-mono">
                    ${costo > 0 ? '$' + costo.toLocaleString('es-MX', { minimumFractionDigits: 2 }) : '—'}
                </td>
                <td class="px-3 py-2.5 text-right font-semibold text-slate-700 dark:text-slate-200 font-mono">
                    ${importe > 0 ? '$' + importe.toLocaleString('es-MX', { minimumFractionDigits: 2 }) : '—'}
                </td>
                <td class="px-3 py-2.5 text-center">${badge}</td>
            `;
            tbody.appendChild(tr);
        });
        show('seccionConceptos');
    }

    async function parsearXml(file) {
        hide('errorXml'); show('spinnerXml');
        const form = new FormData();
        form.append('xml', file);
        form.append('_token', '{{ csrf_token() }}');
        try {
            const res  = await fetch('{{ route("facturas.parsearXml") }}', {
                method: 'POST', body: form,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (!res.ok || data.error) throw new Error(data.error ?? 'Error al procesar el XML.');

            poblarCampos(data, 'xml');
            $('zonaXml').classList.add('tiene-archivo');
            $('textoXml').textContent = file.name.length > 22 ? file.name.substring(0, 22) + '…' : file.name;
            $('iconoXml').innerHTML = '<i class="fas fa-check-circle text-violet-500 text-lg"></i>';
            hide('avisoPdfSobreescribe'); hide('avisoPdfExtranjero');
        } catch (e) {
            $('errorXml').querySelector('span').textContent = e.message;
            show('errorXml');
            $('zonaXml').classList.remove('tiene-archivo');
        } finally { hide('spinnerXml'); }
    }

    async function parsearPdf(file) {
        show('spinnerPdf'); hide('avisoPdfExtranjero'); hide('avisoPdfSobreescribe');
        const form = new FormData();
        form.append('pdf', file);
        form.append('_token', '{{ csrf_token() }}');
        try {
            const res  = await fetch('{{ route("facturas.previsualizarPdf") }}', {
                method: 'POST', body: form,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (res.ok && !data.error) {
                poblarCampos(data, 'pdf');
                if (state.origenParsed !== 'xml') show('avisoPdfExtranjero');
            }
        } catch (_) {  } finally { hide('spinnerPdf'); }
    }

    function validar() {
        let ok = true;
        ['errFdNombre','errFdCosto','errFdGerencia','errFdInsumo'].forEach(hide);
        if (!$('fdNombre').value.trim())   { show('errFdNombre');   ok = false; }
        if (!$('fdGerencia').value)        { show('errFdGerencia'); ok = false; }
        if (!$('fdInsumoSelect').value)    { show('errFdInsumo');   ok = false; }
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
                Swal.fire({ icon:'success', title:'¡Factura guardada!', text:'La factura fue registrada correctamente.',
                    timer:3000, showConfirmButton:false, toast:true, position:'top-end',
                    background:'#f9fafb', color:'#1e293b' });
            }
            if (window.jQuery && window.jQuery('#facturasTable').length) {
                window.jQuery('#facturasTable').DataTable().ajax.reload(null, false);
            }
        } catch (e) {
            if (window.Swal) {
                Swal.fire({ icon:'error', title:'Error', text:e.message, background:'#f9fafb', color:'#1e293b', confirmButtonColor:'#e11d48' });
            } else {
                Swal.fire({
                    icon: 'error', title: 'Error', text: e.message,
                    background: '#f9fafb', color: '#1e293b', confirmButtonColor: '#e11d48',
                });
            }
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

    async function handleXmlFile(file) {
        if (!file) return;
        state.xmlFile = file;
        await parsearXml(file);
    }

    async function handlePdfFile(file) {
        if (!file) return;
        state.pdfFile = file;
        $('textoPdf').textContent = file.name.length > 22 ? file.name.substring(0, 22) + '…' : file.name;
        $('iconoPdf').innerHTML = '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>';
        $('zonaPdf').classList.add('tiene-archivo');
        const url = URL.createObjectURL(file);
        $('pdfPreviewFrame').src = url;
        $('pdfPreviewContainer').classList.remove('hidden');
        await parsearPdf(file);
    }

    document.addEventListener('DOMContentLoaded', function () {

        $('btnAbrirFacturaDirecta').addEventListener('click',    abrirModal);
        $('btnCerrarFacturaDirecta').addEventListener('click',   cerrarModal);
        $('btnCancelarFacturaDirecta').addEventListener('click', cerrarModal);
        $('btnGuardarFacturaDirecta').addEventListener('click',  guardar);

        $('modalFacturaDirecta').addEventListener('click', e => { if (e.target === $('modalFacturaDirecta')) cerrarModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !$('modalFacturaDirecta').classList.contains('hidden')) cerrarModal(); });

        $('inputXml').addEventListener('change', async function () { await handleXmlFile(this.files[0]); });
        setupDragDrop('zonaXml', async file => { $('inputXml').value = ''; await handleXmlFile(file); });

        $('inputPdf').addEventListener('change', async function () { await handlePdfFile(this.files[0]); });
        setupDragDrop('zonaPdf', async file => { await handlePdfFile(file); });

        $('fdInsumoSelect').addEventListener('change', () => hide('seccionAutoMatch'));
    });

})();
</script>

@stack('facturas_scripts')
@endpush