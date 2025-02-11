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
                                    {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroy', $equiposAsignado->InventarioID], 'style' => 'display:inline']) !!}
                                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn']) !!}
                                    {!! Form::close() !!}

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

    <!-- TAB Insumo -->
    <div class="tab-pane fade" id="insumo">
       
        <div class="row">
            <!-- insumos Disponibles -->
            <p class="lead mt-4">Insumos Disponibles</p>
            <div class="drag-area" id="disponibles">
            <div class="table-responsive">
                <table id="insumosTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Asignar</th>
                            <th>Categoria Insumo </th>
                            <th>Nombre Insumo</th>
                            <th>Costo Mensual</th>
                            <th>Costo Anual</th>
                            <th>Frecuencia de Pago</th>
                            <th>Observaciones</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumos as $insumo)
                        <tr>
                            <td>
                                

                                <button class='btn btn-outline-success btn-xs crear-btn-insumo' data-id="{{ $insumo->CategoriaID }}">
                                        <i class="fa fa-edit"></i>
                                </button>

                            </td>

                            <td>{{ $insumo->categoriaid->Categoria }}</td>
                            <td>{{ $insumo->NombreInsumo }}</td>
                            <td>{{ $insumo->CostoMensual }}</td>
                            <td>{{ $insumo->CostoAnual }}</td>
                            <td>{{ $insumo->FrecuenciaDePago }}</td>
                            <td>{{ $insumo->Observaciones }}</td>
                            
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
                                <th>Action</th>
                                <th>Categoria Insumo</th>
                                <th>Nombre Insumo</th>
                                <th>Costo Mensual</th>
                                <th>Costo Anual</th>
                                <th>Frecuencia de Pago</th>
                                <th>Observaciones</th>
                                <th>Fecha de Asignacion</th>
                                <th>Num. Serie</th>
                                <th>Comentarios</th>
                                <th>Mes de pago </th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insumosAsignados as $insumosAsignado)
                            <tr data-id="{{ $insumosAsignado->InventarioID }}">
                                <td>
                                    <button class='btn btn-outline-secondary btn-xs edit-btn-insum' data-id="{{ $insumosAsignado->InventarioID }}">
                                        <i class="fa fa-edit"></i>
                                    </button>
        
                                    {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroyInsumo', $insumosAsignado->InventarioID], 'style' => 'display:inline']) !!}
                                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn-insumo']) !!}
                                    {!! Form::close() !!}

                                </td>
                                
                                
                                <td>{{ $insumosAsignado->CateogoriaInsumo }}</td>
                                <td>{{ $insumosAsignado->NombreInsumo }}</td>
                                <td>{{ $insumosAsignado->CostoMensual }}</td>
                                <td>{{ $insumosAsignado->CostoAnual }}</td>
                                <td>{{ $insumosAsignado->FrecuenciaDePago }}</td>
                                <td>{{ $insumosAsignado->Observaciones }}</td>
                                <td>{{ $insumosAsignado->FechaAsignacion }}</td>
                                <td>{{ $insumosAsignado->NumSerie }}</td>
                                <td>{{ $insumosAsignado->Comentarios }}</td>
                                <td>{{ $insumosAsignado->MesDePago }}</td>
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
       
       <div class="row">
           <!-- lineas Disponibles -->
           <p class="lead mt-4">Lineas Disponibles</p>
           <div class="drag-area" id="disponibles">
           <div class="table-responsive">
               <table id="lineasTable" class="table table-bordered table-striped">
                   <thead>
                       <tr>
                           <th>Asignar</th>
                           <th>Num. Telefonico</th>
                            <th>Plan</th>
                            <th>Cuenta Padre</th>
                            <th>Cuenta Hija</th>
                            <th>Tipo Linea</th>
                            <th>Obra</th>
                            <th>Fecha Fianza</th>
                            <th>Costo Fianza</th>
                            <th>Activo</th>
                            <th>Disponible</th>
                            <th>Monto Renovacion Fianza</th>
                       
                          
                           
                       </tr>
                   </thead>
                   <tbody>
                       @foreach ($Lineas as $Linea)
                       <tr>
                           <td>
                               

                               <button class='btn btn-outline-success btn-xs crear-btn-linea' data-id="{{ $Linea->LineaID }}">
                                       <i class="fa fa-edit"></i>
                               </button>

                           </td>

                    
                           <td>{{ $Linea->NumTelefonico}}</td>
                            <td>{{ $Linea->PlanID}}</td>
                            <td>{{ $Linea->CuentaPadre}}</td>
                            <td>{{ $Linea->CuentaHija}}</td>
                            <td>{{ $Linea->TipoLinea}}</td>
                            <td>{{ $Linea->ObraID}}</td>
                            <td>{{ $Linea->FechaFianza}}</td>
                            <td>{{ $Linea->CostoFianza}}</td>
                            <td>
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckCheckedDisabled1" checked disabled>
                                <label class="form-check-label" for="flexCheckCheckedDisabled1">
                                </label>

                            </td>
                            <td>
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckCheckedDisabled" checked disabled>
                                <label class="form-check-label" for="flexCheckCheckedDisabled">
                                </label>

                            </td>
                            <td>{{ $Linea->MontoRenovacionFianza}}</td>
                           
                       </tr>
                       @endforeach
                   </tbody>
               </table>
           </div>
       </div>

           <!-- lineasasignados Seleccionados -->
           <p class="lead mt-4">Lineas Asignados</p>

               <div class="table-responsive">
                   <table id="lineasAsignadosTable" class="table table-bordered table-striped">
                       <thead>
                           <tr>
                               <th>Action</th>    
                                <th>Num Telefonico</th>
                                <th>Compania</th>
                                <th>PlanTel</th>
                                <th>Costo Renta Mensual</th>
                                <th>Cuenta Padre</th>
                                <th>Cuenta Hija</th>
                                <th>Tipo Linea</th>
                                <th>Obra</th>
                                <th>FechaFianza</th>
                                <th>CostoFianza</th>
                                <th>FechaAsignacion</th>
                                <th>Estado</th>
                                <th>Comentarios</th>
                                <th>MontoRenovacionFianza</th>
                                <th>LineaID</th>
                              

                           </tr>
                       </thead>
                       <tbody>
                           @foreach ($LineasAsignados as $LineasAsignado)
                           <tr data-id="{{ $LineasAsignado->InventarioID }}">
                               <td>
                                   <button class='btn btn-outline-secondary btn-xs edit-btn-linea' data-id="{{ $LineasAsignado->LineaID }}">
                                       <i class="fa fa-edit"></i>
                                   </button>
       
                                   {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroylinea', $LineasAsignado->InventarioID], 'style' => 'display:inline']) !!}
                                       {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn-linea']) !!}
                                   {!! Form::close() !!}

                               </td>
                               
                            
                                <td>{{ $LineasAsignado->NumTelefonico}}</td>
                                <td>{{ $LineasAsignado->Compania}}</td>
                                <td>{{ $LineasAsignado->PlanTel}}</td>
                                <td>{{ $LineasAsignado->CostoRentaMensual}}</td>
                                <td>{{ $LineasAsignado->CuentaPadre}}</td>
                                <td>{{ $LineasAsignado->CuentaHija}}</td>
                                <td>{{ $LineasAsignado->TipoLinea}}</td>
                                <td>{{ $LineasAsignado->Obra}}</td>
                                <td>{{ $LineasAsignado->FechaFianza}}</td>
                                <td>{{ $LineasAsignado->CostoFianza}}</td>
                                <td>{{ $LineasAsignado->FechaAsignacion}}</td>
                                <td>{{ $LineasAsignado->Estado}}</td>
                                <td>{{ $LineasAsignado->Comentarios}}</td>
                                <td>{{ $LineasAsignado->MontoRenovacionFianza}}</td>
                                <td>{{ $LineasAsignado->LineaID}}</td>
                              
                           </tr>
                           @endforeach

                          
                       </tbody>
                   </table>
               </div>

           </div>
       </div>


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

            let table3_1 = $('#lineasTable').DataTable({
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

            let table3 = $('#lineasAsignadosTable').DataTable({
                "paging": true,
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,
                "searching": true,
                "ordering": true,
                "info": true,
                
            });

       
                $(document).on('click', '.edit-btn', function() {
    
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

              
                $(document).on('click', '.crear-btn', function() {
                    let id_E = '{{ $inventario->EmpleadoID }}';

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
                    
                    $('#editModal').modal('show');a
                });

                $(document).on('click', '.submit_equipo', function(event) {   
                    event.preventDefault();
                    
                    $('.error-message').remove();
                    $('.is-invalid').removeClass('is-invalid');
                    
                    let form = document.getElementById('editForm');
                    let isValid = true;
                    
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

            $('.delete-btn').click(function(event) {
                var form =  $(this).closest("form");
                event.preventDefault();
                swal.fire({
                    title: `Eliminar `,
                    text: "¿Realmente desea eliminar este equipo asignado?",
                    icon: "warning",
                    //buttons: true,
                    showDenyButton: true,
                    confirmButtonText: 'Confirmar',
                    denyButtonText: `Cerrar`,
                    dangerMode: true,
                }).then(function(willDelete) {
                    if (willDelete.isConfirmed) {
                    swal.fire({
                        title: 'Hecho!',
                        text: "Se han guardado los cambios",
                        icon: 'success'
                        }).then(function(){
                        form.submit();
                        });
                    }else if (willDelete.isDenied){
                        swal.fire("Cambios no generados");
                    }
                });
            });


            /*insum*/

               $(document).on('click', '.edit-btn-insum', function() {
    
                   
                document.getElementById('tituloinsumo').innerHTML = 'Editar Insumo';

                let row = $(this).closest('tr');
                let id = row.data('id');
                let categoria = row.find("td:eq(1)").text();
                let nombreinsumo = row.find("td:eq(2)").text();
                let costomensual = row.find("td:eq(3)").text();
                let costoanual = row.find("td:eq(4)").text();
                let frecuenciadepago = row.find("td:eq(5)").text();
                let observaciones = row.find("td:eq(6)").text();
                let fechadeasignacion = row.find("td:eq(7)").text();
                let numserie = row.find("td:eq(8)").text();
                let comentarios = row.find("td:eq(9)").text();
                let mespago = row.find("td:eq(10)").text();
                

                $('#editId_insumo').val(id);
                $('#editEmp_insumo').val('');
                $('#editCategoriaInsumo').val(categoria);
                $('#editNombreInsumo').val(nombreinsumo);
                $('#editCostoMensual').val(costomensual);
                $('#editCostoAnual').val(costoanual);
                $('#editFrecuenciaDePago').val(frecuenciadepago);
                $('#editobserv').val(observaciones);
                $('#editFechaDeAsigna').val(fechadeasignacion);
                $('#editNumSerieInsu').val(numserie);
                $('#editComentariosInsumo').val(comentarios);
                $('#editMesDePago').val(mespago);
                
                $('#editModalInsumo').modal('show');

            });

            $(document).on('click', '.crear-btn-insumo', function() {
                let id_E = '{{ $inventario->EmpleadoID }}';
                
                $('#editFormInsumo')[0].reset();
              
                document.getElementById('tituloinsumo').innerHTML = 'Crear Insumo';
                let row = $(this).closest('tr');
                let categoria = row.find("td:eq(1)").text();
                let nombreinsumo = row.find("td:eq(2)").text();
                let costomensual = row.find("td:eq(3)").text();
                let costoanual = row.find("td:eq(4)").text();
                let frecuenciadepago = row.find("td:eq(5)").text();
                let observaciones = row.find("td:eq(6)").text();
                
                $('#editCategoriaInsumo').val(categoria);
                $('#editNombreInsumo').val(nombreinsumo);
                $('#editCostoMensual').val(costomensual);
                $('#editCostoAnual').val(costoanual);
                $('#editFrecuenciaDePago').val(frecuenciadepago);
                $('#editobserv').val(observaciones);
              
                $('#editId_insumo').val(''); 
                $('#editEmp_insumo').val(id_E);
                
                $('#editModalInsumo').modal('show');
            });

            $(document).on('click', '.submit_insumo', function(event) {   
                event.preventDefault();
                
                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');
                
                let form = document.getElementById('editFormInsumo');
                let isValid = true;

                $('#editFormInsumo [required]').each(function() {
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
                
                let id_E = $('#editEmp_insumo').val();
                let id = $('#editId_insumo').val();

                let url = id ? '/inventarios/editar-insumo/' + id : '/inventarios/crear-insumo/' + id_E;
                let method = id ? 'PUT' : 'POST';
                
                let formData = {
                    CateogoriaInsumo: $('#editCategoriaInsumo').val(),
                    NombreInsumo: $('#editNombreInsumo').val(),
                    CostoMensual: $('#editCostoMensual').val(),
                    CostoAnual: $('#editCostoAnual').val(),
                    FrecuenciaDePago: $('#editFrecuenciaDePago').val(),
                    Observaciones: $('#editobserv').val(),
                    FechaAsignacion: $('#editFechaDeAsigna').val(),
                    NumSerie: $('#editNumSerieInsu').val(),
                    Comentarios: $('#editComentariosInsumo').val(),
                    MesDePago: $('#editMesDePago').val(),
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
                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: "Datos del insumo guardado correctamente",
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('#editModalInsumo').modal('hide');
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

            $('.delete-btn-insumo').click(function(event) {
            var form =  $(this).closest("form");
            event.preventDefault();
            swal.fire({
                title: `Eliminar `,
                text: "¿Realmente desea eliminar este insumo asignado?",
                icon: "warning",
                //buttons: true,
                showDenyButton: true,
                confirmButtonText: 'Confirmar',
                denyButtonText: `Cerrar`,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete.isConfirmed) {
                swal.fire({
                    title: 'Hecho!',
                    text: "Se han guardado los cambios",
                    icon: 'success'
                    }).then(function(){
                    form.submit();
                    });
                }else if (willDelete.isDenied){
                    swal.fire("Cambios no generados");
                }
            });
            });


            /*telefono*/

               $(document).on('click', '.edit-btn-linea', function() {

                document.getElementById('titulolinea').innerHTML = 'Editar Linea';

                let row = $(this).closest('tr');
                let id = row.data('id');
            
                let comentarios = row.find("td:eq(13)").text();
                let FechaAsignacion = row.find("td:eq(11)").text();
                

                $('#editfechalinea').val(FechaAsignacion);
                $('#editcomenl').val(comentarios);
                $('#editId_linea').val(id);
                $('#editEmp_linea').val('');
                $('#lineaid').val('');
        
                   
                $('#editModalLinea').modal('show');

            });

            $(document).on('click', '.crear-btn-linea', function() {
                let id_E = '{{ $inventario->EmpleadoID }}';
                
               console.log('click linea');
                
                $('#editFormLinea')[0].reset();
                document.getElementById('titulolinea').innerHTML = 'Crear Linea';
               
                let row = $(this).closest('tr');
                let id = row.data('id');
                console.log (id);

                $('#editId_linea').val(''); 
                $('#editEmp_linea').val(id_E);
                $('#lineaid').val(id);
                
                $('#editModalLinea').modal('show');
            });

            $(document).on('click', '.submit_linea', function(event) {   
                event.preventDefault();
                
                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');
                
                let form = document.getElementById('editFormLinea');
                let isValid = true;

                $('#editFormLinea [required]').each(function() {
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

                let id_E = $('#editEmp_linea').val();
                let id = $('#editId_linea').val();

                console.log(id_E, id);
                let url = id ? '/inventarios/editar-linea/' + id : '/inventarios/crear-linea/' + id_E;
                let method = id ? 'PUT' : 'POST';
                
                let formData = {
                    FechaAsignacion: $('#editfechalinea').val(),
                    Comentarios: $('#editcomenl').val(),
                    Linea : $('#lineaid').val()
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
                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: "Datos del insumo guardado correctamente",
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('#editModalLinea').modal('hide');
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
            $('.delete-btn-linea').click(function(event) {
            var form =  $(this).closest("form");
            event.preventDefault();
            swal.fire({
                title: `Eliminar `,
                text: "¿Realmente desea eliminar este linea asignado?",
                icon: "warning",
                //buttons: true,
                showDenyButton: true,
                confirmButtonText: 'Confirmar',
                denyButtonText: `Cerrar`,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete.isConfirmed) {
                swal.fire({
                    title: 'Hecho!',
                    text: "Se han guardado los cambios",
                    icon: 'success'
                    }).then(function(){
                    form.submit();
                    });
                }else if (willDelete.isDenied){
                    swal.fire("Cambios no generados");
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
