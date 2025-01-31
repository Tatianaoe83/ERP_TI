<ul class="nav nav-tabs" id="myTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" data-toggle="tab" href="#empleados">Empleado</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#equipo">Equipo de cómputo</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#insumo">Insumo</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#linea">Línea de telefonía</a>
  </li>
</ul>

<div class="tab-content mt-3">




    <!-- TAB Empleado -->
    <div class="tab-pane fade show active" id="empleados">
        <div class="row">
            <!-- NombreEmpleado Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('NombreEmpleado', 'Nombre del Empleado:') !!}
                {!! Form::text('NombreEmpleado', old('NombreEmpleado', $inventario->NombreEmpleado ?? ''), ['class' => 'form-control', 'maxlength' => 100]) !!}
            </div>

             <!-- UnidadNegocio Field -->
             <div class="form-group col-sm-6">
                {!! Form::label('UnidadNegocioID', 'Unidad de Negocio:') !!}

                {!!Form::select('UnidadNegocioID',App\Models\UnidadesDeNegocio::all()->
                    pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}
            </div>

              <!-- UnidadNegocio Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('GerenciaID', 'Gerencia:') !!}

                {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
                    pluck('NombreGerencia','GerenciaID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}
            </div>

              <!-- ObraID Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('ObraID', 'Obra:') !!}

                {!!Form::select('ObraID',App\Models\Obras::all()->
                    pluck('NombreObra','ObraID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

               
            </div>

              <!-- ObraID Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('DepartamentoID', 'Departamento:') !!}

                {!!Form::select('DepartamentoID',App\Models\Departamentos::all()->
                    pluck('NombreDepartamento','DepartamentoID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}
            </div>


            <!-- PuestoID Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('PuestoID', 'Puesto:') !!}
                {!!Form::select('PuestoID',App\Models\Puestos::all()->
                    pluck('NombrePuesto','PuestoID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}
            </div>

          
            <!-- NumTelefono Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('NumTelefono', 'Número de Teléfono:') !!}
                {!! Form::text('NumTelefono', old('NumTelefono', $inventario->NumTelefono ?? ''), ['class' => 'form-control', 'maxlength' => 50]) !!}
            </div>

            <!-- Correo Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('Correo', 'Correo Electrónico:') !!}
                {!! Form::email('Correo', old('Correo', $inventario->Correo ?? ''), ['class' => 'form-control', 'maxlength' => 150]) !!}
            </div>

           
        </div>
    </div>

    <!-- TAB Equipo de Computo -->
    <div class="tab-pane fade" id="equipo">
       
            <div class="row">
            <!-- equiposAsignados Disponibles -->
            <p class="lead mt-4">Equipos Disponibles</p>
            <div class="drag-area" id="disponibles">
            <div class="table-responsive">
                <table id="equiposTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Asignar</th>
                            <th>Categoria</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Caracteristicas</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equipos as $equipo)
                        <tr>
                            <td>
                                <a href="#" class="btn btn-outline-success btn-xs ">
                                    <i class="fas fa-plus-square"></i>
                                </a>
                            </td>
                            <td>{{ $equipo->CategoriaID }}</td>
                            <td>{{ $equipo->Marca }}</td>
                            <td>{{ $equipo->Modelo }}</td>
                            <td>{{ $equipo->Caracteristicas }}</td>
                            <td>{{ $equipo->Precio }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

            <!-- equiposAsignados Seleccionados -->
            <p class="lead mt-4">Equipos Asignados</p>

                <div class="table-responsive">
                    <table id="equiposAsignadosTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Categoria Equipo</th>
                                <th>Marca</th>
                                <th>Caracteristicas</th>
                                <th>Modelo</th>
                                <th>Precio</th>
                                <th>Fecha Asignacion</th>
                                <th>Fecha de Compra</th>
                                <th>Num. Serie</th>
                                <th>Folio</th>
                                <th>Marca</th>
                                <th>Gerencia Equipo</th>
                                <th>Comentarios</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($equiposAsignados as $equiposAsignado)
                            <tr data-id="{{ $equiposAsignado->InventarioID }}">
                                <td>
                                    <button class='btn btn-outline-secondary btn-xs edit-btn' data-id="{{ $equiposAsignado->InventarioID }}">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger btn-flat delete-btn" data-id="{{ $equiposAsignado->InventarioID }}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                                <td>{{ $equiposAsignado->CategoriaEquipo }}</td>
                                <td>{{ $equiposAsignado->Marca }}</td>
                                <td>{{ $equiposAsignado->Caracteristicas }}</td>
                                <td>{{ $equiposAsignado->Modelo }}</td>
                                <td>{{ $equiposAsignado->Precio }}</td>
                                <td>{{ $equiposAsignado->FechaAsignacion }}</td>
                                <td>{{ $equiposAsignado->FechaDeCompra }}</td>
                                <td>{{ $equiposAsignado->NumSerie }}</td>
                                <td>{{ $equiposAsignado->Folio }}</td>
                                <td>{{ $equiposAsignado->Marca }}</td>
                                <td>{{ $equiposAsignado->GerenciaEquipo }}</td>
                                <td>{{ $equiposAsignado->Comentarios }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <!-- TAB Insumo -->
    <div class="tab-pane fade" id="insumo">
        <p>Insumo</p>
    </div>

    <!-- TAB Línea -->
    <div class="tab-pane fade" id="linea">
        <p>Línea de telefonía</p>
    </div>

</div>

@push('third_party_stylesheets')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@push('third_party_scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#equiposTable').DataTable({
                "paging": true,      
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,     
                "searching": true,   
                "ordering": true,     
                "info": true,         
               
            });
        });
    </script>

<script>
        $(document).ready(function() {
            // Inicializar DataTables
            let table = $('#equiposAsignadosTable').DataTable({
                "paging": true,
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,
                "searching": true,
                "ordering": true,
                "info": true,
                "language": {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando página _PAGE_ de _PAGES_",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros en total)",
                    "search": "Buscar:",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                }
            });

            // Evento para abrir modal de edición
            $(document).on('click', '.edit-btn', function() {
                let row = $(this).closest('tr');
                let id = row.data('id');
                let categoria = row.find("td:eq(1)").text();
                let marca = row.find("td:eq(2)").text();
                let caracteristicas = row.find("td:eq(3)").text();

                $('#editId').val(id);
                $('#editCategoria').val(categoria);
                $('#editMarca').val(marca);
                $('#editCaracteristicas').val(caracteristicas);
                
                $('#editModal').modal('show');
                
            });

            // Evento para eliminar con confirmación
            $(document).on('click', '.delete-btn', function() {
                let row = $(this).closest('tr');
                let id = row.data('id');

                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "¡No podrás revertir esto!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Sí, eliminarlo!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        row.remove(); // Simula la eliminación en la tabla
                        Swal.fire("¡Eliminado!", "El registro ha sido eliminado.", "success");
                    }
                });
            });

            // Evento para guardar cambios del modal
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#editId').val();
                let row = $('#equiposAsignadosTable').find(`tr[data-id="${id}"]`);
                
                row.find("td:eq(1)").text($('#editCategoria').val());
                row.find("td:eq(2)").text($('#editMarca').val());
                row.find("td:eq(3)").text($('#editCaracteristicas').val());

                $('#editModal').modal('hide');
                Swal.fire("Guardado!", "Los cambios han sido guardados.", "success");
            });
        });
    </script>

@endpush
