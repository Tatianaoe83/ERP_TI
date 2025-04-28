@extends('layouts.app')

@section('content')
<div class="col-xs-12 col-sm-12 col-md-12">
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Empleados</h3>
        </div>
        <div class="card-body">
            @push('third_party_stylesheets')
               <!-- css -->
               <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
               <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
            @endpush

            
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="row">
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                            <label>Nombre empleado:</label>
                            <input type="text" class="form-control" id="filtro-nombre">
                            </div>

                           

                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                            <label>Obra empleado:</label>
                            <input type="text" class="form-control" id="filtro-obra">
                            </div>

                            
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6 col-sm-6 col-md-6">

                            <div class="form-group">
                            <label>Puesto inventario:</label>
                            <input type="text" class="form-control" id="filtro-puesto">
                            </div>

                           
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                            <label>Buscar Inventario:</label>
                            <input type="text" class="form-control" id="filtro-inventario">
                            </div>

                           
                        </div>
                    </div>
                </div>
            </div>
            
           

            <table id="tabla-empleados" class="table">
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
                
                <!-- DataTables Core -->
                <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
                
                <!-- DataTables Buttons -->
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
                
                <!-- JSZIP y PDFMake para exportación -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

        
            <script>
            $(document).ready(function () {
                var table = $('#tabla-empleados').DataTable({
                    searching: false,
                    ajax: {
                        url: '{{ route("inventarios.indexVista") }}',
                        data: function (d) {
                            d.nombre = $('#filtro-nombre').val();
                            d.obra = $('#filtro-obra').val();
                            d.puesto = $('#filtro-puesto').val();
                            d.filtro_inventario = $('#filtro-inventario').val();
                        }
                    },
                    columns: [
                        {
                            className: 'dt-control',
                            orderable: false,
                            data: null,
                            defaultContent: '',
                        },
                        { data: 'NombreEmpleado' },
                        { data: 'nombre_puesto' },
                        { data: 'nombre_obra' },
                        { data: 'NumTelefono' },
                        { data: 'Correo' },
                        { data: 'Estado' },
                        { data: 'action', orderable: false, searchable: false }
                    ]
                });

                $('#filtro-nombre, #filtro-obra, #filtro-puesto, #filtro-inventario').on('keyup change', function () {
                    table.ajax.reload();
                });

                // Expandir detalles
                $('#tabla-empleados tbody').on('click', 'td.dt-control', function () {
                    var tr = $(this).closest('tr');
                    var row = table.row(tr);

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        row.child('<div class="text-center">Cargando...</div>').show();
                        tr.addClass('shown');

                        $.get(`/inventarios/${row.data().EmpleadoID}/inventario`, function (data) {
                            row.child(data).show();
                        });
                    }
                });
            });
            </script>
               
            @endpush
        </div>
    </div>
</div>
@endsection