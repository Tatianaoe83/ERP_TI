@extends('layouts.app')

@section('content')
<style>
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background-color: #475569; }

    .tog-group { display: inline-flex; flex-direction: column; gap: 5px; align-items: flex-start; }
    .tog-mg-row { display: flex; align-items: center; gap: 6px; }
    .tog-btn {
        padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px;
        cursor: pointer; border: 1px solid #cbd5e1; background: #f8fafc; color: #64748b;
        transition: all .15s; white-space: nowrap; line-height: 1.4; min-width: 96px; text-align: center;
    }
    .dark .tog-btn { border-color: #475569; background: #1e293b; color: #94a3b8; }
    .tog-btn.active { background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; font-weight: 600; }
    .dark .tog-btn.active { background: #1e3a5f; color: #93c5fd; border-color: #3b82f6; }
    .tog-btn.solo { cursor: default; pointer-events: none; }

    .tog-connector { width: 14px; height: 1px; background: #e2e8f0; flex-shrink: 0; transition: background .15s; }
    .dark .tog-connector { background: #334155; }
    .tog-connector.active { background: #93c5fd; }
    .dark .tog-connector.active { background: #3b82f6; }

    .inp-wrap { position: relative; display: flex; align-items: center; }
    .margen-input {
        width: 68px; padding: 4px 18px 4px 8px; font-size: 12px; border-radius: 6px;
        border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; text-align: right;
        outline: none; transition: border-color .15s, box-shadow .15s, background .15s;
        font-family: ui-monospace, monospace; font-weight: 500;
    }
    .dark .margen-input { border-color: #475569; background: #1e293b; color: #e2e8f0; }
    .margen-input.active-input { border-color: #93c5fd; background: #f0f7ff; color: #1d4ed8; }
    .dark .margen-input.active-input { border-color: #3b82f6; background: #1e3a5f; color: #93c5fd; }
    .margen-input:focus { border-color: #6366f1 !important; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
    .pct-sign { position: absolute; right: 6px; font-size: 11px; color: #94a3b8; pointer-events: none; }
    .dark .pct-sign { color: #64748b; }
</style>

<div class="w-full mx-auto bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">

    <!-- Encabezado -->
    <div class="px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Generar Corte Anual</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Gestión visual de costos y variantes por gerencia.</p>
            </div>
        </div>
    </div>

    <!-- Filtros y acciones -->
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
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 transition-colors">
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
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 transition-colors">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 items-center lg:pb-0.5">
                <button type="button" id="verGuardado"
                    class="h-11 px-5 flex items-center justify-center gap-2 rounded-xl text-sm font-bold border-2 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200">
                    <i class="fas fa-folder-open"></i> <span>Ver corte guardado</span>
                </button>
                <button type="button" id="enviar"
                    class="h-11 px-6 flex items-center justify-center gap-2 rounded-xl text-sm font-bold text-white shadow-lg shadow-indigo-500/20 dark:shadow-indigo-900/40 transition-all duration-200
                           bg-indigo-600 hover:bg-indigo-500 hover:-translate-y-0.5 active:translate-y-0 active:shadow-md">
                    <i class="fas fa-calculator"></i> <span>Generar corte</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Estado por gerencia -->
    <div class="px-6 md:px-8 py-5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950/50">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">
            Estado por gerencia ({{ $anioConsulta }})
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Con corte — badges clickeables -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 overflow-hidden min-h-[120px] flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20 shrink-0 flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Con corte</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm">({{ count($gerenciasConCorte) }})</span>
                    <span class="ml-auto text-[10px] text-emerald-600 dark:text-emerald-500 italic flex items-center gap-1">
                        <i class="fas fa-hand-pointer text-[9px]"></i> Click para ver
                    </span>
                </div>
                <div class="p-3 flex-1 overflow-y-auto custom-scroll min-h-0">
                    @forelse($gerenciasConCorte as $g)
                        <button type="button"
                            class="ver-corte-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                   bg-emerald-100 dark:bg-emerald-800/40
                                   text-emerald-800 dark:text-emerald-200
                                   text-xs font-medium mr-1.5 mb-1
                                   hover:bg-emerald-200 dark:hover:bg-emerald-700/50
                                   hover:shadow-sm active:scale-95
                                   transition-all duration-150 cursor-pointer border border-transparent
                                   hover:border-emerald-300 dark:hover:border-emerald-600"
                            data-gerencia-id="{{ $g->GerenciaID }}"
                            data-gerencia-nombre="{{ $g->NombreGerencia }}"
                            title="Ver corte guardado de {{ $g->NombreGerencia }}">
                            <i class="fas fa-folder-open text-[10px] opacity-60"></i>
                            {{ $g->NombreGerencia }}
                        </button>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">Ninguna gerencia con corte en este año.</p>
                    @endforelse
                </div>
            </div>

            <!-- Sin corte — solo lectura -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 overflow-hidden min-h-[120px] flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-amber-50 dark:bg-amber-900/20 shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Sin corte</span>
                    <span class="ml-2 text-slate-500 dark:text-slate-400 text-sm">({{ count($gerenciasSinCorte) }})</span>
                </div>
                <div class="p-3 flex-1 overflow-y-auto custom-scroll min-h-0">
                    @forelse($gerenciasSinCorte as $g)
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-800/40 text-amber-800 dark:text-amber-200 text-xs font-medium mr-1.5 mb-1">{{ $g->NombreGerencia }}</span>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">Todas las gerencias tienen corte.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="px-6 md:px-8 py-5">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">Datos del corte</p>
    </div>
    <div class="relative w-full custom-scroll overflow-x-auto bg-gray-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
        <table id="tabla" class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insumo</th>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center w-full">Monto Base / Margen (%)</th>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Precio Final</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900"></tbody>
        </table>

        <div id="tabla-placeholder" class="py-24 text-center bg-gray-50 dark:bg-slate-900 transition-colors">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-50 dark:bg-slate-800 mb-4">
                <i class="fas fa-search-dollar text-3xl text-slate-300 dark:text-slate-600"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-700 dark:text-slate-200">Esperando datos</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">Selecciona una gerencia arriba para comenzar.</p>
        </div>

        <div id="guardar-en-footer" class="hidden">
            <button type="button" id="guardar"
                class="h-10 px-5 flex items-center justify-center gap-2 rounded-xl text-sm font-bold text-white shadow-lg shadow-emerald-500/20 dark:shadow-emerald-900/40 transition-all duration-200
                       bg-emerald-600 hover:bg-emerald-500 hover:-translate-y-0.5 active:translate-y-0 active:shadow-md">
                <i class="fas fa-save"></i> <span>Guardar</span>
            </button>
        </div>
    </div>

    <!-- Modal: Corte guardado -->
    <div id="bloque-corte-guardado" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="modal-corte-guardado-title">
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" id="modal-corte-guardado-backdrop"></div>
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-6xl max-h-[90vh] flex flex-col bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="flex items-center justify-between shrink-0 px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                    <h2 id="modal-corte-guardado-title" class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-folder-open text-indigo-500"></i>
                        <span>Corte guardado</span>
                        <span id="modal-corte-nombre" class="text-slate-400 dark:text-slate-500 font-normal text-base"></span>
                    </h2>
                    <button type="button" id="cerrar-corte-guardado"
                        class="rounded-lg p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition-colors"
                        aria-label="Cerrar">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Loading state dentro del modal -->
                <div id="modal-corte-loading" class="hidden flex-1 flex items-center justify-center py-16">
                    <div class="flex flex-col items-center gap-3">
                        <i class="fas fa-spinner fa-spin text-2xl text-slate-400 dark:text-slate-500"></i>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Cargando corte...</p>
                    </div>
                </div>

                <div id="modal-corte-contenido" class="flex-1 min-h-0 overflow-auto p-4 sm:p-6">
                    <div class="overflow-x-auto custom-scroll -mx-2 sm:mx-0">
                        <table id="tabla-guardados" class="w-full text-left border-collapse min-w-[700px]">
                            <thead id="tabla-guardados-head" class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800"></thead>
                            <tbody id="tabla-guardados-body" class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900"></tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-4">
                        Costo = base por variante; Costo + margen = precio aplicado por mes; Costo total (año) = suma anual.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('third_party_scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {

    const currencyFmt = new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: 'MXN', maximumFractionDigits: 2
    });

    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio',
                   'Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    // ─── DataTable ─────────────────────────────────────────────────────────────
    const table = $('#tabla').DataTable({
        destroy      : true,
        responsive   : true,
        searching    : true,
        processing   : true,
        serverSide   : true,
        pageLength   : 25,
        deferLoading : 0,
        dom: 'rt<"dt-corte-footer flex flex-col sm:flex-row justify-between items-center p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950"ip>',
        language: {
            zeroRecords: "<div class='py-8 text-center text-slate-400 dark:text-slate-500 italic'>Sin resultados</div>",
            info       : "<span class='text-xs font-medium text-slate-500 dark:text-slate-400'>Viendo <span class='text-slate-800 dark:text-white font-bold'>_START_</span> - <span class='text-slate-800 dark:text-white font-bold'>_END_</span> de <span class='text-slate-800 dark:text-white font-bold'>_TOTAL_</span></span>",
            infoEmpty  : "<span class='text-xs font-medium text-slate-400'>Sin registros</span>",
            paginate   : {
                first: '<<', last: '>>',
                next    : '<i class="fas fa-chevron-right text-[10px]"></i>',
                previous: '<i class="fas fa-chevron-left text-[10px]"></i>'
            }
        },
        ajax: {
            url : '{{ route("cortes.ver") }}',
            type: 'GET',
            data: function (d) { d.gerenciaID = $('#gerenciaID').val(); },
            dataSrc: function (json) {
                $('#tabla-placeholder').hide();
                if (!json || !Array.isArray(json.data)) return [];
                return json.data.map(r => {
                    const distintos = r.Distintos || [];
                    return {
                        NombreInsumo : r.NombreInsumo,
                        MontosPorMes : r.MontosPorMes || [],
                        Distintos    : distintos,
                        SelectedIndex: 0,
                        Margenes     : distintos.map(() => 0)
                    };
                });
            },
            error: function (xhr) { console.error('Error AJAX:', xhr); }
        },
        columns: [
            {
                data     : 'NombreInsumo',
                className: 'p-4 align-top text-sm font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800'
            },
            {
                data     : null,
                className: 'p-4 align-top border-b border-slate-100 dark:border-slate-800',
                render   : function (row, type, _, meta) {
                    const distintos = row.Distintos || [];
                    const margenes  = (row.Margenes && row.Margenes.length === distintos.length)
                        ? row.Margenes : distintos.map(() => 0);
                    const rowIdx    = meta.row;
                    const isSolo    = distintos.length <= 1;
                    const pairs = distintos.map((m, i) => {
                        const isActive   = i === (Number(row.SelectedIndex) || 0);
                        const btnClass   = `tog-btn${isActive ? ' active' : ''}${isSolo ? ' solo' : ''}`;
                        const connClass  = `tog-connector${isActive ? ' active' : ''}`;
                        const inputClass = `margen-input${isActive ? ' active-input' : ''}`;
                        return `<div class="tog-mg-row">
                            <button type="button" class="${btnClass}"
                                data-row="${rowIdx}" data-idx="${i}" data-val="${Number(m) || 0}">
                                ${currencyFmt.format(Number(m) || 0)}
                            </button>
                            <div class="${connClass}" data-row="${rowIdx}" data-idx="${i}"></div>
                            <div class="inp-wrap">
                                <input type="number" min="0" max="100" step="0.1"
                                    class="${inputClass}"
                                    data-row="${rowIdx}" data-idx="${i}"
                                    value="${Number(margenes[i]) || 0}">
                                <span class="pct-sign">%</span>
                            </div>
                        </div>`;
                    }).join('');
                    return `<div class="flex justify-center"><div class="tog-group" data-row="${rowIdx}">${pairs}</div></div>`;
                }
            },
            {
                data     : null,
                className: 'p-4 align-top text-right border-b border-slate-100 dark:border-slate-800',
                render   : function (row, type, _, meta) {
                    const idx      = Number(row.SelectedIndex) || 0;
                    const margenes = (row.Margenes && row.Margenes.length)
                        ? row.Margenes : (row.Distintos || []).map(() => 0);
                    const base     = Number(row.Distintos?.[idx] ?? 0);
                    const margen   = Number(margenes[idx]) || 0;
                    const rowIdx   = meta.row;
                    return `<span class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400
                                bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-lg
                                border border-emerald-100 dark:border-emerald-500/20 shadow-sm precio-final"
                                data-row="${rowIdx}">
                                ${currencyFmt.format(base * (1 + margen / 100))}
                            </span>`;
                }
            }
        ],
        drawCallback: function () {
            const base     = 'px-3 py-1.5 ml-1.5 rounded-lg border text-xs font-semibold transition-all duration-200 cursor-pointer shadow-sm ';
            const normal   = 'bg-gray-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-indigo-600 dark:hover:text-indigo-400';
            const active   = '!bg-indigo-600 !border-indigo-600 !text-white shadow-indigo-500/30 hover:!bg-indigo-700';
            const disabled = 'opacity-40 cursor-not-allowed shadow-none';
            $('.dataTables_paginate .paginate_button').addClass(base + normal);
            $('.dataTables_paginate .paginate_button.current').removeClass(normal).addClass(active);
            $('.dataTables_paginate .paginate_button.disabled').addClass(disabled);
        },
        initComplete: function () {
            const $footer = $('.dt-corte-footer');
            if ($footer.length && $('#guardar').length) {
                $('#guardar').appendTo($footer).removeClass('hidden').show();
            }
        }
    });

    // ─── Helpers ───────────────────────────────────────────────────────────────
    function getRowData(rowIdx) { return table.row(rowIdx).data(); }

    function actualizarEstilos(rowIdx) {
        const data = getRowData(rowIdx);
        if (!data) return;
        const activeIdx = Number(data.SelectedIndex) || 0;
        $(`[data-row="${rowIdx}"].tog-btn`).each(function () {
            $(this).toggleClass('active', Number($(this).data('idx')) === activeIdx);
        });
        $(`[data-row="${rowIdx}"].tog-connector`).each(function () {
            $(this).toggleClass('active', Number($(this).data('idx')) === activeIdx);
        });
        $(`[data-row="${rowIdx}"].margen-input`).each(function () {
            $(this).toggleClass('active-input', Number($(this).data('idx')) === activeIdx);
        });
    }

    function actualizarPrecio(rowIdx) {
        const data = getRowData(rowIdx);
        if (!data) return;
        const idx      = Number(data.SelectedIndex) || 0;
        const margenes = (data.Margenes && data.Margenes.length === data.Distintos.length)
            ? data.Margenes : data.Distintos.map(() => 0);
        const base   = Number(data.Distintos?.[idx] ?? 0);
        const margen = Number(margenes[idx]) || 0;
        $(`[data-row="${rowIdx}"].precio-final`).text(currencyFmt.format(base * (1 + margen / 100)));
    }

    // ─── Eventos tabla ─────────────────────────────────────────────────────────
    $('#tabla tbody').on('click', '.tog-btn:not(.solo)', function () {
        const rowIdx = Number($(this).data('row'));
        const data   = getRowData(rowIdx);
        data.SelectedIndex = Number($(this).data('idx'));
        actualizarEstilos(rowIdx);
        actualizarPrecio(rowIdx);
    });

    $('#tabla tbody').on('change', '.margen-input', function () {
        const rowIdx = Number($(this).data('row'));
        const varIdx = Number($(this).data('idx'));
        let num = parseFloat($(this).val()) || 0;
        if (num < 0) num = 0;
        if (num > 100) num = 100;
        $(this).val(num);
        const data = getRowData(rowIdx);
        if (!data) return;
        if (!data.Margenes || data.Margenes.length !== data.Distintos.length) {
            data.Margenes = data.Distintos.map(() => 0);
        }
        data.Margenes[varIdx] = num;
        if (varIdx === (Number(data.SelectedIndex) || 0)) actualizarPrecio(rowIdx);
    });

    // ─── Generar corte ─────────────────────────────────────────────────────────
    $('#enviar').on('click', function () {
        if (!$('#gerenciaID').val()) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Selecciona una gerencia', confirmButtonColor: '#4f46e5' });
            return;
        }
        table.ajax.reload(null, false);
    });

    // ─── Cambio de año ─────────────────────────────────────────────────────────
    $('#anioCorte').on('change', function () {
        const anio = $(this).val();
        if (anio) window.location.href = '{{ route("cortes.index") }}?anio=' + anio;
    });

    // ─── Función central para cargar y abrir el modal ──────────────────────────
    async function abrirCorteGuardado(gid, anio, nombreGerencia) {
        // Mostrar modal con loading
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
            const data = json.data || [];

            renderTablaGuardados(data);
        } catch (e) {
            cerrarModal();
            Swal.fire({ icon: 'error', title: 'Error', text: e?.message || 'No se pudo cargar el corte guardado', confirmButtonColor: '#4f46e5' });
            return;
        }

        // Ocultar loading, mostrar contenido
        $('#modal-corte-loading').addClass('hidden').removeClass('flex');
        $('#modal-corte-contenido').removeClass('hidden');
    }

    // ─── Botón "Ver corte guardado" (desde filtros) ────────────────────────────
    $('#verGuardado').on('click', async function () {
        const gid  = $('#gerenciaID').val();
        const anio = $('#anioCorte').val();
        if (!gid) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Selecciona una gerencia', confirmButtonColor: '#4f46e5' });
            return;
        }
        const nombre = $('#gerenciaID option:selected').text();
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando...');
        await abrirCorteGuardado(gid, anio, nombre);
        $btn.prop('disabled', false).html('<i class="fas fa-folder-open"></i> <span>Ver corte guardado</span>');
    });

    // ─── Click en badge de gerencia "Con corte" ────────────────────────────────
    $(document).on('click', '.ver-corte-badge', async function () {
        const gid    = $(this).data('gerencia-id');
        const nombre = $(this).data('gerencia-nombre');
        const anio   = $('#anioCorte').val();

        // Selecciona la gerencia en el filtro también (para consistencia)
        $('#gerenciaID').val(gid);

        await abrirCorteGuardado(gid, anio, nombre);
    });

    // ─── Render tabla guardados con márgenes por variante ─────────────────────
    function renderTablaGuardados(data) {
        const thead = $('#tabla-guardados-head');
        const tbody = $('#tabla-guardados-body');
        thead.empty();
        tbody.empty();

        if (data.length === 0) {
            thead.html(`<tr>
                <th class="py-3 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insumo</th>
                <th class="py-3 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Info</th>
            </tr>`);
            tbody.append('<tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No hay corte guardado para esta gerencia y año.</td></tr>');
            return;
        }

        const porInsumo = {};
        data.forEach(row => {
            const key = row.NombreInsumo || '';
            if (!porInsumo[key]) porInsumo[key] = [];
            porInsumo[key].push(row);
        });

        const thBase = 'py-3 px-2 sm:px-3 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider';
        let headHtml = `<tr>
            <th class="${thBase} text-left sticky left-0 z-10 bg-slate-50 dark:bg-slate-950 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)] px-3 sm:px-4">Insumo</th>
            <th class="${thBase} text-center whitespace-nowrap">Variante</th>`;
        MESES.forEach(m => {
            headHtml += `<th class="${thBase} text-right whitespace-nowrap">${m}</th>`;
        });
        headHtml += `
            <th class="${thBase} text-right">Costo base</th>
            <th class="${thBase} text-center">Margen (%)</th>
            <th class="${thBase} text-right">Costo + margen</th>
            <th class="${thBase} text-right">Total año</th>
        </tr>`;
        thead.html(headHtml);

        Object.entries(porInsumo).forEach(([nombreInsumo, variantes]) => {
            const rowspan = variantes.length;

            variantes.forEach((row, vi) => {
                const meses         = row.Meses || {};
                const costoBase     = Number(row.Costo) || 0;
                const margen        = Number(row.Margen) || 0;
                const costoConMg    = costoBase * (1 + margen / 100);
                let costoTotalAnual = Number(row.CostoTotalAnual) || 0;
                if (!costoTotalAnual) {
                    costoTotalAnual = MESES.reduce((acc, m) => acc + (meses[m]?.CostoTotal ? Number(meses[m].CostoTotal) : 0), 0);
                }

                const celdasMes = MESES.map(m => {
                    const v = (meses[m] && meses[m].CostoTotal) ? Number(meses[m].CostoTotal) : 0;
                    return `<td class="py-2 px-2 sm:px-3 text-right text-xs font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">
                        ${v > 0 ? currencyFmt.format(v) : '<span class="text-slate-300 dark:text-slate-600">—</span>'}
                    </td>`;
                }).join('');

                const varianteBadge = rowspan > 1
                    ? `<span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                           bg-blue-50 text-blue-600 border border-blue-100
                           dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800/30">Op. ${vi + 1}</span>`
                    : `<span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                           bg-slate-100 text-slate-500 border border-slate-200
                           dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">Fijo</span>`;

                const tdNombre = vi === 0
                    ? `<td class="py-2 px-3 sm:px-4 text-sm font-semibold text-slate-800 dark:text-white
                            sticky left-0 z-[1] bg-gray-50 dark:bg-slate-900
                            shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]"
                            rowspan="${rowspan}">${escapeHtml(nombreInsumo)}</td>`
                    : '';

                const trClass = vi === 0
                    ? 'border-t-2 border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/50 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                    : 'bg-gray-50 dark:bg-slate-900/50 hover:bg-slate-50 dark:hover:bg-slate-800/50';

                tbody.append(`<tr class="${trClass}">
                    ${tdNombre}
                    <td class="py-2 px-2 sm:px-3 text-center">${varianteBadge}</td>
                    ${celdasMes}
                    <td class="py-2 px-3 sm:px-4 text-right text-sm font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">
                        ${costoBase > 0 ? currencyFmt.format(costoBase) : '—'}
                    </td>
                    <td class="py-2 px-3 sm:px-4 text-center text-sm font-mono text-slate-600 dark:text-slate-300">
                        ${margen > 0 ? margen + '%' : '—'}
                    </td>
                    <td class="py-2 px-3 sm:px-4 text-right text-sm font-mono text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                        ${costoConMg > 0 ? currencyFmt.format(costoConMg) : '—'}
                    </td>
                    <td class="py-2 px-3 sm:px-4 text-right text-sm font-mono font-bold text-slate-800 dark:text-white whitespace-nowrap">
                        ${costoTotalAnual > 0 ? currencyFmt.format(costoTotalAnual) : '—'}
                    </td>
                </tr>`);
            });
        });
    }

    // ─── Cerrar modal ──────────────────────────────────────────────────────────
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

    // ─── Guardar corte ─────────────────────────────────────────────────────────
    $('#guardar').on('click', async function () {
        const gid  = $('#gerenciaID').val();
        const anio = parseInt($('#anioCorte').val(), 10) || new Date().getFullYear();

        if (!gid) {
            Swal.fire({ icon: 'error', title: 'Selecciona una gerencia primero', confirmButtonColor: '#4f46e5' });
            return;
        }

        // Verificar si ya existe
        let yaExiste = false;
        try {
            const chk     = await fetch(
                '{{ route("cortes.guardados") }}?anio=' + encodeURIComponent(anio) + '&gerenciaID=' + encodeURIComponent(gid),
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const chkJson = await chk.json().catch(() => ({}));
            yaExiste = Array.isArray(chkJson.data) && chkJson.data.length > 0;
        } catch (_) {
            yaExiste = true;
        }

        if (yaExiste) {
            const confirmResult = await Swal.fire({
                icon             : 'warning',
                title            : '¿Sobreescribir corte?',
                html             : `Ya existe un corte guardado para esta gerencia y año.<br>
                                    <strong>Será reemplazado</strong> con los datos actuales.<br><br>
                                    ¿Deseas continuar?`,
                showCancelButton : true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor : '#64748b',
                confirmButtonText : '<i class="fas fa-save"></i>&nbsp; Sí, sobreescribir',
                cancelButtonText  : 'Cancelar'
            });
            if (!confirmResult.isConfirmed) return;
        }

        const payload = [];
        $('#tabla tbody tr').each(function () {
            const row = table.row(this).data();
            if (!row) return;
            const distintos = row.Distintos    || [];
            const montos    = row.MontosPorMes || [];
            const margenes  = (row.Margenes && row.Margenes.length === distintos.length)
                ? row.Margenes : distintos.map(() => 0);

            for (const mp of montos) {
                if (Number(mp.Costo) <= 0) continue;
                const varIdx     = distintos.findIndex(d => Math.abs(Number(d) - Number(mp.Costo)) < 0.001);
                const margenVal  = varIdx >= 0 ? (Number(margenes[varIdx]) || 0) : 0;
                const costoTotal = +(Number(mp.Costo) * (1 + margenVal / 100)).toFixed(2);
                payload.push({
                    NombreInsumo: decodeHtml(String(row.NombreInsumo || 'SIN_NOMBRE')),
                    Mes         : mp.Mes,
                    Costo       : +Number(mp.Costo).toFixed(2),
                    Margen      : +margenVal.toFixed(2),
                    CostoTotal  : costoTotal,
                    GerenciaID  : Number(gid)
                });
            }
        });

        if (!payload.length) {
            Swal.fire({ icon: 'warning', title: 'Sin datos', confirmButtonColor: '#4f46e5' });
            return;
        }

        const btn = $(this);
        const txt = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        try {
            const res = await fetch(`{{ route('cortes.store') }}`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': `{{ csrf_token() }}`, 'Accept': 'application/json' },
                body   : JSON.stringify({ rows: payload, anio })
            });

            const contentType = res.headers.get('content-type') || '';
            let data = null, text = '';
            if (contentType.includes('application/json')) {
                data = await res.json().catch(() => null);
            } else {
                text = await res.text().catch(() => '');
            }

            if (!res.ok) {
                const msg = (data && (data.message || data.error)) || text || `Error HTTP ${res.status}`;
                if (data && data.errors && typeof data.errors === 'object') {
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', html: Object.values(data.errors).flat().join('<br>'), confirmButtonColor: '#4f46e5' });
                } else {
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: msg, confirmButtonColor: '#4f46e5' });
                }
                btn.prop('disabled', false).html(txt);
                return;
            }

            Swal.fire({ icon: 'success', title: yaExiste ? 'Corte sobreescrito' : 'Corte guardado', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());

        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error', text: e?.message || 'Error inesperado', confirmButtonColor: '#4f46e5' });
            btn.prop('disabled', false).html(txt);
        }
    });

}); // fin $(function)

function decodeHtml(str) {
    const txt = document.createElement('textarea');
    txt.innerHTML = str;
    return txt.value;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
@endpush