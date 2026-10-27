<div class="index-page__card overflow-hidden">

    <div id="gerenciaInfo" class="hidden p-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
        <div class="index-page__icon">
            <i class="fas fa-building"></i>
        </div>
        <span id="titleGerencia" class="text-lg font-bold text-slate-800 dark:text-slate-100"></span>
    </div>

    <div class="index-page__table-wrap table-responsive">
        <table id="facturasTable" class="table index-table w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Insumo</th>
                    <th>Emisor</th>
                    <th>Solicitud</th>
                    <th>Presupuesto interno</th>
                    <th class="text-right">Total</th>
                    <th>Mes</th>
                    <th>Año</th>
                    <th>Último Cambio</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="modalReemplazoFactura" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
        <div class="relative bg-gray-50 dark:bg-slate-900 rounded-2xl w-full max-w-3xl mx-4 overflow-hidden border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh]"
            style="box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5)">

            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between shrink-0 bg-gray-50 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center border border-indigo-200 dark:border-indigo-800/40">
                        <i class="fas fa-file-invoice text-indigo-600 dark:text-indigo-400 text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Actualizar Factura</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sube un documento o edita los datos manualmente</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalReemplazo()"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition-all">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 pb-32">
                <form id="formReemplazarFactura" onsubmit="enviarReemplazoFactura(event)" class="flex flex-col space-y-6 w-full">
                    
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Factura actual:</p>
                        <p id="reemplazoFacturaNombre" class="text-sm text-slate-800 dark:text-slate-200 font-bold truncate"></p>
                    </div>
                    <input type="hidden" id="reemplazoFacturaID">

                    <div id="reemplazoVistaWrap" class="hidden">
                        <p id="reemplazoVistaTitulo" class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Archivo actual</p>
                        <iframe id="reemplazoVista" title="Archivo de la factura" class="w-full h-64 rounded-xl border border-slate-200 dark:border-slate-700 bg-white"></iframe>
                    </div>

                    <div>
                        <label id="reemplazoEtiquetaArchivo" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-4">
                            <i class="fas fa-file-upload text-indigo-500 mr-1.5"></i> Reemplazar archivo <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        
                        <div class="space-y-4">
                            <div id="reemplazoZonaXml" class="flex flex-col gap-1.5">
                                <label class="inline-flex items-center w-max px-2.5 py-1 rounded-md bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 text-[10px] font-bold border border-violet-200 dark:border-violet-700">
                                    <i class="fas fa-file-code mr-1"></i> XML
                                </label>
                                <input type="file" id="reemplazoXml" accept=".xml, application/xml, text/xml"
                                    class="block w-full text-xs text-slate-600 dark:text-slate-300
                                           file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0
                                           file:text-xs file:font-bold file:bg-violet-200 file:text-violet-800
                                           dark:file:bg-violet-800 dark:file:text-violet-200
                                           hover:file:bg-violet-300 dark:hover:file:bg-violet-700
                                           cursor-pointer border-2 border-violet-200 dark:border-violet-800/60
                                           rounded-xl bg-gray-50 dark:bg-slate-900 p-2 transition-all
                                           focus:outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-500/20">
                            </div>

                            <div id="reemplazoZonaPdf" class="flex flex-col gap-1.5">
                                <label class="inline-flex items-center w-max px-2.5 py-1 rounded-md bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 text-[10px] font-bold border border-rose-200 dark:border-rose-700">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </label>
                                <input type="file" id="reemplazoPdf" accept=".pdf, application/pdf"
                                    class="block w-full text-xs text-slate-600 dark:text-slate-300
                                           file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0
                                           file:text-xs file:font-bold file:bg-rose-200 file:text-rose-800
                                           dark:file:bg-rose-800 dark:file:text-rose-200
                                           hover:file:bg-rose-300 dark:hover:file:bg-rose-700
                                           cursor-pointer border-2 border-rose-200 dark:border-rose-800/60
                                           rounded-xl bg-gray-50 dark:bg-slate-900 p-2 transition-all
                                           focus:outline-none focus:border-rose-400 focus:ring-4 focus:ring-rose-500/20">
                            </div>
                        </div>
                    </div>

                    <div id="previewContenedor" class="hidden p-5 rounded-xl bg-indigo-50/80 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-3 flex items-center gap-1.5">
                            <i class="fas fa-eye"></i> Datos Detectados
                        </p>
                        <div id="previewContenido" class="text-sm text-slate-700 dark:text-slate-300 space-y-2"></div>
                    </div>

                    <hr class="border-slate-200 dark:border-slate-700">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fas fa-building text-indigo-500 mr-1.5"></i> Emisor
                        </label>
                        <input type="text" id="reemplazoEmisor" 
                            class="w-full h-11 px-4 text-sm font-medium border-2 border-slate-200 dark:border-slate-700 rounded-xl bg-gray-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all"
                            placeholder="Nombre del emisor">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fas fa-dollar-sign text-green-500 mr-1.5"></i> Costo (Subtotal)
                        </label>
                        <input type="number" id="reemplazoCosto" step="0.01" min="0"
                            class="w-full h-11 px-4 text-sm font-medium border-2 border-slate-200 dark:border-slate-700 rounded-xl bg-gray-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all"
                            placeholder="0.00">
                    </div>

                </form>
            </div>

            <div class="px-5 py-4 bg-gray-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="cerrarModalReemplazo()"
                    class="h-10 px-5 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" form="formReemplazarFactura" id="btnSubmitReemplazo"
                    class="h-10 inline-flex items-center gap-2 px-6 text-sm font-bold rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white transition-all hover:-translate-y-0.5"
                    style="box-shadow: 0 4px 12px rgba(99,102,241,0.25)">
                    <i class="fas fa-save text-xs"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <div id="modalVistaFactura" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
        <div class="relative bg-gray-50 dark:bg-slate-900 rounded-2xl w-full max-w-5xl mx-4 overflow-hidden border border-slate-200 dark:border-slate-700 flex flex-col" style="height:min(88vh,900px);box-shadow:0 25px 50px -12px rgba(0,0,0,0.5)">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between shrink-0">
                <div>
                    <h3 id="vistaFacturaTitulo" class="text-sm font-bold text-slate-800 dark:text-slate-100">Factura</h3>
                    <p id="vistaFacturaSub" class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5"></p>
                </div>
                <button type="button" onclick="cerrarVistaFactura()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition-all">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <iframe id="vistaFacturaFrame" title="Vista de la factura" class="flex-1 w-full bg-white"></iframe>
        </div>
    </div>
