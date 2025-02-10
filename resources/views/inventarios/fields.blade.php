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
                {!! Form::text('NombreEmpleado', old('NombreEmpleado', $inventario->NombreEmpleado ?? ''), ['class' => 'form-control', 'maxlength' => 100, 'disabled']) !!}
            </div>

             <!-- UnidadNegocio Field -->
             <div class="form-group col-sm-6">
                {!! Form::label('UnidadNegocioID', 'Unidad de Negocio:') !!}

                {!!Form::select('UnidadNegocioID',App\Models\UnidadesDeNegocio::all()->
                    pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

              <!-- UnidadNegocio Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('GerenciaID', 'Gerencia:') !!}

                {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
                    pluck('NombreGerencia','GerenciaID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

              <!-- ObraID Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('ObraID', 'Obra:') !!}

                {!!Form::select('ObraID',App\Models\Obras::all()->
                    pluck('NombreObra','ObraID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}

               
            </div>

              <!-- ObraID Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('DepartamentoID', 'Departamento:') !!}

                {!!Form::select('DepartamentoID',App\Models\Departamentos::all()->
                    pluck('NombreDepartamento','DepartamentoID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>


            <!-- PuestoID Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('PuestoID', 'Puesto:') !!}
                {!!Form::select('PuestoID',App\Models\Puestos::all()->
                    pluck('NombrePuesto','PuestoID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

          
            <!-- NumTelefono Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('NumTelefono', 'Número de Teléfono:') !!}
                {!! Form::text('NumTelefono', old('NumTelefono', $inventario->NumTelefono ?? ''), ['class' => 'form-control', 'maxlength' => 50, 'disabled']) !!}
            </div>

            <!-- Correo Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('Correo', 'Correo Electrónico:') !!}
                {!! Form::email('Correo', old('Correo', $inventario->Correo ?? ''), ['class' => 'form-control', 'maxlength' => 150, 'disabled']) !!}
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
                            

                                <button class='btn btn-outline-success btn-xs crear-btn' data-id="{{ $equipo->CategoriaID }}">
                                        <i class="fa fa-edit"></i>
                                </button>

                            </td>
                            <td>{{ $equipo->categoriaid->Categoria }}</td>
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
                                <th>Categoria</th>
                                <th>Marca</th>
                                <th>Caracteristicas</th>
                                <th>Modelo</th>
                                <th>Precio</th>
                                <th>Fecha Asignacion</th>
                                <th>Fecha de Compra</th>
                                <th>Num. Serie</th>
                                <th>Folio</th>
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
                                <td data-gerencia-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->gerenciaid->NombreGerencia }}</td>
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
        <div class="row">
            <!-- insumos Disponibles -->
            <p class="lead mt-4">Insumos Disponibles</p>
            <div class="drag-area" id="disponibles">
            <div class="table-responsive">
                <table id="insumosTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nombre Insumo</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumos as $insumo)
                        <tr>
                            
                            
                            <td>{{ $insumo->NombreInsumo }}</td>
                            
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

            <!-- insumosasignados Seleccionados -->
            <p class="lead mt-4">Insumos Asignados</p>

                <div class="table-responsive">
                    <table id="insumosAsignadosTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Categoria Insumo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insumosAsignados as $insumosAsignado)
                            <tr>
                                
                                
                                <td>{{ $insumosAsignado->CateogoriaInsumo }}</td>
                                
                            </tr>
                            @endforeach

                           
                        </tbody>
                    </table>
                </div>

            </div>
        </div>


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
            let table1_1 =  $('#equiposTable').DataTable({
                "paging": true,      
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,     
                "searching": true,   
                "ordering": true,     
                "info": true,         
               
            });
            let table2_1 = $('#insumosTable').DataTable({
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
                
            });

                        

            let table2 = $('#insumosAsignadosTable').DataTable({
                "paging": true,
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,
                "searching": true,
                "ordering": true,
                "info": true,
                
            });

           // Evento para abrir modal de edición
                $(document).on('click', '.edit-btn', function() {
                    console.log('Botón de edición clickeado');
                   
                    document.getElementById('titulo').innerHTML = 'Editar Equipo';

                    let row = $(this).closest('tr');
                    let id = row.data('id');
        
                    let categoria = row.find("td:eq(1)").text();
                    let marca = row.find("td:eq(2)").text();
                    let caracteristicas = row.find("td:eq(3)").text();
                    let modelo = row.find("td:eq(4)").text();
                    let precio = row.find("td:eq(5)").text();
                    let fecha_asigna = row.find("td:eq(6)").text();
                    let fecha_compra = row.find("td:eq(7)").text();
                    let num_serie = row.find("td:eq(8)").text();
                    let folio = row.find("td:eq(9)").text();
                    let gerencia = row.find("td:eq(10)").data('gerencia-id');
                    let comentarios = row.find("td:eq(11)").text();

                    $('#editId').val(id);
                    $('#editEmp').val('');
                    $('#editCategoria').val(categoria);
                    $('#editMarca').val(marca);
                    $('#editCaracteristicas').val(caracteristicas);
                    $('#editModelo').val(modelo);
                    $('#editPrecio').val(precio);
                    $('#editFechaAsignacion').val(fecha_asigna);
                    $('#editFechaDeCompra').val(fecha_compra);
                    $('#editNumSerie').val(num_serie);
                    $('#editFolio').val(folio);
    
                    $('#editGerenciaEquipo').val(gerencia).trigger('change');
                    $('#editComentarios').val(comentarios);
                    
                    $('#editModal').modal('show');

                });

                // Evento para abrir modal de creación
                $(document).on('click', '.crear-btn', function() {
                    let id_E = '{{ $inventario->EmpleadoID }}';
                    console.log('Botón de creación clickeado', id_E);
                    
                    // Limpiar los campos del formulario para una nueva entrada
                    $('#editForm')[0].reset();
                    $('#editGerenciaEquipo').val(null).trigger('change');

                    document.getElementById('titulo').innerHTML = 'Crear Equipo';
                    let row = $(this).closest('tr');
                    let categoria = row.find("td:eq(1)").text();
                    let marca = row.find("td:eq(2)").text();
                    let modelo = row.find("td:eq(3)").text();
                    let caracteristicas = row.find("td:eq(4)").text();
                    let precio = row.find("td:eq(5)").text();

                    $('#editCategoria').val(categoria);
                    $('#editMarca').val(marca);
                    $('#editCaracteristicas').val(caracteristicas);
                    $('#editModelo').val(modelo);
                    $('#editPrecio').val(precio);
                    $('#editId').val(''); 
                    $('#editEmp').val(id_E);
                    
                    $('#editModal').modal('show');
                });

                // Evento para guardar cambios
                $(document).on('click', '.submit_equipo', function(event) {   
                    event.preventDefault();
                    
                    // Limpiar mensajes de error previos
                    $('.error-message').remove();
                    $('.is-invalid').removeClass('is-invalid');
                    
                    // Validar el formulario
                    let form = document.getElementById('editForm');
                    let isValid = true;
                    
                    // Validar campos requeridos
                    $('#editForm [required]').each(function() {
                        if (!$(this).val()) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                        } else {
                            $(this).removeClass('is-invalid');
                        }
                    });

                    if (!isValid) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Campos requeridos',
                            text: 'Por favor complete todos los campos obligatorios',
                        });
                        return;
                    }
                    
                    let id_E = $('#editEmp').val();
                    let id = $('#editId').val();
                    let url = id ? '/inventarios/editar-equipo/' + id : '/inventarios/crear-equipo/' + id_E;
                    let method = id ? 'PUT' : 'POST';
                    
                    let formData = {
                        CategoriaEquipo: $('#editCategoria').val(),
                        GerenciaEquipoID: $('#editGerenciaEquipo').val(),
                        Marca: $('#editMarca').val(),
                        Caracteristicas: $('#editCaracteristicas').val(),
                        Modelo: $('#editModelo').val(),
                        Precio: $('#editPrecio').val(),
                        FechaAsignacion: $('#editFechaAsignacion').val(),
                        NumSerie: $('#editNumSerie').val(),
                        Folio: $('#editFolio').val(),
                        FechaDeCompra: $('#editFechaDeCompra').val(),
                        Comentarios: $('#editComentarios').val(),
                    };

                    let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.errors) {
                            // Mostrar errores de validación
                            Object.keys(data.errors).forEach(field => {
                                const input = $(`#edit${field}`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(data.errors[field][0]);
                            });
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de validación',
                                text: 'Por favor revise los campos marcados en rojo',
                            });
                        } else {
                            // Éxito
                            Swal.fire({
                                position: "top-end",
                                icon: "success",
                                title: "Datos del equipo guardados correctamente",
                                showConfirmButton: false,
                                timer: 1500
                            });
                            $('#editModal').modal('hide');
                            setTimeout(function(){
                                location.reload();
                            }, 1600);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Ocurrió un error al guardar los datos",
                        });
                    });
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

        
        });
    </script>

    <!-- Agregar un poco de CSS para los campos requeridos -->
    <style>
        .form-group label:after {
            content: " *";
            color: red;
        }
        .form-group:not(:has([required])) label:after {
            content: "";
        }
    </style>

@endpush
