<div>

    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
    @include('inventarios.partials.tipo-persona-styles')

    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            padding-top: 4px;
        }

        .dark .select2-container .select2-selection--single {
            border-color: #374151 !important;
            background-color: #111827 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #e5e7eb !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .select2-container {
            width: 100% !important;
        }

        #tabla-empleados td.dt-control {
            width: 2.75rem;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            padding: 0.4rem !important;
        }
        #tabla-empleados td.dt-control::before,
        #tabla-empleados td.dt-control::after {
            display: none !important;
            content: none !important;
            background: none !important;
        }
        .inv-row-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            padding: 0;
            border: 1px solid #d1d5db;
            border-radius: 0.45rem;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            z-index: 2;
        }
        .inv-row-toggle i {
            font-size: 0.75rem;
            pointer-events: none;
            transition: transform 0.18s ease;
        }
        #tabla-empleados tbody tr.shown .inv-row-toggle {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        #tabla-empleados tbody tr.shown .inv-row-toggle i {
            transform: rotate(90deg);
        }
        .dark .inv-row-toggle {
            background: #1f2937;
            border-color: #4b5563;
            color: #9ca3af;
        }
        .dark #tabla-empleados tbody tr.shown .inv-row-toggle {
            background: #1e3a8a;
            border-color: #3b82f6;
            color: #bfdbfe;
        }
        .inv-empleados-wrap {
            overflow: visible;
        }
        @media (max-width: 767px) {
            .inv-empleados-wrap {
                overflow-x: auto;
                overflow-y: visible;
                -webkit-overflow-scrolling: touch;
            }
            .inv-row-toggle {
                width: 2.75rem;
                height: 2.75rem;
            }
        }
    </style>
    @endpush

    <div class="index-page__table-wrap inv-empleados-wrap">
        <table id="tabla-empleados" class="table index-table w-full">
            <thead>
                <tr>
                    <th aria-label="Inventario"></th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Puesto</th>
                    <th>Obra</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>

    @push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('layouts.partials.index-page-js')

    <script>
        $(document).ready(function() {

            // =========================
            // SELECT2
            // =========================
            $('.jz-inv').select2({
                width: '100%',
                placeholder: 'Seleccionar...',
                allowClear: true,
                dropdownParent: $('body')
            });

            // =========================
            // DATATABLE
            // =========================
            var table = $('#tabla-empleados').DataTable({
                retrieve: true,
                serverSide: true,
                processing: true,
                responsive: {
                    details: false
                },
                autoWidth: false,
                searching: false,
                pageLength: 10,
                dom: "t<'index-page__dt-footer'ip>",
                language: {
                    sProcessing: 'Procesando...',
                    sLengthMenu: 'Mostrar _MENU_',
                    sZeroRecords: 'No se encontraron resultados',
                    sEmptyTable: 'Ningún dato disponible en esta tabla',
                    sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
                    sInfoEmpty: 'Mostrando 0 a 0 de 0',
                    sInfoFiltered: '(filtrado de _MAX_ registros)',
                    oPaginate: {
                        sFirst: 'Primero',
                        sLast: 'Último',
                        sNext: 'Siguiente',
                        sPrevious: 'Anterior'
                    }
                },

                ajax: {
                    url: '{{ route("inventarios.indexVista") }}',

                    data: function(d) {

                        d.nombre = $('#filtro-nombre').val();
                        d.obra = $('#filtro-obra').val();
                        d.puesto = $('#filtro-puesto').val();
                        d.filtro_inventario = $('#filtro-inventario').val();
                        d.tipo_persona = $('#filtro-persona').val();
                        d.estatus = $('#filtro-estatus').val();

                    }
                },

                columns: [{
                        className: 'dt-control dark:bg-[#101010] dark:text-white',
                        orderable: false,
                        searchable: false,
                        data: null,
                        width: '42px',
                        defaultContent: '<button type="button" class="inv-row-toggle" title="Ver inventario" aria-label="Ver inventario del empleado" aria-expanded="false"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>',
                    },

                    {
                        data: 'NombreEmpleado',
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'tipo_persona',
                        orderable: false,
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'nombre_puesto',
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'nombre_obra',
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'NumTelefono',
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'Correo',
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'Estado',
                        class: 'dark:bg-[#101010] dark:text-white'
                    },

                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        class: 'dark:bg-[#101010] dark:text-white'
                    }
                ],

                initComplete: function() {

                    var api = this.api();
                    if (window.IndexPage) {
                        window.IndexPage.init(api);
                    }

                },
                drawCallback: function() {
                    if (window.IndexPage) {
                        window.IndexPage.updateCount(this.api());
                    }
                }
            });

            // =========================
            // FILTROS
            // =========================
            var recargarFiltrosTexto = null;
            $('#filtro-nombre, #filtro-inventario')
                .on('keyup', function() {
                    clearTimeout(recargarFiltrosTexto);
                    recargarFiltrosTexto = setTimeout(function() {
                        table.ajax.reload();
                    }, 300);
                });

            $('#filtro-obra, #filtro-puesto, #filtro-persona, #filtro-estatus')
                .on('change', function() {
                    table.ajax.reload();
                });

            $('#limpiar-filtros-inv').on('click', function() {
                $('#filtro-nombre').val('');
                $('#filtro-inventario').val('');
                $('#filtro-estatus').val('1');
                $('#filtro-persona').val('').trigger('change');
                $('#filtro-obra').val('').trigger('change');
                $('#filtro-puesto').val('').trigger('change');
                table.ajax.reload();
            });

            // =========================
            // COLOR PLACEHOLDER
            // =========================
            $('#filtro-persona').on('change', function() {

                if ($(this).val() === '') {

                    $(this).addClass('text-secondary');

                } else {

                    $(this).removeClass('text-secondary');

                }

            });

            // =========================
            // EXPANDIR DETALLES
            // =========================
            // =========================
            // EXPANDIR DETALLES
            // En móvil DataTables Responsive interceptaba el mismo clic
            // (td.dt-control) y abría/cerraba al instante. El chevron
            // solo carga el inventario del empleado.
            // =========================
            $('#tabla-empleados tbody').on('click', '.inv-row-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();

                var tr = $(this).closest('tr');
                if (tr.hasClass('child')) {
                    tr = tr.prev();
                }
                var row = table.row(tr);
                var data = row.data();
                if (!data || !data.EmpleadoID) return;

                var toggle = tr.find('.inv-row-toggle');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    toggle.attr('aria-expanded', 'false');
                    return;
                }

                row.child('<div class="text-center py-3">Cargando...</div>').show();
                tr.addClass('shown');
                toggle.attr('aria-expanded', 'true');

                $.get('/inventarios/' + data.EmpleadoID + '/inventario')
                    .done(function(html) {
                        if (!row.child.isShown()) return;
                        row.child(html).show();
                    })
                    .fail(function() {
                        row.child.hide();
                        tr.removeClass('shown');
                        toggle.attr('aria-expanded', 'false');
                    });
            });

        });
    </script>

    @endpush

</div>