@extends('layouts.app')

@section('content')
<style>
    /* Scrollbar */
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background-color: #475569; }

    /* Transición suave para tabs */
    .tab-content { display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
    .tab-content.active { display: block; opacity: 1; }
</style>

<div class="w-full mx-auto max-w-7xl">
    
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
                class="px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2
                       bg-indigo-600 text-white shadow-md">
                <i class="fas fa-receipt"></i> Facturas
            </button>
            <button onclick="switchTab('historial')" id="tab-historial" 
                class="px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2
                       text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">
                <i class="fas fa-history"></i> Historial
            </button>
        </div>
    </div>

    <div id="content-facturas" class="tab-content active">
        <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
            
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                <form id="formFilter" class="flex flex-col lg:flex-row items-end gap-4">
                    
                    <div class="w-full lg:w-1/3 group">
                        <label class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Gerencia</label>
                        <div class="relative">
                            {!! Form::select('gerenci_id', ['' => 'Selecciona una opción'] + App\Models\Gerencia::all()->pluck('NombreGerencia', 'GerenciaID')->toArray(), null, [
                                'class' => 'w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all',
                                'id' => 'gerenci_id'
                            ]) !!}
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fas fa-chevron-down text-xs"></i></div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4 group">
                        <label class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Mes</label>
                        <div class="relative">
                            <select id="mesFilter" class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                                <option value="" disabled selected>Seleccionar mes</option>
                                @foreach($meses as $mes) <option value="{{ $mes }}">{{ $mes }}</option> @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400"><i class="fas fa-calendar-alt text-xs"></i></div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4 group">
                        <label class="block text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Año</label>
                        <div class="relative">
                            <select id="añoFilter" class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                                <option value="" disabled selected>Seleccionar año</option>
                                @foreach($years as $año) <option value="{{ $año }}">{{ $año }}</option> @endforeach
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

            <div id="gerenciaInfo" class="hidden p-4 bg-indigo-50 dark:bg-indigo-900/10 border-b border-indigo-100 dark:border-indigo-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-300">
                        <i class="fas fa-building"></i>
                    </div>
                    <span id="titleGerencia" class="text-lg font-bold text-slate-800 dark:text-slate-100"></span>
                </div>
                
                <div class="flex gap-3">
                    <button id="crear" class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-md transition-all flex items-center gap-2" data-toggle="modal" data-target="#modalFactura">
                        <i class="fas fa-plus"></i> Crear Factura
                    </button>
                    <button id="graficas" class="h-10 px-4 rounded-lg bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-2">
                        <i class="fas fa-chart-pie text-indigo-500"></i> Ver Gráficas
                    </button>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <table id="facturasTable" class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-4 px-6 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insumo</th>
                            <th class="py-4 px-6 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Mes</th>
                            <th class="py-4 px-6 text-xs font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="content-historial" class="tab-content">
        <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 p-8 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 mb-4">
                <i class="fas fa-history text-3xl text-slate-400 dark:text-slate-500"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Historial de Cortes</h3>
            <p class="text-slate-500 dark:text-slate-400 mt-2">Aquí podrás ver el historial de cortes generados anteriormente.</p>
            <div class="mt-8 p-4 border border-dashed border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                <span class="text-sm font-medium text-slate-400">Contenido del historial próximamente...</span>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="modalFactura" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden bg-gray-50 dark:bg-slate-900">
            <div class="modal-header border-b border-slate-100 dark:border-slate-800 p-5 bg-slate-50/50 dark:bg-slate-950/50">
                <h5 class="modal-title text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-file-upload text-indigo-500"></i> Subir XML
                </h5>
                <button type="button" class="close text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 outline-none" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-6">
                <div class="flex gap-4 mb-6">
                    <div class="flex-1 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800/50">
                        <label class="block text-[10px] font-bold text-indigo-400 uppercase">Gerencia</label>
                        <span id="previewGerencia" class="text-sm font-bold text-indigo-700 dark:text-indigo-300 block truncate">--</span>
                    </div>
                    <div class="flex-1 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800/50">
                        <label class="block text-[10px] font-bold text-indigo-400 uppercase">Mes</label>
                        <span id="previewMes" class="text-sm font-bold text-indigo-700 dark:text-indigo-300 block">--</span>
                    </div>
                </div>

                <form action="{{ route('cortes.saveXML') }}" method="POST" id="formImagen" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="gerenci_id" id="modalGerenciaInput">
                    <input type="hidden" name="mes" id="modalMesInput">
                    
                    <div class="mb-5">
                        <label class="block w-full cursor-pointer group">
                            <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-800/50 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/10 group-hover:border-indigo-400 transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-2 group-hover:text-indigo-500 transition-colors"></i>
                                    <p class="mb-1 text-sm text-slate-500 dark:text-slate-400"><span class="font-bold text-indigo-500">Clic para subir</span> o arrastra</p>
                                    <p class="text-xs text-slate-400">Soporta archivos .XML</p>
                                </div>
                                <input type="file" id="inputImage" name="imagen" accept=".xml" required class="hidden" />
                            </div>
                        </label>
                        <div id="fileName" class="mt-2 text-xs text-center text-emerald-500 font-medium h-4"></div>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0">
                        Procesar Factura
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    // Lógica de Tabs
    function switchTab(tab) {
        // Reset botones
        document.getElementById('tab-facturas').className = "px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700";
        document.getElementById('tab-historial').className = "px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700";

        // Botón Activo
        const activeClass = "px-6 py-2 text-sm font-bold rounded-lg transition-all duration-300 flex items-center gap-2 bg-indigo-600 text-white shadow-md";
        document.getElementById('tab-' + tab).className = activeClass;

        // Mostrar Contenido
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.getElementById('content-' + tab).classList.add('active');
    }

    // Input File Nombre
    document.getElementById('inputImage').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        document.getElementById('fileName').textContent = fileName ? 'Archivo seleccionado: ' + fileName : '';
    });

    $(document).ready(function() {
        const currencyFmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

        var table = $('#facturasTable').DataTable({
            destroy: true, responsive: true, searching: true, processing: true, serverSide: true, pageLength: 12,
            dom: 'rt<"flex flex-col sm:flex-row justify-between items-center p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900"ip>',
            language: {
                zeroRecords: "<div class='py-8 text-center text-slate-400 italic'>No hay facturas</div>",
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
                },
                dataSrc: 'data'
            },
            columns: [
                { data: 'NombreInsumo', className: 'p-4 text-sm font-semibold text-slate-700 dark:text-slate-200 border-b dark:border-slate-800' },
                { data: 'Mes', className: 'p-4 text-sm text-slate-600 dark:text-slate-300 border-b dark:border-slate-800' },
                { 
                    data: 'Costo', 
                    className: 'p-4 text-right border-b dark:border-slate-800',
                    render: function(data) {
                        return `<span class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2.5 py-1 rounded-lg">${currencyFmt.format(data)}</span>`;
                    }
                },
            ],
            drawCallback: function() {
                const btnClass = 'px-3 py-1.5 ml-1.5 rounded-lg border text-xs font-semibold transition-all duration-200 cursor-pointer shadow-sm ';
                const normal = 'bg-gray-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700';
                const active = '!bg-indigo-600 !border-indigo-600 !text-white hover:!bg-indigo-700';
                const disabled = 'opacity-40 cursor-not-allowed shadow-none';

                $('.dataTables_paginate .paginate_button').addClass(btnClass + normal);
                $('.dataTables_paginate .paginate_button.current').removeClass(normal).addClass(active);
                $('.dataTables_paginate .paginate_button.disabled').addClass(disabled);
            }
        });

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

        // Modal Logic
        $('#crear').click(() => {
            const gerenciaSel = $('#gerenci_id option:selected').text();
            const mesSel = $('#mesFilter').val();
            
            $('#modalGerenciaInput').val($('#gerenci_id').val());
            $('#modalMesInput').val(mesSel);
            
            $('#previewGerencia').text(gerenciaSel !== 'Selecciona una opción' ? gerenciaSel : '---');
            $('#previewMes').text(mesSel || '---');
        });
    });

    // Subida XML Async (Opcional, si quieres mantener la lógica AJAX que tenías)
    const input = document.getElementById('inputImage');
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if(input && token) {
        input.addEventListener('change', async () => {
            if (!input.files.length) return;
            // Aquí puedes restaurar tu lógica de fetch para subir XML automáticamente
            // O dejar que el botón "Procesar Factura" haga el submit normal del form.
        });
    }
</script>
@endpush