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

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .select2-container {
            width: 100% !important;
        }
    </style>
    @endpush

    <div class="index-page__table-wrap table-responsive">
        <table id="tabla-empleados" class="table index-table w-full">
            <thead>
                <tr>
                    <th></th>
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
                allowClear: true
            });

            // =========================
            // DATATABLE
            // =========================
            var table = $('#tabla-empleados').DataTable({
                responsive: true,
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
                        data: null,
                        defaultContent: '',
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

                    // =========================
                    // CARGAR OBRAS
                    // =========================
                    var obras = api.column(4).data().unique().sort();

                    $('#filtro-obra')
                        .empty()
                        .append('<option value="">Todas las obras</option>');

                    obras.each(function(d) {

                        if (d && d.trim() !== '') {

                            $('#filtro-obra').append(
                                '<option value="' + d + '">' + d + '</option>'
                            );

                        }

                    });

                    // =========================
                    // CARGAR PUESTOS
                    // =========================
                    var puestos = api.column(3).data().unique().sort();

                    $('#filtro-puesto')
                        .empty()
                        .append('<option value="">Todos los puestos</option>');

                    puestos.each(function(d) {

                        if (d && d.trim() !== '') {

                            $('#filtro-puesto').append(
                                '<option value="' + d + '">' + d + '</option>'
                            );

                        }

                    });

                    // Reinicializar Select2
                    $('.jz-inv').select2({
                        width: '100%',
                        placeholder: 'Seleccionar...',
                        allowClear: true
                    });

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
            $('#filtro-nombre, #filtro-inventario')
                .on('keyup', function() {

                    table.ajax.reload();

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
            $('#tabla-empleados tbody').on('click', 'td.dt-control', function() {

                var tr = $(this).closest('tr');
                var row = table.row(tr);

                if (row.child.isShown()) {

                    row.child.hide();
                    tr.removeClass('shown');

                } else {

                    row.child('<div class="text-center">Cargando...</div>').show();
                    tr.addClass('shown');

                    $.get(`/inventarios/${row.data().EmpleadoID}/inventario`, function(data) {

                        row.child(data).show();

                    });

                }

            });

        });
    </script>

    @endpush

</div>