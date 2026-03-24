<div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">

    {{-- Filtros --}}
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
        <form id="formFilter" class="flex flex-col lg:flex-row items-end gap-4">

            {{-- Gerencia --}}
            <div class="w-full lg:w-1/3">
                <label class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Gerencia</label>
                <div class="relative">
                    {!! Form::select('gerenci_id', $gerencia, null, [
                        'class' => 'w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all',
                        'id' => 'gerenci_id'
                    ]) !!}
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fas fa-chevron-down text-xs"></i></div>
                </div>
            </div>

            {{-- Mes --}}
            <div class="w-full lg:w-1/5">
                <label class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Mes</label>
                <div class="relative">
                    <select id="mesFilter" class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                        <option value="">Todos los meses</option>
                        @foreach($meses as $num => $nombre)
                            <option value="{{ $num }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fas fa-calendar-alt text-xs"></i></div>
                </div>
            </div>

            {{-- Año --}}
            <div class="w-full lg:w-1/5">
                <label class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Año</label>
                <div class="relative">
                    <select id="añoFilter" class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                        <option value="">Todos los años</option>
                        @foreach($years as $año)
                            <option value="{{ $año }}">{{ $año }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fas fa-calendar text-xs"></i></div>
                </div>
            </div>

            {{-- Botón Filtrar --}}
            <div class="w-full lg:w-auto">
                <button type="submit" class="w-full h-11 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Barra de gerencia seleccionada --}}
    <div id="gerenciaInfo" class="hidden p-4 bg-indigo-50 dark:bg-indigo-900/10 border-b border-indigo-100 dark:border-indigo-800 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-300 shrink-0">
            <i class="fas fa-building"></i>
        </div>
        <span id="titleGerencia" class="text-lg font-bold text-slate-800 dark:text-slate-100"></span>
    </div>

    {{-- Tabla --}}
    <div class="w-full overflow-x-auto">
        <table id="facturasTable" class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-700">
                <tr>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Nombre</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Solicitud</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Gerencia</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Costo</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Mes</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Año</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insumo</th>
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center">PDF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900"></tbody>
        </table>
    </div>

</div>

@push('facturas_scripts')
<script>
$(document).ready(function() {
    const currencyFmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    const mesesNombres = {
        1:'Enero', 2:'Febrero', 3:'Marzo', 4:'Abril',
        5:'Mayo', 6:'Junio', 7:'Julio', 8:'Agosto',
        9:'Septiembre', 10:'Octubre', 11:'Noviembre', 12:'Diciembre'
    };

    const insumoCache = {};

    const table = $('#facturasTable').DataTable({
        destroy: true,
        responsive: true,
        searching: true,
        processing: true,
        serverSide: true,
        pageLength: 12,
        dom: 'rt<"flex flex-col sm:flex-row justify-between items-center p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900"ip>',
        language: {
            zeroRecords: "<div class='py-10 text-center text-slate-400 italic'>No hay facturas con los filtros seleccionados</div>",
            info:        "<span class='text-xs font-medium text-slate-500'>_START_ - _END_ de _TOTAL_</span>",
            infoEmpty:   "0 registros",
            processing:  "<span class='text-xs text-indigo-500 font-medium'>Cargando...</span>",
            paginate:    { first: '<<', last: '>>', next: '>', previous: '<' }
        },
        ajax: {
                url: '{{ route("facturas.ver") }}', // Asegúrate de que esta ruta apunte a indexVista
                method: 'GET',
                data: function(d) {
                    d.mes        = $('#mesFilter').val();
                    d.gerenci_id = $('#gerenci_id').val();
                    d.año        = $('#añoFilter').val();
                }
                // ELIMINA la línea de: dataSrc: 'data'
            },
        columns: [
            // Nombre
            {
                data: 'Nombre',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                render: function(data) {
                    if (data === null || data === undefined) return '<span class="text-slate-400">—</span>';
                    
                    const strData = String(data);
                    const short = strData.length > 50 ? strData.substring(0, 50) + '…' : strData;
                    return `<span class="text-sm font-semibold text-slate-800 dark:text-slate-100" title="${strData}">${short}</span>`;
                }
            },
            // Solicitud
            {
                data: 'SolicitudID',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                render: function(data) {
                    if (!data) return '<span class="text-slate-400">—</span>';
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-100 dark:border-indigo-800/50">
                        <i class="fas fa-hashtag text-[10px]"></i>${data}
                    </span>`;
                }
            },
            // Gerencia
            {
                data: 'NombreGerencia',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                render: function(data) {
                    if (!data) return '<span class="text-slate-400 text-xs">—</span>';
                    return `<span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 dark:text-slate-300">
                        <i class="fas fa-building text-slate-400 text-[10px]"></i>${data}
                    </span>`;
                }
            },
            // Costo
            {
                data: 'Costo',
                className: 'px-4 py-3 border-b dark:border-slate-800 text-right',
                render: function(data) {
                    if (data === null || data === undefined) return '<span class="text-slate-400">—</span>';
                    return `<span class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2.5 py-1 rounded-lg">${currencyFmt.format(data)}</span>`;
                }
            },
            // Mes
            {
                data: 'Mes',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                render: function(data) {
                    if (!data) return '<span class="text-slate-400 text-xs">—</span>';
                    const nombre = mesesNombres[parseInt(data)] ?? data;
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 text-xs font-semibold border border-sky-100 dark:border-sky-800/40">
                        <i class="fas fa-calendar-alt text-[10px]"></i>${nombre}
                    </span>`;
                }
            },
            // Año
            {
                data: 'Anio',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                render: function(data) {
                    if (!data) return '<span class="text-slate-400 text-xs">—</span>';
                    return `<span class="text-sm font-semibold text-slate-600 dark:text-slate-300">${data}</span>`;
                }
            },
            // Insumo — select editable poblado desde cortes
            {
                data: 'InsumoNombre',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                orderable: false,
                render: function(val, type, row) {
                    const valorActual = val ? val.replace(/"/g, '&quot;') : '';
                    const labelActual = val
                        ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 border border-violet-100 dark:border-violet-800/40">
                               <i class="fas fa-tag text-[9px]"></i>${val}
                           </span>`
                        : '';

                    return `
                    <div class="insumo-select-wrap relative" data-factura="${row.FacturasID}" data-solicitud="${row.SolicitudID}" data-valor="${valorActual}">
                        <div class="insumo-label mb-1">${labelActual}</div>
                        <div class="relative inline-block">
                            <select class="insumo-select pl-3 pr-8 py-1.5 text-xs font-medium rounded-lg outline-none transition-all cursor-pointer appearance-none
                                           bg-gray-50 dark:bg-slate-900
                                           border border-slate-200 dark:border-slate-700
                                           text-slate-600 dark:text-slate-300
                                           hover:border-violet-400 dark:hover:border-violet-500
                                           focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 shadow-sm"
                                    style="min-width:160px"
                                    data-loaded="false"
                                    data-factura="${row.FacturasID}"
                                    data-solicitud="${row.SolicitudID}"
                                    data-valor="${valorActual}">
                                <option value="">— Asignar insumo —</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                            </div>
                        </div>
                    </div>`;
                }
            },
            // PDF
            {
                data: 'PdfRuta',
                className: 'px-4 py-3 border-b dark:border-slate-800 text-center',
                orderable: false,
                render: function(data) {
                    if (!data) return `<span class="text-xs text-slate-300 dark:text-slate-600 italic">Sin PDF</span>`;
                    const url = '/storage/' + data;
                    return `<a href="${url}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/40 text-xs font-bold hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>`;
                }
            },
        ],
        drawCallback: function() {
            const btnClass = 'px-3 py-1.5 ml-1.5 rounded-lg border text-xs font-semibold transition-all duration-200 cursor-pointer shadow-sm ';
            const normal   = 'bg-gray-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700';
            const active   = '!bg-indigo-600 !border-indigo-600 !text-white hover:!bg-indigo-700';
            const disabled = 'opacity-40 cursor-not-allowed shadow-none';

            $('.dataTables_paginate .paginate_button').addClass(btnClass + normal);
            $('.dataTables_paginate .paginate_button.current').removeClass(normal).addClass(active);
            $('.dataTables_paginate .paginate_button.disabled').addClass(disabled);
        }
    });

    $('#facturasTable').on('focus', '.insumo-select', async function() {
        const $sel     = $(this);
        const solID    = $sel.data('solicitud');
        const valorAct = $sel.data('valor') || '';

        if ($sel.data('loaded') === true || $sel.data('loaded') === 'true') return;
        $sel.data('loaded', true);

        $sel.empty().append('<option>Cargando...</option>').prop('disabled', true);

        try {
            let insumos = insumoCache[solID];

            if (!insumos) {
                const res  = await fetch(
                    `{{ route('facturas.insumosPorGerencia') }}?solicitudID=${encodeURIComponent(solID)}`,
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const json = await res.json();
                insumos    = (json && json.data) ? json.data : [];
                insumoCache[solID] = insumos;
            }

            $sel.empty().append('<option value="">— Sin asignar —</option>');

            insumos.forEach(nombre => {
                const selected = nombre === valorAct ? 'selected' : '';
                $sel.append(`<option value="${nombre}" ${selected}>${nombre}</option>`);
            });

            if (insumos.length === 0) {
                $sel.append('<option value="" disabled>Sin insumos en corte</option>');
            }

        } catch (e) {
            $sel.empty().append('<option value="" disabled>Error al cargar</option>');
            console.error('Error cargando insumos:', e);
        }

        $sel.prop('disabled', false);
    });

    // Guardar al cambiar el select
    $('#facturasTable').on('change', '.insumo-select', async function() {
        const $sel      = $(this);
        const $wrap     = $sel.closest('.insumo-select-wrap');
        const facturaID = $sel.data('factura');
        const nombre    = $sel.val();

        $sel.prop('disabled', true);

        try {
            const res = await fetch(`/facturas/${facturaID}/insumo`, {
                method:  'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept':       'application/json'
                },
                body: JSON.stringify({ InsumoNombre: nombre })
            });

            if (!res.ok) throw new Error('Error HTTP ' + res.status);

            const $label = $wrap.find('.insumo-label');
            if (nombre) {
                $label.html(`
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 border border-violet-100 dark:border-violet-800/40">
                        <i class="fas fa-tag text-[9px]"></i>${nombre}
                    </span>`);
            } else {
                $label.html('');
            }

            $sel.data('valor', nombre);
            $sel.addClass('!border-emerald-500');
            setTimeout(() => $sel.removeClass('!border-emerald-500'), 1200);

        } catch (e) {
            console.error('Error guardando insumo:', e);
            $sel.addClass('!border-red-500');
            setTimeout(() => $sel.removeClass('!border-red-500'), 1500);
        }

        $sel.prop('disabled', false);
    });

    // Filtrar al submit
    $('#formFilter').on('submit', function(e) {
        e.preventDefault();
        const gerencia = $('#gerenci_id option:selected').text();
        if (gerencia && gerencia !== 'Selecciona una opción') {
            $('#titleGerencia').text(gerencia);
            $('#gerenciaInfo').removeClass('hidden').addClass('flex');
        } else {
            $('#gerenciaInfo').addClass('hidden').removeClass('flex');
        }
        table.ajax.reload();
    });
});
</script>
@endpush