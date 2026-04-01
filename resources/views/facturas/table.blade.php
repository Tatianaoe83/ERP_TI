<div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">

    <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
        <form id="formFilter" class="flex flex-col lg:flex-row items-end gap-4">
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

            <div class="w-full lg:w-auto">
                <button type="submit" class="w-full h-11 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <div id="gerenciaInfo" class="hidden p-4 bg-indigo-50 dark:bg-indigo-900/10 border-b border-indigo-100 dark:border-indigo-800 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-300 shrink-0">
            <i class="fas fa-building"></i>
        </div>
        <span id="titleGerencia" class="text-lg font-bold text-slate-800 dark:text-slate-100"></span>
    </div>

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
                    <th class="py-4 px-4 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center">Archivo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900"></tbody>
        </table>
    </div>

    <div id="modalReemplazoFactura" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/75 backdrop-blur-sm">
    <div class="relative bg-gray-50 dark:bg-slate-900 rounded-2xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-slate-700"
         style="box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35)">

        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center border border-indigo-100 dark:border-indigo-800/40">
                    <i class="fas fa-sync-alt text-indigo-500 dark:text-indigo-400 text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Reemplazar Archivos</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Actualiza el XML o PDF de esta factura</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalReemplazo()"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <form id="formReemplazarFactura" onsubmit="enviarReemplazoFactura(event)">
            <div class="p-5 space-y-4">
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <i class="fas fa-file-invoice text-slate-400 text-xs"></i>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Actualizando: <strong id="reemplazoFacturaNombre" class="text-slate-700 dark:text-slate-200 font-semibold"></strong>
                    </p>
                </div>
                <input type="hidden" id="reemplazoFacturaID">

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                        Subir XML <span class="text-slate-300 dark:text-slate-600 font-normal normal-case tracking-normal">(opcional)</span>
                    </label>
                    <input type="file" id="reemplazoXml" accept=".xml, application/xml, text/xml"
                        class="block w-full text-xs text-slate-500 dark:text-slate-400
                               file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                               file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600
                               dark:file:bg-indigo-900/30 dark:file:text-indigo-400
                               hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50
                               cursor-pointer border border-slate-200 dark:border-slate-700
                               rounded-xl bg-gray-50 dark:bg-slate-800 p-2 transition-colors">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                        Subir PDF <span class="text-slate-300 dark:text-slate-600 font-normal normal-case tracking-normal">(opcional)</span>
                    </label>
                    <input type="file" id="reemplazoPdf" accept=".pdf, application/pdf"
                        class="block w-full text-xs text-slate-500 dark:text-slate-400
                               file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                               file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-600
                               dark:file:bg-emerald-900/30 dark:file:text-emerald-400
                               hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50
                               cursor-pointer border border-slate-200 dark:border-slate-700
                               rounded-xl bg-gray-50 dark:bg-slate-800 p-2 transition-colors">
                </div>

                <div id="previewContenedor" class="hidden p-4 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800/40">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-2 flex items-center gap-1.5">
                        <i class="fas fa-check-circle text-xs"></i> Datos Extraídos
                    </p>
                    <div id="previewContenido" class="text-sm text-slate-600 dark:text-slate-300 space-y-1.5"></div>
                </div>
            </div>

            <div class="px-5 py-4 bg-gray-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <button type="button" onclick="cerrarModalReemplazo()"
                    class="h-10 px-5 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmitReemplazo"
                    class="h-10 inline-flex items-center gap-2 px-5 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white transition-colors"
                    style="box-shadow: 0 4px 12px rgba(99,102,241,0.3)">
                    <i class="fas fa-save text-xs"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
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
                url: '{{ route("facturas.ver") }}',
                method: 'GET',
                data: function(d) {
                    d.mes        = $('#mesFilter').val();
                    d.gerenci_id = $('#gerenci_id').val();
                    d.año        = $('#añoFilter').val();
                }
            },
        columns: [
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
            {
                data: 'Costo',
                className: 'px-4 py-3 border-b dark:border-slate-800 text-right',
                render: function(data) {
                    if (data === null || data === undefined) return '<span class="text-slate-400">—</span>';
                    return `<span class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2.5 py-1 rounded-lg">${currencyFmt.format(data)}</span>`;
                }
            },
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
            {
                data: 'Anio',
                className: 'px-4 py-3 border-b dark:border-slate-800',
                render: function(data) {
                    if (!data) return '<span class="text-slate-400 text-xs">—</span>';
                    return `<span class="text-sm font-semibold text-slate-600 dark:text-slate-300">${data}</span>`;
                }
            },
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
                                           bg-gray-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700
                                           text-slate-600 dark:text-slate-300 hover:border-violet-400 dark:hover:border-violet-500
                                           focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 shadow-sm"
                                style="min-width:160px" data-loaded="false" data-factura="${row.FacturasID}" data-solicitud="${row.SolicitudID}" data-valor="${valorActual}">
                                <option value="">— Asignar insumo —</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                            </div>
                        </div>
                    </div>`;
                }
            },
            {
                data: 'PdfRuta',
                className: 'px-4 py-3 border-b dark:border-slate-800 text-center align-middle',
                orderable: false,
                render: function(data, type, row) {
                    const tienePdf = row.PdfRuta ? true : false;
                    const tieneXml = row.ArchivoRuta ? true : false;
                    
                    let viewBtn = '';
                    if (tienePdf) {
                        viewBtn = `<a href="/storage/${row.PdfRuta}" target="_blank"
                            class="inline-flex items-center justify-center w-full gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/40 text-xs font-bold hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors" title="Ver PDF">
                            <i class="fas fa-file-pdf"></i> Ver PDF
                        </a>`;
                    } else if (tieneXml) {
                        viewBtn = `<a href="/storage/${row.ArchivoRuta}" target="_blank"
                            class="inline-flex items-center justify-center w-full gap-1.5 px-3 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-800/40 text-xs font-bold hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors" title="Ver XML">
                            <i class="fas fa-file-code"></i> Ver XML
                        </a>`;
                    } else {
                        viewBtn = `<span class="text-xs text-slate-400 italic block mb-1">Sin archivo</span>`;
                    }

                    const nombreFactura = row.Nombre ? row.Nombre.replace(/'/g, "\\'") : 'Factura';
                    const replaceBtn = `<button type="button" onclick="abrirModalReemplazo(${row.FacturasID}, '${nombreFactura}')" class="inline-flex items-center justify-center w-full mt-2 gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"><i class="fas fa-sync-alt"></i> Reemplazar</button>`;
                    return `<div class="flex flex-col items-center justify-center w-28 mx-auto">${viewBtn} ${replaceBtn}</div>`;
                }
            }
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
        }

        $sel.prop('disabled', false);
    });

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
            $sel.addClass('!border-red-500');
            setTimeout(() => $sel.removeClass('!border-red-500'), 1500);
        }

        $sel.prop('disabled', false);
    });

    async function loadPreview(url, formData) {
        $('#previewContenedor').removeClass('hidden');
        $('#previewContenido').html('<div class="flex items-center gap-2 text-indigo-500"><i class="fas fa-spinner fa-spin"></i> Leyendo documento...</div>');
        
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            
            const data = await res.json();
            
            if(!res.ok || data.error === true) {
                throw new Error(data.error && typeof data.error === 'string' ? data.error : 'No se pudo leer la información del documento.');
            }

            let html = '';
            if(data.emisor) html += `<div><strong class="font-semibold text-slate-800 dark:text-slate-200">Emisor:</strong> ${data.emisor}</div>`;
            if(data.total) html += `<div><strong class="font-semibold text-slate-800 dark:text-slate-200">Total (Sin IVA):</strong> $${parseFloat(data.total).toLocaleString('es-MX', {minimumFractionDigits:2})} ${data.moneda || 'MXN'}</div>`;
            if(data.uuid) html += `<div><strong class="font-semibold text-slate-800 dark:text-slate-200">UUID:</strong> <span class="font-mono text-xs">${data.uuid}</span></div>`;
            
            if(!html && data.conceptos) html += `<div><strong class="font-semibold text-slate-800 dark:text-slate-200">Conceptos:</strong> ${data.conceptos.length} detectados</div>`;

            $('#previewContenido').html(html || '<div class="text-slate-500 italic">No se detectaron datos relevantes en el archivo.</div>');
        } catch(e) {
            $('#previewContenido').html(`<div class="text-red-500 font-medium flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> ${e.message}</div>`);
        }
    }

    $('#reemplazoXml').on('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        const formData = new FormData();
        formData.append('xml', file);
        loadPreview('{{ route("facturas.parsearXml") }}', formData);
    });

    $('#reemplazoPdf').on('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        const formData = new FormData();
        formData.append('pdf', file);
        loadPreview('{{ route("facturas.previsualizarPdf") }}', formData);
    });

    window.abrirModalReemplazo = function(id, nombre) {
        $('#reemplazoFacturaID').val(id);
        $('#reemplazoFacturaNombre').text(nombre);
        $('#reemplazoXml').val('');
        $('#reemplazoPdf').val('');
        $('#previewContenedor').addClass('hidden');
        $('#previewContenido').html('');
        $('#modalReemplazoFactura').removeClass('hidden');
    };

    window.cerrarModalReemplazo = function() {
        $('#modalReemplazoFactura').addClass('hidden');
    };

    window.enviarReemplazoFactura = async function(e) {
        e.preventDefault();
        const facturaID = $('#reemplazoFacturaID').val();
        const fileXml = $('#reemplazoXml')[0].files[0];
        const filePdf = $('#reemplazoPdf')[0].files[0];

        if (!fileXml && !filePdf) {
            Swal.fire({ icon: 'warning', text: 'Selecciona al menos un archivo para subir.' });
            return;
        }

        const formData = new FormData();
        if (fileXml) formData.append('archivo_xml', fileXml);
        if (filePdf) formData.append('archivo_pdf', filePdf);
        formData.append('_token', '{{ csrf_token() }}');

        const btn = $('#btnSubmitReemplazo');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);

        try {
            const res = await fetch(`/facturas/${facturaID}/reemplazar-archivo`, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const json = await res.json();

            if (!res.ok) throw new Error(json.message || 'Error al subir el archivo');

            if (window.Swal) {
                Swal.fire({ icon: 'success', title: '¡Actualizado!', text: json.message, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
            }
            
            cerrarModalReemplazo();
            $('#facturasTable').DataTable().ajax.reload(null, false); 

        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: error.message });
        } finally {
            btn.html(originalText).prop('disabled', false);
        }
    };

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