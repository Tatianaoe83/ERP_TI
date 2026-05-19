@extends('layouts.app')

@section('content')
<div class="col-xs-12 col-sm-12 col-md-12">

    <h3 class="text-[#101D49] dark:text-white">Inventario</h3>

    @push('third_party_stylesheets')
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">

            <div class="row">

                <!-- Nombre -->
                <div class="col-xs-6 col-sm-5 col-md-7">
                    <div class="form-group">
                        <label class="text-[#101D49] dark:text-white">
                            Nombre empleado:
                        </label>

                        <input type="text"
                            class="form-control"
                            id="filtro-nombre"
                            placeholder="Buscar empleado...">
                    </div>
                </div>

                <!-- Obra -->
                <div class="col-xs-6 col-sm-4 col-md-5">
                    <div class="form-group">

                        <label class="text-[#101D49] dark:text-white">
                            Obra empleado:
                        </label>

                        <select id="filtro-obra" class="jz1 form-control">
                            <option value="">Todas las obras</option>
                        </select>

                    </div>
                </div>

            </div>

            <div class="row">

                <!-- Puesto -->
                <div class="col-xs-6 col-sm-6 col-md-3">

                    <div class="form-group">

                        <label class="text-[#101D49] dark:text-white">
                            Puesto empleado:
                        </label>

                        <select id="filtro-puesto" class="jz1 form-control">
                            <option value="">Todos los puestos</option>
                        </select>

                    </div>

                </div>

                <!-- Inventario -->
                <div class="col-xs-6 col-sm-6 col-md-3">

                    <div class="form-group">

                        <label class="text-[#101D49] dark:text-white">
                            Buscar Inventario:
                        </label>

                        <input type="text"
                            class="form-control"
                            id="filtro-inventario">

                    </div>

                </div>

                <!-- Tipo Persona -->
                <div class="col-xs-6 col-sm-6 col-md-3">

                    <div class="form-group">

                        <label class="text-[#101D49] dark:text-white">
                            Tipo de Persona:
                        </label>

                        <select class="form-control text-secondary"
                            id="filtro-persona">

                            <option value="" selected>
                                Seleccionar tipo de persona
                            </option>
                            <option value="FISICA">
                                FISICA
                            </option>

                            <option value="REFERENCIADO">
                                REFERENCIADO
                            </option>

                            <option value="EXTRAORDINARIO">
                                EXTRAORDINARIO
                            </option>

                        </select>

                    </div>

                </div>

                <!-- Estatus -->
                <div class="col-xs-6 col-sm-6 col-md-3">

                    <div class="form-group">

                        <label class="text-[#101D49] dark:text-white">
                            Estatus:
                        </label>

                        <select class="form-control" id="filtro-estatus">
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                            <option value="">Todos</option>
                        </select>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <table id="tabla-empleados" class="table" style="width: 100%;">
        <thead>
            <tr>
                <th></th>
                <th>Nombre</th>
                <th>Puesto</th>
                <th>Obra</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
    </table>

    @push('third_party_scripts')

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

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
            $('.jz1').select2({
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
                pageLength: 7,

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

                    // =========================
                    // CARGAR OBRAS
                    // =========================
                    var obras = api.column(3).data().unique().sort();

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
                    var puestos = api.column(2).data().unique().sort();

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
                    $('.jz1').select2({
                        width: '100%',
                        placeholder: 'Seleccionar...',
                        allowClear: true
                    });

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
@endsection