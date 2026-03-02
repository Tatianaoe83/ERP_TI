@extends('layouts.app')

@section('content')
<style>
    /* Scrollbar minimalista */
    .custom-scroll::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }

    .custom-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 10px;
    }

    .dark .custom-scroll::-webkit-scrollbar-thumb {
        background-color: #475569;
    }
</style>

<div class="w-full mx-auto bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">

    <!-- Encabezado: título y descripción -->
    <div class="px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                    Generar Corte Anual
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Gestión visual de costos y variantes por gerencia.
                </p>
            </div>
        </div>
    </div>

    <!-- Filtros y acciones -->
    <div class="px-6 md:px-8 py-5 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/30">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">Filtros y acciones</p>
        <div class="flex flex-col lg:flex-row lg:items-end gap-4 lg:gap-6">
            <div class="flex flex-wrap items-end gap-4 flex-1">
                <div class="w-full sm:w-40 group">
                    <label for="anioCorte" class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 ml-1">
                        Año
                    </label>
                    <div class="relative">
                        <select name="anioCorte" id="anioCorte"
                            class="w-full h-11 pl-4 pr-10 appearance-none cursor-pointer rounded-xl outline-none transition-all duration-200
                                   bg-gray-50 dark:bg-slate-900 
                                   border-2 border-slate-200 dark:border-slate-700 
                                   text-slate-700 dark:text-slate-200 text-sm font-medium
                                   hover:border-indigo-400 dark:hover:border-indigo-500
                                   focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                            @foreach($years as $y)
                            <option value="{{ $y }}" {{ $y == $anioConsulta ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 transition-colors">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="w-full sm:flex-1 sm:min-w-[200px] sm:max-w-sm group">
                    <label for="gerenciaID" class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 ml-1">
                        Gerencia
                    </label>
                    <div class="relative">
                        <select name="gerenciaID" id="gerenciaID"
                            class="w-full h-11 pl-4 pr-10 appearance-none cursor-pointer rounded-xl outline-none transition-all duration-200
                                   bg-gray-50 dark:bg-slate-900 
                                   border-2 border-slate-200 dark:border-slate-700 
                                   text-slate-700 dark:text-slate-200 text-sm font-medium
                                   hover:border-indigo-400 dark:hover:border-indigo-500
                                   focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                            <option value="" disabled selected>Selecciona una opción...</option>
                            @foreach($gerencia as $g)
                            <option value="{{ $g->GerenciaID }}">{{ $g->NombreGerencia }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 dark:text-slate-500 group-hover:text-indigo-500 transition-colors">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
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
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">Estado por gerencia ({{ $anioConsulta }})</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 overflow-hidden min-h-[120px] flex flex-col">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20 shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Con corte</span>
                    <span class="ml-2 text-slate-500 dark:text-slate-400 text-sm">({{ count($gerenciasConCorte) }})</span>
                </div>
                <div class="p-3 flex-1 overflow-y-auto custom-scroll min-h-0">
                    @forelse($gerenciasConCorte as $g)
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-800/40 text-emerald-800 dark:text-emerald-200 text-xs font-medium mr-1.5 mb-1">{{ $g->NombreGerencia }}</span>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">Ninguna gerencia con corte en este año.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 overflow-hidden min-h-[120px] flex flex-col">
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

    <!-- Tabla de datos del corte -->
    <div class="px-6 md:px-8 py-5">
        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3 ml-1">Datos del corte</p>
    </div>
    <div class="relative w-full custom-scroll overflow-x-auto bg-gray-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
        <table id="tabla" class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insumo</th>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Monto Base</th>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center">Variantes</th>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center">Margen (%)</th>
                    <th class="py-4 px-5 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Precio Final</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900">
            </tbody>
        </table>

        <div id="tabla-placeholder" class="py-24 text-center bg-gray-50 dark:bg-slate-900 transition-colors">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-50 dark:bg-slate-800 mb-4">
                <i class="fas fa-search-dollar text-3xl text-slate-300 dark:text-slate-600"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-700 dark:text-slate-200">Esperando datos</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">Selecciona una gerencia arriba para comenzar.</p>
        </div>
        <!-- Botón Guardar se mueve aquí por JS (al pie de la tabla) -->
        <div id="guardar-en-footer" class="hidden">
            <button type="button" id="guardar"
                class="h-10 px-5 flex items-center justify-center gap-2 rounded-xl text-sm font-bold text-white shadow-lg shadow-emerald-500/20 dark:shadow-emerald-900/40 transition-all duration-200 
                       bg-emerald-600 hover:bg-emerald-500 hover:-translate-y-0.5 active:translate-y-0 active:shadow-md">
                <i class="fas fa-save"></i> <span>Guardar</span>
            </button>
        </div>
    </div>

    <!-- Modal: Corte guardado (preview, tabla responsive) -->
    <div id="bloque-corte-guardado" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="modal-corte-guardado-title">
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" id="modal-corte-guardado-backdrop"></div>
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-6xl max-h-[90vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="flex items-center justify-between shrink-0 px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                    <h2 id="modal-corte-guardado-title" class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-folder-open text-indigo-500"></i>
                        Corte guardado (solo lectura)
                    </h2>
                    <button type="button" id="cerrar-corte-guardado" class="rounded-lg p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition-colors" aria-label="Cerrar">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div class="flex-1 min-h-0 overflow-auto p-4 sm:p-6">
                    <div class="overflow-x-auto custom-scroll -mx-2 sm:mx-0">
                        <table id="tabla-guardados" class="w-full text-left border-collapse min-w-[700px]">
                            <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-3 px-3 sm:px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider sticky left-0 z-10 bg-slate-50 dark:bg-slate-950 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)]">Insumo</th>
                                    @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $m)
                                    <th class="py-3 px-2 sm:px-3 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right whitespace-nowrap">{{ $m }}</th>
                                    @endforeach
                                    <th class="py-3 px-3 sm:px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Costo</th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center">Margen (%)</th>
                                    <th class="py-3 px-3 sm:px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Costo + margen</th>
                                    <th class="py-3 px-3 sm:px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Costo total (año)</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-guardados-body" class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-4">Costo = base; Costo + margen = precio aplicado por mes; Costo total (año) = suma anual a considerar.</p>
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
    $(function() {
        const currencyFmt = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            maximumFractionDigits: 2
        });

        const table = $('#tabla').DataTable({
            destroy: true,
            responsive: true,
            searching: true,
            processing: true,
            serverSide: true,
            pageLength: 25,
            deferLoading: 0,
            dom: 'rt<"dt-corte-footer flex flex-col sm:flex-row justify-between items-center p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950"ip>',
            language: {
                zeroRecords: "<div class='py-8 text-center text-slate-400 dark:text-slate-500 italic'>Sin resultados</div>",
                info: "<span class='text-xs font-medium text-slate-500 dark:text-slate-400'>Viendo <span class='text-slate-800 dark:text-white font-bold'>_START_</span> - <span class='text-slate-800 dark:text-white font-bold'>_END_</span> de <span class='text-slate-800 dark:text-white font-bold'>_TOTAL_</span></span>",
                infoEmpty: "<span class='text-xs font-medium text-slate-400'>Sin registros</span>",
                paginate: {
                    first: '<<',
                    last: '>>',
                    next: '<i class="fas fa-chevron-right text-[10px]"></i>',
                    previous: '<i class="fas fa-chevron-left text-[10px]"></i>'
                }
            },
            ajax: {
                url: '{{ route("cortes.ver") }}',
                type: 'GET',
                data: function(d) {
                    d.gerenciaID = $('#gerenciaID').val();
                },
                dataSrc: function(json) {
                    $('#tabla-placeholder').hide();
                    if (!json || !Array.isArray(json.data)) return [];
                    return json.data.map(r => ({
                        NombreInsumo: r.NombreInsumo,
                        MontosPorMes: r.MontosPorMes || [],
                        Distintos: r.Distintos || [],
                        SelectedIndex: 0,
                        Margen: 0
                    }));
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                }
            },
            columns: [{
                    data: 'NombreInsumo',
                    className: 'p-4 align-middle text-sm font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800'
                },
                {
                    data: null,
                    className: 'p-4 align-middle text-right border-b border-slate-100 dark:border-slate-800',
                    render: function(row) {
                        const montos = row.Distintos || [];
                        const idx = Number(row.SelectedIndex) || 0;

                        if (montos.length <= 1) {
                            return `<span class="font-mono text-sm font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-transparent dark:border-slate-700">
                                        ${currencyFmt.format(Number(montos[0]||0))}
                                    </span>`;
                        }

                        const options = montos.map((m, i) => `<option value="${i}" ${i===idx?'selected':''}>${currencyFmt.format(Number(m)||0)}</option>`).join('');

                        return `
                        <div class="relative inline-block w-40 group/select">
                            <select class="monto-select w-full pl-3 pr-8 py-1.5 text-xs font-mono font-medium rounded-lg outline-none transition-all cursor-pointer appearance-none
                                           bg-gray-50 dark:bg-slate-900 
                                           border border-slate-200 dark:border-slate-700
                                           text-slate-700 dark:text-slate-200
                                           hover:border-indigo-400 dark:hover:border-indigo-500
                                           focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-sm">
                                ${options}
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400 dark:text-slate-500 group-hover/select:text-indigo-500">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                            </div>
                        </div>`;
                    }
                },
                {
                    data: 'Distintos',
                    className: 'p-4 align-middle text-center border-b border-slate-100 dark:border-slate-800',
                    orderable: false,
                    render: function(d) {
                        const c = d?.length || 0;
                        const isMulti = c > 1;
                        const bgClass = isMulti ?
                            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20' :
                            'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
                        const txt = isMulti ? `${c} Opciones` : 'Fijo';
                        return `<span class="px-2.5 py-1 rounded-md text-[10px] uppercase font-bold tracking-wide border ${bgClass}">${txt}</span>`;
                    }
                },
                {
                    data: 'Margen',
                    className: 'p-4 align-middle text-center border-b border-slate-100 dark:border-slate-800',
                    orderable: false,
                    render: function(margen) {
                        return `
                        <div class="flex justify-center">
                            <div class="relative flex items-center w-28 group/input transition-all duration-200 hover:-translate-y-0.5">
                                <input type="number" min="0" max="100" step="0.1" 
                                    class="margen-input w-full pl-3 pr-7 py-1.5 text-right text-sm font-mono font-medium rounded-lg outline-none transition-all shadow-sm
                                           bg-gray-50 dark:bg-slate-900 
                                           border border-slate-200 dark:border-slate-700
                                           text-slate-700 dark:text-slate-200
                                           group-hover/input:border-indigo-400 dark:group-hover/input:border-indigo-500
                                           focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" 
                                    value="${Number(margen)||0}">
                                <div class="absolute right-2.5 text-slate-400 dark:text-slate-500 text-xs font-bold pointer-events-none">%</div>
                            </div>
                        </div>`;
                    }
                },
                {
                    data: null,
                    className: 'p-4 align-middle text-right border-b border-slate-100 dark:border-slate-800',
                    render: function(row) {
                        const idx = Number(row.SelectedIndex) || 0;
                        const base = Number(row.Distintos?.[idx] ?? 0);
                        const final = base * (1 + ((Number(row.Margen) || 0) / 100));
                        return `<span class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-100 dark:border-emerald-500/20 shadow-sm precio-final">
                                    ${currencyFmt.format(final)}
                                </span>`;
                    }
                }
            ],
            drawCallback: function() {
                const btnClass = 'px-3 py-1.5 ml-1.5 rounded-lg border text-xs font-semibold transition-all duration-200 cursor-pointer shadow-sm ';
                const normal = 'bg-gray-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-indigo-600 dark:hover:text-indigo-400';
                const active = '!bg-indigo-600 !border-indigo-600 !text-white shadow-indigo-500/30 hover:!bg-indigo-700';
                const disabled = 'opacity-40 cursor-not-allowed shadow-none';
                $('.dataTables_paginate .paginate_button').addClass(btnClass + normal);
                $('.dataTables_paginate .paginate_button.current').removeClass(normal).addClass(active);
                $('.dataTables_paginate .paginate_button.disabled').addClass(disabled);
            },
            initComplete: function() {
                var $footer = $('.dt-corte-footer');
                if ($footer.length && $('#guardar').length) {
                    $('#guardar').appendTo($footer).removeClass('hidden').show();
                }
            }
        });

        $('#tabla tbody').on('change', '.monto-select', function() {
            const $tr = $(this).closest('tr');
            const row = table.row($tr);
            const data = row.data();
            const idx = Number($(this).val()) || 0;

            data.SelectedIndex = idx;

            const margen = Number(data.Margen) || 0;
            const base = Number(data.Distintos?.[idx] ?? 0);
            const final = base * (1 + (margen / 100));

            $tr.find('.precio-final').text(currencyFmt.format(final));
        });

        $('#tabla tbody').on('change', '.margen-input', function() {
            const $tr = $(this).closest('tr');
            const row = table.row($tr);
            const data = row.data();
            let num = parseFloat($(this).val()) || 0;

            if (num < 0) num = 0;
            if (num > 100) num = 100;
            $(this).val(num);

            data.Margen = num;

            const idx = Number(data.SelectedIndex) || 0;
            const base = Number(data.Distintos?.[idx] ?? 0);
            const final = base * (1 + (num / 100));

            $tr.find('.precio-final').text(currencyFmt.format(final));
        });

        $('#enviar').on('click', function() {
            if (!$('#gerenciaID').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Selecciona una gerencia',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }
            table.ajax.reload(null, false);
        });

        $('#anioCorte').on('change', function() {
            var anio = $(this).val();
            if (anio) {
                window.location.href = '{{ route("cortes.index") }}?anio=' + anio;
            }
        });

        const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $('#verGuardado').on('click', async function() {
            const gid = $('#gerenciaID').val();
            const anio = $('#anioCorte').val();
            if (!gid) {
                Swal.fire({ icon: 'warning', title: 'Atención', text: 'Selecciona una gerencia', confirmButtonColor: '#4f46e5' });
                return;
            }
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando...');
            try {
                const res = await fetch('{{ route("cortes.guardados") }}?anio=' + encodeURIComponent(anio) + '&gerenciaID=' + encodeURIComponent(gid), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json().catch(() => ({}));
                const data = json.data || [];
                const tbody = $('#tabla-guardados-body');
                tbody.empty();
                if (data.length === 0) {
                    tbody.append('<tr><td colspan="17" class="py-8 text-center text-slate-500 dark:text-slate-400">No hay corte guardado para esta gerencia y año.</td></tr>');
                } else {
                    data.forEach(function(row) {
                        const meses = row.Meses || {};
                        let costoMasMargenSum = 0;
                        let costoMasMargenCount = 0;
                        const celdasMes = MESES.map(function(m) {
                            const v = (meses[m] && meses[m].CostoTotal) ? Number(meses[m].CostoTotal) : 0;
                            if (v > 0) { costoMasMargenSum += v; costoMasMargenCount++; }
                            return '<td class="py-2 px-3 text-right text-sm font-mono text-slate-700 dark:text-slate-300">' + (v > 0 ? currencyFmt.format(v) : '—') + '</td>';
                        }).join('');
                        const costoBase = Number(row.Costo) || 0;
                        const margen = Number(row.Margen) || 0;
                        const costoMasMargenProm = costoMasMargenCount > 0 ? costoMasMargenSum / costoMasMargenCount : 0;
                        const costoTotalAnual = Number(row.CostoTotalAnual) || 0;
                        const tr = '<tr class="bg-gray-50 dark:bg-slate-900/50 hover:bg-slate-50 dark:hover:bg-slate-800/50">' +
                            '<td class="py-2 px-3 sm:px-4 text-sm font-semibold text-slate-800 dark:text-white sticky left-0 z-[1] bg-gray-50 dark:bg-slate-900 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">' + escapeHtml(row.NombreInsumo || '') + '</td>' +
                            celdasMes +
                            '<td class="py-2 px-4 text-right text-sm font-mono text-slate-600 dark:text-slate-300">' + (costoBase > 0 ? currencyFmt.format(costoBase) : '—') + '</td>' +
                            '<td class="py-2 px-4 text-center text-sm font-mono text-slate-600 dark:text-slate-300">' + (margen > 0 ? margen + '%' : '—') + '</td>' +
                            '<td class="py-2 px-4 text-right text-sm font-mono text-emerald-600 dark:text-emerald-400">' + (costoMasMargenProm > 0 ? currencyFmt.format(costoMasMargenProm) : '—') + '</td>' +
                            '<td class="py-2 px-4 text-right text-sm font-mono font-bold text-slate-800 dark:text-white">' + (costoTotalAnual > 0 ? currencyFmt.format(costoTotalAnual) : '—') + '</td>' +
                            '</tr>';
                        tbody.append(tr);
                    });
                }
                $('#bloque-corte-guardado').removeClass('hidden');
                document.body.classList.add('overflow-hidden');
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e && e.message ? e.message : 'No se pudo cargar el corte guardado', confirmButtonColor: '#4f46e5' });
            }
            $btn.prop('disabled', false).html('<i class="fas fa-folder-open"></i> <span>Ver corte guardado</span>');
        });

        $('#cerrar-corte-guardado').on('click', function() {
            $('#bloque-corte-guardado').addClass('hidden');
            document.body.classList.remove('overflow-hidden');
        });

        $('#modal-corte-guardado-backdrop').on('click', function() {
            $('#bloque-corte-guardado').addClass('hidden');
            document.body.classList.remove('overflow-hidden');
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$('#bloque-corte-guardado').hasClass('hidden')) {
                $('#bloque-corte-guardado').addClass('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        $('#guardar').on('click', async function() {
            const gid = $('#gerenciaID').val();
            if (!gid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Selecciona una gerencia primero',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            const rows = table.rows({
                search: 'applied'
            }).data().toArray();
            const payload = [];

            $('#tabla tbody tr').each(function(i) {
                const row = table.row(this).data();
                if (!row) return;

                const $select = $(this).find('.monto-select');
                const $input = $(this).find('.margen-input');
                const margenVal = $input.length ? (Number($input.val()) || 0) : (Number(row.Margen) || 0);

                const montos = row.MontosPorMes || [];

                for (const mp of montos) {
                    if (Number(mp.Costo) > 0) {
                        const costoTotal = +(Number(mp.Costo) * (1 + margenVal / 100)).toFixed(2);
                        payload.push({
                            NombreInsumo: decodeHtml(String(row.NombreInsumo || 'SIN_NOMBRE')),
                            Mes: mp.Mes,
                            Costo: +Number(mp.Costo).toFixed(2),
                            Margen: +margenVal.toFixed(2),
                            CostoTotal: costoTotal,
                            GerenciaID: Number(gid)
                        });
                    }
                }
            });

            if (!payload.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin datos',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            const btn = $(this);
            const txt = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            try {
                const res = await fetch(`{{ route('cortes.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': `{{ csrf_token() }}`,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        rows: payload,
                        anio: parseInt($('#anioCorte').val(), 10) || new Date().getFullYear()
                    })
                });

                const contentType = res.headers.get('content-type') || '';
                let data = null;
                let text = '';

                if (contentType.includes('application/json')) {
                    data = await res.json().catch(() => null);
                } else {
                    text = await res.text().catch(() => '');
                }

                if (!res.ok) {
                    const msg =
                        (data && (data.message || data.error)) ||
                        text ||
                        `Error HTTP ${res.status}`;

                    if (data && data.errors && typeof data.errors === 'object') {
                        const lines = Object.values(data.errors).flat().join('<br>');
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo guardar',
                            html: lines,
                            confirmButtonColor: '#4f46e5'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo guardar',
                            text: msg,
                            confirmButtonColor: '#4f46e5'
                        });
                    }

                    btn.prop('disabled', false).html(txt);
                    return;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());

            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: e?.message || 'Error inesperado',
                    confirmButtonColor: '#4f46e5'
                });
                btn.prop('disabled', false).html(txt);
            }

        });
    });

    function decodeHtml(str) {
        const txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
    }
</script>
@endpush