</div>

@push('facturas_scripts')
<script>
    $(document).ready(function() {
        const currencyFmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

        const mesesNombres = {
            1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril', 5: 'Mayo', 6: 'Junio',
            7: 'Julio', 8: 'Agosto', 9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre'
        };

        function _escHtml(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        async function confirmarCambioSeguroFactura(opts) {
            const { titulo, detalleHtml, textoPlano } = opts;
            if (window.Swal && typeof Swal.fire === 'function') {
                const r = await Swal.fire({
                    icon: 'question',
                    title: titulo,
                    html: detalleHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar',
                    focusCancel: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#64748b'
                });
                return !!r.isConfirmed;
            }
            return window.confirm(textoPlano || titulo);
        }

        const insumoCache = {};
        window.currentModalInsumos = [];

        const table = $('#facturasTable').DataTable({
            destroy: true,
            responsive: true,
            searching: true,
            processing: false,
            serverSide: true,
            pageLength: 10,
            dom: "<'index-page__dt-toolbar'f>t<'index-page__dt-footer'ip>",
            language: {
                zeroRecords: "<div class='py-10 text-center text-slate-400 italic'>No hay facturas con los filtros seleccionados</div>",
                info: "<span class='text-xs font-medium text-slate-500'>_START_ - _END_ de _TOTAL_</span>",
                infoEmpty: "0 registros",
                paginate: { first: '<<', last: '>>', next: '>', previous: '<' }
            },
            ajax: {
                url: '{{ route("facturas.ver") }}',
                method: 'GET',
                data: function(d) {
                    d.mes = $('#mesFilter').val();
                    d.gerenci_id = $('#gerenci_id').val();
                    d.año = $('#añoFilter').val();
                }
            },
            columns: [{
                    data: 'FacturasID',
                    className: 'px-4 py-3 border-b dark:border-slate-800 whitespace-nowrap',
                    render: function(data) {
                        return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px] font-extrabold border border-slate-200 dark:border-slate-700">
                        <i class="fas fa-hashtag text-[10px]"></i>${data}
                    </span>`;
                    }
                },
                {
                    data: 'InsumoNombre',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    orderable: false,
                    render: function(val, type, row) {
                        const valorActual = val ? val.replace(/"/g, '&quot;') : '';
                        
                        return `
                    <div class="insumo-select-wrap relative" data-factura="${row.FacturasID}" data-valor="${valorActual}">
                        <select class="insumo-select pr-9 pl-3 py-2 text-xs font-medium rounded-lg outline-none transition-all cursor-pointer appearance-none
                                       bg-gray-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700
                                       text-slate-700 dark:text-slate-200 hover:border-violet-400 dark:hover:border-violet-500
                                       focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 shadow-sm
                                       disabled:opacity-50 disabled:cursor-not-allowed"
                            data-loaded="false" data-factura="${row.FacturasID}" data-valor="${valorActual}">
                            ${val ? `<option value="${valorActual}">${valorActual.length > 32 ? valorActual.substring(0, 32) + '...' : valorActual}</option>` : '<option value="">— Sin asignar —</option>'}
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-all chevron-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <svg class="w-3.5 h-3.5 text-violet-500 animate-spin hidden loading-icon" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>`;
                    }
                },
                {
                    data: 'Emisor',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    render: function(data) {
                        if (data === null || data === undefined) return '<span class="text-slate-400">—</span>';
                        const strData = String(data);
                        const short = strData.length > 50 ? strData.substring(0, 50) + '…' : strData;
                        return `<span class="text-sm font-bold text-slate-800 dark:text-slate-100" title="${strData.replace(/"/g, '&quot;')}">${short}</span>`;
                    }
                },
                {
                    data: 'SolicitudID',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    render: function(data) {
                        if (!data) return '<span class="text-slate-400">—</span>';
                        return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-extrabold border border-indigo-200 dark:border-indigo-800/50">
                        <i class="fas fa-hashtag text-[10px]"></i>${data}
                    </span>`;
                    }
                },
                {
                    data: 'NombreGerencia',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    render: function(data) {
                        if (!data) return '<span class="text-slate-400 text-xs">—</span>';
                        return `<span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <i class="fas fa-building text-slate-400 text-[10px]"></i>${data}
                    </span>`;
                    }
                },
                {
                    data: 'Costo',
                    className: 'px-4 py-3 border-b dark:border-slate-800 text-right',
                    render: function(data) {
                        if (data === null || data === undefined) return '<span class="text-slate-400">—</span>';
                        return `<span class="font-mono text-sm font-extrabold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800/50 px-2.5 py-1 rounded-lg">${currencyFmt.format(data)}</span>`;
                    }
                },
                {
                    data: 'Mes',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                    <div class="mes-select-wrap relative" data-factura="${row.FacturasID}">
                        <select class="mes-select pl-3 pr-8 py-1.5 text-xs font-semibold rounded-lg outline-none transition-all cursor-pointer appearance-none
                                       bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                                       text-slate-700 dark:text-slate-200 hover:border-sky-400 dark:hover:border-sky-500
                                       focus:border-sky-500 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 shadow-sm"
                            data-factura="${row.FacturasID}">
                            <option value="1" ${data == 1 ? 'selected' : ''}>Enero</option>
                            <option value="2" ${data == 2 ? 'selected' : ''}>Febrero</option>
                            <option value="3" ${data == 3 ? 'selected' : ''}>Marzo</option>
                            <option value="4" ${data == 4 ? 'selected' : ''}>Abril</option>
                            <option value="5" ${data == 5 ? 'selected' : ''}>Mayo</option>
                            <option value="6" ${data == 6 ? 'selected' : ''}>Junio</option>
                            <option value="7" ${data == 7 ? 'selected' : ''}>Julio</option>
                            <option value="8" ${data == 8 ? 'selected' : ''}>Agosto</option>
                            <option value="9" ${data == 9 ? 'selected' : ''}>Septiembre</option>
                            <option value="10" ${data == 10 ? 'selected' : ''}>Octubre</option>
                            <option value="11" ${data == 11 ? 'selected' : ''}>Noviembre</option>
                            <option value="12" ${data == 12 ? 'selected' : ''}>Diciembre</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                            <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                    </div>`;
                    }
                },
                {
                    data: 'Anio',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    render: function(data) {
                        if (!data) return '<span class="text-slate-400 text-xs">—</span>';
                        return `<span class="text-sm font-bold text-slate-700 dark:text-slate-300">${data}</span>`;
                    }
                },
                {
                    data: 'NombreEmpleado',
                    className: 'px-4 py-3 border-b dark:border-slate-800',
                    render: function(data) {
                        if (!data || data.trim() === '') {
                            return '<span class="text-slate-400 dark:text-slate-500 italic text-xs">Sin cambios</span>';
                        }
                        const partes = data.trim().split(/\s+/);
                        let nombre = '';
                        
                        if (partes.length >= 3) {
                            // Formato: APELLIDO APELLIDO NOMBRE NOMBRE... -> mostrar NOMBRE APELLIDO
                            nombre = partes[2] + ' ' + partes[0];
                        } else if (partes.length === 2) {
                            nombre = partes[1] + ' ' + partes[0];
                        } else {
                            nombre = partes[0];
                        }
                        
                        return `<span class="text-xs font-semibold text-slate-700 dark:text-slate-300">${nombre}</span>`;
                    }
                },
                {
                    data: 'PdfRuta',
                    className: 'px-4 py-3 border-b dark:border-slate-800 whitespace-nowrap',
                    orderable: false,
                    render: function(data, type, row) {
                        const tipoArchivo = row.PdfRuta ? 'pdf' : (row.ArchivoRuta ? 'xml' : '');
                        const fileUrl = row.PdfRuta || row.ArchivoRuta || '';
                        const insumoStr = row.InsumoNombre ? row.InsumoNombre.replace(/[\r\n]+/g, ' ').replace(/'/g, "\\'").replace(/"/g, '&quot;') : '';
                        const solID = row.SolicitudID || 'null';
                        const nombreFactura = row.Emisor ? row.Emisor.replace(/[\r\n]+/g, ' ').replace(/'/g, "\\'").replace(/"/g, '&quot;') : 'Factura';

                        let actions = '<div class="index-actions">';

                        if (tipoArchivo) {
                            actions += `<button type="button" onclick="verFacturaArchivo('${tipoArchivo}', '/storage/${fileUrl}')" class="index-action index-action--view" title="Ver ${tipoArchivo === 'pdf' ? 'PDF' : 'XML'}">
                            <i class="fas fa-eye"></i>
                        </button>`;
                        }

                        actions += `<button type="button" onclick="abrirModalReemplazo(${row.FacturasID}, '${nombreFactura}', '${fileUrl}', '${insumoStr}', ${solID}, ${row.GerenciaID}, '${tipoArchivo}')" class="index-action index-action--edit" title="Actualizar">
                        <i class="fas fa-edit"></i>
                    </button>`;

                        return actions + '</div>';
                    }
                }
            ],
            drawCallback: function() {
                if (window.IndexPage) {
                    window.IndexPage.updateCount(this.api());
                }

                $('#facturasTable .mes-select').each(function () {
                    $(this).data('facturasMesPrev', $(this).val());
                });
            },
            initComplete: function() {
                if (window.IndexPage) {
                    window.IndexPage.init(this.api());
                }
            }
        });

        (function mountFacturasLoader() {
            const $w = $('#facturasTable_wrapper');
            if (!$w.length || $w.find('#facturasTableLoadingOverlay').length) return;
            $w.prepend(
                '<div id="facturasTableLoadingOverlay" class="hidden absolute inset-0 z-[25] bg-gray-50/90 dark:bg-slate-900/90">' +
                '<span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 inline-flex items-center gap-2 rounded-xl border border-indigo-200/70 dark:border-indigo-800/50 bg-white dark:bg-slate-800 px-4 py-3 text-sm font-bold text-indigo-600 dark:text-indigo-300 shadow-lg">' +
                '<i class="fas fa-spinner fa-spin text-lg" aria-hidden="true"></i>' +
                '<span>Cargando facturas…</span></span></div>'
            );
        })();

        $('#facturasTable')
            .on('preXhr.dt', function () {
                $('#facturasTableLoadingOverlay').removeClass('hidden');
            })
            .on('xhr.dt error.dt', function () {
                $('#facturasTableLoadingOverlay').addClass('hidden');
            });

        $('#facturasTable').on('focus', '.insumo-select', async function() {
            const $sel = $(this);
            const $wrap = $sel.closest('.insumo-select-wrap');
            const facturaID = $sel.data('factura');
            const valorAct = $sel.data('valor') || '';

            if ($sel.data('loaded') === true || $sel.data('loaded') === 'true') return;
            $sel.data('loaded', true);

            // Mostrar spinner, ocultar chevron
            $wrap.find('.chevron-icon').addClass('hidden');
            $wrap.find('.loading-icon').removeClass('hidden');
            $sel.prop('disabled', true);

            try {
                let cache = insumoCache[facturaID];
                if (!cache || !Array.isArray(cache.nombres)) {
                    const res = await fetch(`{{ route('facturas.getInsumosPorGerencia') }}?facturaID=${encodeURIComponent(facturaID)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    cache = {
                        nombres: (json && json.data) ? json.data : [],
                        tiene: !!(json && json.tiene_presupuesto)
                    };
                    insumoCache[facturaID] = cache;
                }
                const insumos = cache.nombres;

                // Guardar el valor actual antes de limpiar
                const currentVal = $sel.val();
                $sel.empty().append('<option value="">— Sin asignar —</option>');
                
                insumos.forEach(nombre => {
                    const selected = (nombre === valorAct || nombre === currentVal) ? 'selected' : '';
                    const displayNombre = nombre.length > 32 ? nombre.substring(0, 32) + '...' : nombre;
                    $sel.append(`<option value="${nombre}" ${selected} title="${nombre}">${displayNombre}</option>`);
                });

                const actual = String(valorAct || currentVal || '');
                if (actual && insumos.indexOf(actual) === -1) {
                    const displayActual = actual.length > 32 ? actual.substring(0, 32) + '...' : actual;
                    $sel.append(`<option value="${actual}" selected title="${actual}">${displayActual}</option>`);
                }
                $sel.data('tienePresupuesto', !!cache.tiene);
                $sel.append('<option value="__nuevo__">Agregar insumo (desviación)</option>');
            } catch (e) {
                console.error('Error cargando insumos:', e);
                $sel.empty().append('<option value="" disabled>Error al cargar</option>');
            } finally {
                // Ocultar spinner, mostrar chevron
                $wrap.find('.loading-icon').addClass('hidden');
                $wrap.find('.chevron-icon').removeClass('hidden');
                $sel.prop('disabled', false);
                
                // Abrir el select automáticamente después de cargar
                setTimeout(() => {
                    const selectElement = $sel[0];
                    if (selectElement && typeof selectElement.showPicker === 'function') {
                        try {
                            selectElement.showPicker();
                        } catch (err) {
                            // Fallback si showPicker no está soportado
                            $sel.focus();
                        }
                    } else {
                        // Fallback para browsers que no soportan showPicker
                        $sel.focus();
                        // Simular click para abrir dropdown
                        const event = new MouseEvent('mousedown', { bubbles: true, cancelable: true });
                        selectElement.dispatchEvent(event);
                    }
                }, 50);
            }
        });

        $('#facturasTable').on('change', '.insumo-select', async function() {
            const $sel = $(this);
            const $wrap = $sel.closest('.insumo-select-wrap');
            const facturaID = $sel.data('factura');
            let nuevo = $sel.val() || '';
            const prev = String($sel.attr('data-valor') || '').trim();
            if (nuevo === '__nuevo__') {
                const sinPresupuesto = $sel.data('tienePresupuesto') === false || $sel.data('tienePresupuesto') === 'false';
                const pedido = (typeof Swal !== 'undefined')
                    ? await Swal.fire({
                        title: 'Insumo nuevo',
                        text: sinPresupuesto
                            ? 'Este presupuesto interno no tiene corte. El insumo se cuenta como desviación.'
                            : 'Este insumo no está en el corte. Se cuenta como desviación.',
                        input: 'text',
                        inputPlaceholder: 'Nombre del insumo',
                        showCancelButton: true,
                        confirmButtonText: 'Usar insumo',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#d97706'
                    })
                    : { isConfirmed: true, value: window.prompt('Nombre del insumo. Se cuenta como desviación.') };
                const nombre = pedido && pedido.isConfirmed ? String(pedido.value || '').trim() : '';
                if (!nombre) {
                    $sel.val(prev);
                    return;
                }
                const yaExiste = $sel.find('option').filter(function () { return this.value === nombre; }).length > 0;
                if (!yaExiste) {
                    $sel.append($('<option>', { value: nombre, text: nombre }));
                }
                $sel.val(nombre);
                nuevo = nombre;
            }
            if (nuevo === prev) return;

            const prevLabel = prev ? (prev.length > 48 ? prev.substring(0, 48) + '…' : prev) : '— Sin asignar —';
            const nuevoLabel = ($sel.find('option:selected').text() || '').trim() || '— Sin asignar —';

            const ok = await confirmarCambioSeguroFactura({
                titulo: '¿Confirmar cambio de insumo?',
                detalleHtml:
                    '<p class="text-sm text-slate-600 dark:text-slate-300 text-left mb-2">Factura <strong>#' + _escHtml(String(facturaID)) + '</strong></p>' +
                    '<p class="text-xs text-slate-500 dark:text-slate-400 text-left uppercase tracking-wide font-bold mb-1">Valor actual</p>' +
                    '<p class="text-sm text-slate-800 dark:text-slate-100 text-left font-semibold mb-3">' + _escHtml(prevLabel) + '</p>' +
                    '<p class="text-xs text-slate-500 dark:text-slate-400 text-left uppercase tracking-wide font-bold mb-1">Nuevo valor</p>' +
                    '<p class="text-sm text-indigo-700 dark:text-indigo-300 text-left font-semibold">' + _escHtml(nuevoLabel) + '</p>',
                textoPlano: '¿Confirmar cambio de insumo en la factura #' + facturaID + '?'
            });
            if (!ok) {
                $sel.val(prev);
                return;
            }

            $sel.prop('disabled', true);

            try {
                const res = await fetch(`/facturas/${facturaID}/insumo`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ InsumoNombre: nuevo })
                });

                if (!res.ok) throw new Error('Error HTTP ' + res.status);

                $sel.attr('data-valor', nuevo);
                $wrap.attr('data-valor', nuevo);

                $sel.removeClass('!border-green-500').addClass('!border-green-500');
                setTimeout(() => $sel.removeClass('!border-green-500'), 800);

            } catch (e) {
                $sel.val(prev);
                $sel.addClass('!border-rose-500');
                setTimeout(() => $sel.removeClass('!border-rose-500'), 1500);
            } finally {
                $sel.prop('disabled', false);
            }
        });

        $('#facturasTable').on('focus', '.mes-select', function() {
            $(this).data('facturasMesPrev', $(this).val());
        });

        $('#facturasTable').on('change', '.mes-select', async function() {
            const $sel = $(this);
            const facturaID = $sel.data('factura');
            const mes = parseInt($sel.val(), 10);
            const mesPrev = parseInt(String($sel.data('facturasMesPrev')), 10);
            if (!Number.isNaN(mesPrev) && mes === mesPrev) return;

            const nomPrev = Number.isNaN(mesPrev)
                ? 'Mes anterior'
                : (mesesNombres[mesPrev] || ('Mes ' + mesPrev));
            const nomNuevo = mesesNombres[mes] || ('Mes ' + mes);

            const ok = await confirmarCambioSeguroFactura({
                titulo: '¿Confirmar cambio de mes?',
                detalleHtml:
                    '<p class="text-sm text-slate-600 dark:text-slate-300 text-left mb-2">Factura <strong>#' + _escHtml(String(facturaID)) + '</strong></p>' +
                    '<p class="text-xs text-slate-500 dark:text-slate-400 text-left uppercase tracking-wide font-bold mb-1">Mes actual</p>' +
                    '<p class="text-sm text-slate-800 dark:text-slate-100 text-left font-semibold mb-3">' + _escHtml(nomPrev) + '</p>' +
                    '<p class="text-xs text-slate-500 dark:text-slate-400 text-left uppercase tracking-wide font-bold mb-1">Nuevo mes</p>' +
                    '<p class="text-sm text-indigo-700 dark:text-indigo-300 text-left font-semibold">' + _escHtml(nomNuevo) + '</p>',
                textoPlano: '¿Confirmar cambio de mes de ' + nomPrev + ' a ' + nomNuevo + ' en la factura #' + facturaID + '?'
            });
            if (!ok) {
                if (!Number.isNaN(mesPrev)) $sel.val(String(mesPrev));
                return;
            }

            $sel.prop('disabled', true);

            try {
                const res = await fetch(`/facturas/${facturaID}/mes`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ Mes: mes })
                });

                if (!res.ok) throw new Error('Error HTTP ' + res.status);

                $sel.data('facturasMesPrev', String(mes));
                $sel.removeClass('!border-green-500').addClass('!border-green-500');
                setTimeout(() => $sel.removeClass('!border-green-500'), 800);

            } catch (e) {
                if (!Number.isNaN(mesPrev)) $sel.val(String(mesPrev));
                $sel.addClass('!border-rose-500');
                setTimeout(() => $sel.removeClass('!border-rose-500'), 1500);
            } finally {
                $sel.prop('disabled', false);
            }
        });

        function buildSearchableSelect($container, options, selectedValue, placeholder = "— Buscar y seleccionar —", dataIdx = null) {
            let optsHtml = `<li data-val="" class="px-3 py-2.5 text-xs font-semibold cursor-pointer rounded-lg mb-1 transition-colors hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-slate-600 dark:text-slate-300 ${!selectedValue ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400' : ''}">— Dejar en blanco —</li>`;
            
            options.forEach(opt => {
                const isSelected = opt === selectedValue;
                const activeClass = isSelected ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400' : 'hover:bg-gray-200 dark:hover:bg-slate-700/50';
                optsHtml += `<li data-val="${opt}" class="px-3 py-2.5 text-xs font-medium cursor-pointer rounded-lg mb-0.5 transition-colors text-slate-700 dark:text-slate-200 ${activeClass}">${opt}</li>`;
            });

            const displayValue = selectedValue || placeholder;
            const idxClass = dataIdx !== null ? `concepto-insumo-wrapper` : 'global-insumo-wrapper';
            const idxAttr = dataIdx !== null ? `data-idx="${dataIdx}"` : '';

            const html = `
            <div class="${idxClass} relative w-full" ${idxAttr}>
                <input type="hidden" class="real-input" value="${selectedValue || ''}">
                <div class="select-trigger flex items-center justify-between w-full h-11 px-4 text-sm font-semibold border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-gray-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" tabindex="0">
                    <span class="selected-text truncate pr-4 ${!selectedValue ? 'text-slate-400 dark:text-slate-500 font-medium' : ''}">${displayValue}</span>
                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-300 transform"></i>
                </div>
                <div class="select-dropdown hidden absolute z-[100] w-full mt-2 bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl overflow-hidden transform opacity-0 scale-95 transition-all duration-200 origin-top">
                    <div class="p-2 border-b border-slate-200 dark:border-slate-700 bg-gray-100/50 dark:bg-slate-900/50">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-2.5 text-xs text-slate-400"></i>
                            <input type="text" class="select-search w-full h-9 pl-8 pr-3 text-xs font-medium rounded-lg bg-gray-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 placeholder-slate-400" placeholder="Escribe para buscar...">
                        </div>
                    </div>
                    <ul class="select-options max-h-56 overflow-y-auto p-2 space-y-0.5 custom-scrollbar">
                        ${optsHtml}
                    </ul>
                </div>
            </div>`;

            $container.html(html);

            const $wrapper = $container.find(`.${idxClass}`);
            const $trigger = $wrapper.find('.select-trigger');
            const $dropdown = $wrapper.find('.select-dropdown');
            const $search = $wrapper.find('.select-search');
            const $options = $wrapper.find('.select-options li');
            const $input = $wrapper.find('.real-input');
            const $text = $wrapper.find('.selected-text');
            const $icon = $wrapper.find('.fa-chevron-down');

            $trigger.on('click', function(e) {
                e.stopPropagation();
                const isHidden = $dropdown.hasClass('hidden');
                
                $('.select-dropdown').addClass('hidden').removeClass('opacity-100 scale-100').addClass('opacity-0 scale-95');
                $('.fa-chevron-down').removeClass('rotate-180');
                
                if (isHidden) {
                    $dropdown.removeClass('hidden');
                    requestAnimationFrame(() => {
                        $dropdown.removeClass('opacity-0 scale-95').addClass('opacity-100 scale-100');
                        $icon.addClass('rotate-180');
                        $search.val('').trigger('input');
                        $search.focus();
                    });
                }
            });

            $search.on('input', function() {
                const val = $(this).val().toLowerCase().trim();
                $options.each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.includes(val)) $(this).show();
                    else $(this).hide();
                });
            });

            $options.on('click', function(e) {
                e.stopPropagation();
                const val = $(this).data('val');
                const text = $(this).text();
                
                $options.removeClass('bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400').addClass('hover:bg-gray-200 dark:hover:bg-slate-700/50');
                $(this).removeClass('hover:bg-gray-200 dark:hover:bg-slate-700/50').addClass('bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400');
                
                $input.val(val);
                $text.text(val || placeholder);
                
                if (!val) {
                    $text.addClass('text-slate-400 dark:text-slate-500 font-medium');
                } else {
                    $text.removeClass('text-slate-400 dark:text-slate-500 font-medium');
                }

                $dropdown.removeClass('opacity-100 scale-100').addClass('opacity-0 scale-95');
                $icon.removeClass('rotate-180');
                setTimeout(() => $dropdown.addClass('hidden'), 200);
            });

            $dropdown.on('click', function(e) { e.stopPropagation(); });
        }

        $(document).on('click', function() {
            $('.select-dropdown').removeClass('opacity-100 scale-100').addClass('opacity-0 scale-95');
            $('.fa-chevron-down').removeClass('rotate-180');
            setTimeout(() => $('.select-dropdown').addClass('hidden'), 200);
        });

        function escaparHtmlFactura(valor) {
            return String(valor == null ? '' : valor)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function atributoXml(nodo, nombre) {
            if (!nodo) return '';
            return nodo.getAttribute(nombre) || nodo.getAttribute(nombre.toLowerCase()) || '';
        }

        function nodoCfdi(doc, local) {
            const espacios = [
                'http://www.sat.gob.mx/cfd/4',
                'http://www.sat.gob.mx/cfd/3',
                'http://www.sat.gob.mx/TimbreFiscalDigital'
            ];
            for (let i = 0; i < espacios.length; i++) {
                const lista = doc.getElementsByTagNameNS(espacios[i], local);
                if (lista && lista.length) return lista;
            }
            const sueltos = doc.getElementsByTagName(local);
            return sueltos && sueltos.length ? sueltos : [];
        }

        function documentoXmlLegible(texto) {
            const xml = new DOMParser().parseFromString(texto, 'text/xml');
            if (xml.querySelector('parsererror')) {
                return '<p style="font-family:sans-serif;padding:24px;">No se pudo leer el XML.</p>';
            }
            const comprobantes = nodoCfdi(xml, 'Comprobante');
            const comprobante = comprobantes[0];
            if (!comprobante) {
                return '<p style="font-family:sans-serif;padding:24px;">Este XML no tiene el formato de una factura CFDI.</p>';
            }
            const emisor = nodoCfdi(xml, 'Emisor')[0];
            const receptor = nodoCfdi(xml, 'Receptor')[0];
            const timbre = nodoCfdi(xml, 'TimbreFiscalDigital')[0];
            const conceptos = nodoCfdi(xml, 'Concepto');
            const dinero = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
            const monto = (valor) => {
                const n = parseFloat(valor);
                return Number.isNaN(n) ? '—' : dinero.format(n);
            };
            let filas = '';
            for (let i = 0; i < conceptos.length; i++) {
                const c = conceptos[i];
                filas += '<tr><td>' + escaparHtmlFactura(atributoXml(c, 'Descripcion') || atributoXml(c, 'ClaveProdServ')) + '</td>'
                    + '<td style="text-align:right">' + escaparHtmlFactura(atributoXml(c, 'Cantidad')) + '</td>'
                    + '<td style="text-align:right">' + monto(atributoXml(c, 'ValorUnitario')) + '</td>'
                    + '<td style="text-align:right">' + monto(atributoXml(c, 'Importe')) + '</td></tr>';
            }
            if (!filas) filas = '<tr><td colspan="4">Sin conceptos</td></tr>';
            return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
                + 'body{margin:0;background:#f8fafc;color:#0f172a;font-family:Segoe UI,sans-serif;padding:24px;}'
                + '.hoja{max-width:860px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;}'
                + 'h1{margin:0 0 4px;font-size:20px;} .muted{color:#64748b;font-size:12px;}'
                + '.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:18px 0;}'
                + '.caja{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;}'
                + '.caja strong{display:block;font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:4px;}'
                + 'table{width:100%;border-collapse:collapse;font-size:13px;}'
                + 'th,td{padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;}'
                + 'th{font-size:11px;text-transform:uppercase;color:#64748b;}'
                + '.totales{margin-top:16px;text-align:right;font-size:14px;}'
                + '.totales b{font-size:18px;}'
                + '</style></head><body><div class="hoja"><h1>Factura</h1>'
                + '<p class="muted">Folio ' + escaparHtmlFactura((atributoXml(comprobante, 'Serie') + ' ' + atributoXml(comprobante, 'Folio')).trim() || 'sin folio')
                + ' · ' + escaparHtmlFactura(atributoXml(comprobante, 'Fecha')) + '</p>'
                + '<div class="grid"><div class="caja"><strong>Emisor</strong>' + escaparHtmlFactura(atributoXml(emisor, 'Nombre') || '—')
                + '<div class="muted">RFC ' + escaparHtmlFactura(atributoXml(emisor, 'Rfc') || '—') + '</div></div>'
                + '<div class="caja"><strong>Receptor</strong>' + escaparHtmlFactura(atributoXml(receptor, 'Nombre') || '—')
                + '<div class="muted">RFC ' + escaparHtmlFactura(atributoXml(receptor, 'Rfc') || '—') + '</div></div></div>'
                + '<table><thead><tr><th>Concepto</th><th style="text-align:right">Cantidad</th><th style="text-align:right">Unitario</th><th style="text-align:right">Importe</th></tr></thead><tbody>'
                + filas + '</tbody></table><div class="totales"><div>Subtotal ' + monto(atributoXml(comprobante, 'SubTotal')) + '</div>'
                + '<div><b>Total ' + monto(atributoXml(comprobante, 'Total')) + ' ' + escaparHtmlFactura(atributoXml(comprobante, 'Moneda') || 'MXN') + '</b></div>'
                + (timbre ? '<div class="muted" style="margin-top:8px;">UUID ' + escaparHtmlFactura(atributoXml(timbre, 'UUID')) + '</div>' : '')
                + '</div></div></body></html>';
        }

        function limpiarFrameFactura(frame) {
            if (!frame) return;
            frame.removeAttribute('srcdoc');
            frame.src = 'about:blank';
        }

        async function mostrarArchivoEnFrame(frame, tipo, url) {
            limpiarFrameFactura(frame);
            if (!url) return;
            if (tipo === 'pdf') {
                frame.src = url;
                return;
            }
            const res = await fetch(url, { headers: { 'Accept': 'application/xml,text/xml,text/plain,*/*' } });
            const texto = await res.text();
            frame.srcdoc = documentoXmlLegible(texto);
        }

        window.verFacturaArchivo = async function(tipo, url) {
            const modal = window.llevarModalAPantalla
                ? window.llevarModalAPantalla('modalVistaFactura')
                : document.getElementById('modalVistaFactura');
            const titulo = modal ? modal.querySelector('#vistaFacturaTitulo') : null;
            const sub = modal ? modal.querySelector('#vistaFacturaSub') : null;
            const frame = modal ? modal.querySelector('#vistaFacturaFrame') : null;
            if (titulo) titulo.textContent = tipo === 'pdf' ? 'Factura PDF' : 'Factura XML';
            if (sub) sub.textContent = tipo === 'pdf' ? 'Representación del PDF cargado.' : 'Datos del CFDI en una vista legible.';
            if (modal) modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            try {
                await mostrarArchivoEnFrame(frame, tipo, url);
            } catch (e) {
                frame.srcdoc = '<p style="font-family:sans-serif;padding:24px;">No se pudo abrir el archivo.</p>';
            }
        };

        window.cerrarVistaFactura = function() {
            const modal = document.getElementById('modalVistaFactura');
            if (modal) modal.classList.add('hidden');
            limpiarFrameFactura(document.getElementById('vistaFacturaFrame'));
            document.body.classList.remove('overflow-hidden');
        };

        window.abrirModalReemplazo = async function(id, nombre, fileUrl, insumoActual, solID, gerenciaID, tipo) {
            $('#formReemplazarFactura')[0].reset();
            $('#btnSubmitReemplazo').prop('disabled', false).html('<i class="fas fa-save text-xs"></i> Guardar');
            $('#reemplazoFacturaID').val(id);
            $('#reemplazoFacturaNombre').text(nombre);
            $('#formReemplazarFactura').data('tipoArchivo', tipo || '');
            
            $('#previewContenedor').addClass('hidden');
            $('#previewContenido').html('');
            $('#reemplazoXml').val('');
            $('#reemplazoPdf').val('');
            const esPdf = tipo === 'pdf';
            const esXml = tipo === 'xml';
            $('#reemplazoZonaPdf').toggleClass('hidden', !esPdf);
            $('#reemplazoZonaXml').toggleClass('hidden', !esXml);
            $('#reemplazoEtiquetaArchivo').html(esPdf
                ? '<i class="fas fa-file-pdf text-rose-500 mr-1.5"></i> Reemplazar PDF <span class="text-slate-400 font-normal">(Opcional)</span>'
                : (esXml
                    ? '<i class="fas fa-file-code text-violet-500 mr-1.5"></i> Reemplazar XML <span class="text-slate-400 font-normal">(Opcional)</span>'
                    : '<i class="fas fa-file-upload text-indigo-500 mr-1.5"></i> Esta factura no tiene archivo'));
            const vista = document.getElementById('reemplazoVista');
            if (fileUrl && (esPdf || esXml)) {
                $('#reemplazoVistaWrap').removeClass('hidden');
                $('#reemplazoVistaTitulo').text(esPdf ? 'PDF cargado' : 'XML cargado');
                try {
                    await mostrarArchivoEnFrame(vista, tipo, '/storage/' + fileUrl);
                } catch (e) {
                    limpiarFrameFactura(vista);
                }
            } else {
                $('#reemplazoVistaWrap').addClass('hidden');
                limpiarFrameFactura(vista);
            }
            
            const modalEdicion = window.llevarModalAPantalla
                ? window.llevarModalAPantalla('modalReemplazoFactura')
                : document.getElementById('modalReemplazoFactura');
            if (modalEdicion) modalEdicion.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            // Cargar los datos actuales de la factura
            try {
                const res = await fetch(`/facturas/${id}/datos`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                
                $('#reemplazoEmisor').val(json.Emisor || '');
                $('#reemplazoCosto').val(json.Costo || '');
            } catch (e) {
                console.error('Error cargando datos de factura:', e);
            }
        };

        window.cerrarModalReemplazo = function() {
            $('#modalReemplazoFactura').addClass('hidden');
            limpiarFrameFactura(document.getElementById('reemplazoVista'));
            document.body.classList.remove('overflow-hidden');
        };

        window.enviarReemplazoFactura = async function(e) {
            e.preventDefault();
            const facturaID = $('#reemplazoFacturaID').val();
            const emisor = $('#reemplazoEmisor').val().trim();
            const costo = $('#reemplazoCosto').val();
            const tipoArchivo = $('#formReemplazarFactura').data('tipoArchivo');
            const fileXml = tipoArchivo === 'xml' ? $('#reemplazoXml')[0].files[0] : null;
            const filePdf = tipoArchivo === 'pdf' ? $('#reemplazoPdf')[0].files[0] : null;
            
            const btn = $('#btnSubmitReemplazo');
            const originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...').prop('disabled', true);

            try {
                const formData = new FormData();
                formData.append('Emisor', emisor);
                formData.append('Costo', costo);
                
                if (fileXml) formData.append('archivo_xml', fileXml);
                if (filePdf) formData.append('archivo_pdf', filePdf);
                formData.append('_token', '{{ csrf_token() }}');

                const res = await fetch(`/facturas/${facturaID}/actualizar-completo`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Error al actualizar');

                if (window.AppAlert) window.AppAlert.ok(json.message || 'Factura actualizada correctamente.', 'Factura actualizada');

                cerrarModalReemplazo();
                $('#facturasTable').DataTable().ajax.reload(null, false);

            } catch (error) {
                if (window.AppAlert) window.AppAlert.fallo(error.message || 'Hubo un error al actualizar la factura.');
                else window.alert(error.message || 'Hubo un error al actualizar la factura.');
            } finally {
                btn.html(originalText).prop('disabled', false);
            }
        };

        async function loadPreview(url, formData) {
            $('#previewContenedor').removeClass('hidden');
            $('#previewContenido').html('<div class="flex items-center gap-2 text-indigo-500 font-bold"><i class="fas fa-spinner fa-spin"></i> Analizando documento...</div>');

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (!res.ok || data.error === true) {
                    throw new Error(data.error && typeof data.error === 'string' ? data.error : 'No se pudo extraer la información del documento.');
                }

                // Actualizar los campos del formulario con los datos parseados
                if (data.emisor) $('#reemplazoEmisor').val(data.emisor);
                if (data.total) $('#reemplazoCosto').val(parseFloat(data.total));

                // Mostrar preview
                let html = '';
                if (data.emisor) html += `<div><strong class="font-extrabold text-slate-800 dark:text-slate-200">Emisor:</strong> ${data.emisor}</div>`;
                if (data.total) html += `<div><strong class="font-extrabold text-slate-800 dark:text-slate-200">Subtotal:</strong> $${parseFloat(data.total).toLocaleString('es-MX', {minimumFractionDigits:2})} <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 ml-1">${data.moneda || 'MXN'}</span></div>`;
                if (data.uuid) html += `<div><strong class="font-extrabold text-slate-800 dark:text-slate-200">UUID:</strong> <span class="font-mono text-xs bg-gray-100 dark:bg-slate-800 px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700">${data.uuid}</span></div>`;
                if (data.fecha) html += `<div><strong class="font-extrabold text-slate-800 dark:text-slate-200">Fecha:</strong> ${data.fecha}</div>`;

                if (!html) html = `<div class="text-slate-500 font-medium italic">Datos extraídos correctamente. Revisa los campos a continuación.</div>`;
                $('#previewContenido').html(html);

            } catch (e) {
                $('#previewContenido').html(`<div class="text-rose-500 font-bold flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> ${e.message}</div>`);
                $('#previewContenedor').removeClass('hidden');
            }
        }

        $('#reemplazoXml').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('xml', file);
            loadPreview('{{ route("facturas.parsearXml") }}', formData);
        });

        $('#reemplazoPdf').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('pdf', file);
            loadPreview('{{ route("facturas.previsualizarPdf") }}', formData);
        });

        function actualizarInfoGerenciaSeleccionada() {
            const $g = $('#gerenci_id');
            const val = $g.val();
            const gerencia = $g.find('option:selected').text();
            if (val && val !== '') {
                $('#titleGerencia').text(gerencia);
                $('#gerenciaInfo').removeClass('hidden').addClass('flex');
            } else {
                $('#titleGerencia').text('');
                $('#gerenciaInfo').addClass('hidden').removeClass('flex');
            }
        }

        function aplicarFiltrosGlobalesFacturas() {
            actualizarInfoGerenciaSeleccionada();
            table.ajax.reload();
            if (typeof window.reloadComparativaFromGlobal === 'function') {
                window.reloadComparativaFromGlobal();
            }
        }

        $('#formFilter').on('submit', function(e) {
            e.preventDefault();
        });

        $('#gerenci_id, #mesFilter, #añoFilter').on('change', function() {
            aplicarFiltrosGlobalesFacturas();
        });

        $('#limpiarFiltrosFacturas').on('click', function() {
            var anio = $('#formFilter').attr('data-anio-default') || '';
            $('#gerenci_id, #mesFilter').val('');
            $('#añoFilter').val(anio);
            $('#gerenci_id, #mesFilter, #añoFilter').trigger('change.select2');
            aplicarFiltrosGlobalesFacturas();
        });

        window.syncGerenciaFacturasBanner = actualizarInfoGerenciaSeleccionada;

        actualizarInfoGerenciaSeleccionada();
    });
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; }
</style>
@endpush