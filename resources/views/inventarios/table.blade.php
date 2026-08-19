<div>

    @push('third_party_stylesheets')
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

    <div class="inv-leyenda">
        <div class="inv-leyenda-item">
            <span class="inv-tipo-badge inv-tipo-fisica">Física</span>
            <div><strong>Persona física</strong>Stock (asignado actual) + Extra (presupuesto futuro).</div>
        </div>
        <div class="inv-leyenda-item">
            <span class="inv-tipo-badge inv-tipo-referenciado">Referenciado</span>
            <div><strong>Gerencia / referenciado</strong>Solo stock. Tiene asignaciones actuales, sin extras.</div>
        </div>
        <div class="inv-leyenda-item">
            <span class="inv-tipo-badge inv-tipo-extraordinario">Extraordinario</span>
            <div><strong>Plaza extraordinaria</strong>Todo es Extra (presupuesto futuro).</div>
        </div>
    </div>

    <div class="index-page__filters" style="padding: 0.9rem 1rem 0;">
        <div class="form-group">
            <label for="filtro-nombre">Nombre empleado</label>
            <input type="text" class="form-control" id="filtro-nombre" placeholder="Buscar empleado...">
        </div>
        <div class="form-group">
            <label for="filtro-obra">Obra</label>
            <select id="filtro-obra" class="jz-inv form-control">
                <option value="">Todas las obras</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro-puesto">Puesto</label>
            <select id="filtro-puesto" class="jz-inv form-control">
                <option value="">Todos los puestos</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro-inventario">Inventario</label>
            <input type="text" class="form-control" id="filtro-inventario" placeholder="Buscar inventario...">
        </div>
        <div class="form-group">
            <label for="filtro-persona">Tipo de persona</label>
            <select class="form-control jz-inv" id="filtro-persona">
                <option value="" selected>Físicas y referenciados (default)</option>
                <option value="FISICA">Física — stock + extra</option>
                <option value="REFERENCIADO">Referenciado — solo stock</option>
                <option value="EXTRAORDINARIO">Extraordinario — todo extra</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro-estatus">Estatus</label>
            <select class="form-control" id="filtro-estatus">
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
                <option value="2">Todos</option>
            </select>
        </div>
    </div>

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

    <!-- Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

    <!-- Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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