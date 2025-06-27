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
                    pluck('NombreEmpresa','UnidadNegocioID'),$inventario->UnidadNegocioID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

              <!-- UnidadNegocio Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('GerenciaID', 'Gerencia:') !!}

                {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
                    pluck('NombreGerencia','GerenciaID'),$inventario->GerenciaID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

              <!-- ObraID Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('ObraID', 'Obra:') !!}

                {!!Form::select('ObraID',App\Models\Obras::all()->
                    pluck('NombreObra','ObraID'),$inventario->ObraID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}

               
            </div>

              <!-- ObraID Field -->
              <div class="form-group col-sm-6">
                {!! Form::label('DepartamentoID', 'Departamento:') !!}

                {!!Form::select('DepartamentoID',App\Models\Departamentos::all()->
                    pluck('NombreDepartamento','DepartamentoID'),$inventario->DepartamentoID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>


            <!-- PuestoID Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('PuestoID', 'Puesto:') !!}
                {!!Form::select('PuestoID',App\Models\Puestos::all()->
                    pluck('NombrePuesto','PuestoID'),$inventario->PuestoID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
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

              <!-- equiposAsignados Seleccionados -->
              <span class="badge badge-success " style="margin-bottom: 15px;margin-top: 15px;">Equipos Asignados</span>


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
                                    {!! Form::button('<i class="fa fa-trash"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn',
                                        'data-id' => $equiposAsignado->InventarioID 
                                    ]) !!}
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
                            <td data-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->GerenciaEquipo }}</td>
                            <td>{{ $equiposAsignado->Comentarios }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- equiposAsignados Disponibles -->
            <span class="badge badge-primary" style="margin-bottom: 15px;margin-top: 15px;">Equipos Disponibles</span>

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
                                    <i class="fas fa-plus"></i>
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

          

            </div>
       

    </div>

    <!-- TAB Insumo -->
    <div class="tab-pane fade" id="insumo">
       
        <div class="row">

         <!-- insumosasignados Seleccionados -->
         <span class="badge badge-success " style="margin-bottom: 15px;margin-top: 15px;">Insumos Asignados</span>

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
                                    {!! Form::button('<i class="fa fa-trash"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn-insumo',
                                        'data-id' => $insumosAsignado->InventarioID 
                                    ]) !!}
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

            <!-- insumos Disponibles -->
            <span class="badge badge-primary" style="margin-bottom: 15px;margin-top: 15px;">Insumos Disponibles</span>
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
                                    <i class="fas fa-plus"></i>
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

           
        </div>


    </div>


        <!-- TAB Línea -->
        <div class="tab-pane fade" id="linea">
       
       <div class="row">

       <!-- lineasasignados Seleccionados -->
<span class="badge badge-success " style="margin-bottom: 15px;margin-top: 15px;">Lineas Asignados</span>

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
                    <button class='btn btn-outline-secondary btn-xs edit-btn-linea' data-id="{{ $LineasAsignado->InventarioID }}">
                        <i class="fa fa-edit"></i>
                    </button>

                    {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroylinea', $LineasAsignado->InventarioID], 'style' => 'display:inline']) !!}
                         {!! Form::button('<i class="fa fa-trash"></i>', [
                             'type' => 'submit',
                             'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn-linea',
                             'data-id' => $LineasAsignado->InventarioID 
                         ]) !!}
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

           <!-- lineas Disponibles -->
           <span class="badge badge-primary" style="margin-bottom: 15px;margin-top: 15px;">Lineas Disponibles</span>
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
                                    <i class="fas fa-plus"></i>
                               </button>

                           </td>

                    
                           <td>{{ $Linea->NumTelefonico}}</td>
                            <td>{{ $Linea->planid->NombrePlan}}</td>
                            <td>{{ $Linea->CuentaPadre}}</td>
                            <td>{{ $Linea->CuentaHija}}</td>
                            <td>{{ $Linea->TipoLinea}}</td>
                            <td>{{ $Linea->obraid->NombreObra ?? 'Sin obra'}}</td>
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

        });
    </script>

<script>

// Seccion equipo 
// Editar equipo (abriendo el modal con los datos)
$(document).on('click', '.edit-btn', function() {
    let row = $(this).closest('tr');
    let id = row.data('id');

    // Asignar valores al formulario
    document.getElementById('titulo').innerHTML = 'Editar Equipo';
    $('#editId').val(id);
    $('#editEmp').val('');
    $('#editCategoria').val(row.find("td:eq(1)").text());
    $('#editMarca').val(row.find("td:eq(2)").text());
    $('#editCaracteristicas').val(row.find("td:eq(3)").text());
    $('#editModelo').val(row.find("td:eq(4)").text());
    $('#editPrecio').val(row.find("td:eq(5)").text());
    $('#editFechaAsignacion').val(row.find("td:eq(6)").text());
    $('#editFechaDeCompra').val(row.find("td:eq(7)").text());
    $('#editNumSerie').val(row.find("td:eq(8)").text());
    $('#editFolio').val(row.find("td:eq(9)").text());
    $('#editGerenciaEquipo').val(row.find("td:eq(10)").data('id')).trigger('change');
    $('#editComentarios').val(row.find("td:eq(11)").text());

    $('#editModal').modal('show');
});

// Crear equipo (con valores vacíos para nuevo registro)
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

    $('#editModal').modal('show');
});

// Enviar formulario de edición o creación con AJAX
$(document).on('click', '.submit_equipo', function(event) {
    event.preventDefault();

    $('.error-message').remove();
    $('.is-invalid').removeClass('is-invalid');

    let isValid = true;

    // Validación de campos requeridos
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

    let id = $('#editId').val();
    let id_E = $('#editEmp').val();
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

    // Enviar datos con AJAX
    $.ajax({
        url: url,
        method: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function(response) {
            if (response.errors) {
                // Mostrar errores de validación
                Object.keys(response.errors).forEach(field => {
                    const input = $(`#edit${field}`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(response.errors[field][0]);
                });

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Por favor revise los campos marcados en rojo',
                });
            } else {
                // Si la solicitud fue exitosa, actualizar la fila correspondiente o agregar una nueva
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Datos del equipo guardados correctamente",
                    showConfirmButton: false,
                    timer: 1500
                });

                // Actualizar o agregar la fila en la tabla
                if (id) {
                    updateTableRow(response.equipo);
                } else {
                    addNewRow(response.equipo);
                }

                $('#editModal').modal('hide');
            }
        },
        error: function(error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al guardar los datos',
            });
        }
    });
});

// Actualizar una fila en la tabla después de editar
function updateTableRow(equipo) {
    let row = $(`tr[data-id=${equipo.InventarioID}]`);
    row.find('td:eq(1)').text(equipo.CategoriaEquipo);
    row.find('td:eq(2)').text(equipo.Marca);
    row.find('td:eq(3)').text(equipo.Caracteristicas);
    row.find('td:eq(4)').text(equipo.Modelo);
    row.find('td:eq(5)').text(equipo.Precio);
    row.find('td:eq(6)').text(equipo.FechaAsignacion);
    row.find('td:eq(7)').text(equipo.FechaDeCompra);
    row.find('td:eq(8)').text(equipo.NumSerie);
    row.find('td:eq(9)').text(equipo.Folio);
    row.find('td:eq(10)').text(equipo.GerenciaEquipo);
    row.find('td:eq(11)').text(equipo.Comentarios);
}

// Agregar una nueva fila en la tabla (para equipo creado)
function addNewRow(equipo) {
    let newRow = `
        <tr data-id="${equipo.InventarioID}">
            <td>
                <button class="btn btn-outline-secondary btn-xs edit-btn" data-id="${equipo.InventarioID}">
                    <i class="fa fa-edit"></i>
                </button>
                <form method="POST" action="/inventarios/destroy/${equipo.InventarioID}" style="display:inline">
                    <button type="submit" class="btn btn-xs btn-outline-danger btn-flat delete-btn" data-id="${equipo.InventarioID}">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
            </td>
            <td>${equipo.CategoriaEquipo}</td>
            <td>${equipo.Marca}</td>
            <td>${equipo.Caracteristicas}</td>
            <td>${equipo.Modelo}</td>
            <td>${equipo.Precio}</td>
            <td>${equipo.FechaAsignacion}</td>
            <td>${equipo.FechaDeCompra}</td>
            <td>${equipo.NumSerie}</td>
            <td>${equipo.Folio}</td>
            <td>${equipo.GerenciaEquipo}</td>
            <td>${equipo.Comentarios}</td>
        </tr>
    `;
    $('#equiposAsignadosTable tbody').append(newRow);
}

// Eliminar equipo con AJAX
$(document).on('click', '.delete-btn', function(event) {
    event.preventDefault();

    var id = $(this).data('id'); // ✅ Obtener el ID del botón delete-btn

    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el ID del equipo.',
        });
        return;
    }

    Swal.fire({
        title: `Eliminar`,
        text: "¿Realmente desea eliminar este equipo asignado?",
        icon: "warning",
        showDenyButton: true,
        confirmButtonText: 'Confirmar',
        denyButtonText: 'Cerrar',
        dangerMode: true,
    }).then(function(willDelete) {
        if (willDelete.isConfirmed) {
            $.ajax({
                url: `/inventarios/${id}`,  // ✅ Se pasa el ID en la URL correctamente
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Eliminado!',
                            text: "El equipo fue eliminado correctamente.",
                            icon: 'success'
                        });

                        // Eliminar la fila de la tabla
                        $(`tr[data-id=${id}]`).remove();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo eliminar el equipo',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al eliminar el equipo.',
                    });
                }
            });
        }
    });
});

// Fin seccion equipo 

// Seccion insumo

$(document).on('click', '.edit-btn-insum', function() {
    let row = $(this).closest('tr');
    let id = row.data('id');

    // Asignar valores al formulario
    document.getElementById('tituloinsumo').innerHTML = 'Editar insumo';

    $('#editId_insumo').val(id);
    $('#editEmp_insumo').val('');
    $('#editCategoriaInsumo').val(row.find("td:eq(1)").text());
    $('#editNombreInsumo').val(row.find("td:eq(2)").text());
    $('#editCostoMensual').val(row.find("td:eq(3)").text());
    $('#editCostoAnual').val(row.find("td:eq(4)").text());
    $('#editFrecuenciaDePago').val(row.find("td:eq(5)").text());
    $('#editobserv').val(row.find("td:eq(6)").text());
    $('#editFechaDeAsigna').val(row.find("td:eq(7)").text());
    $('#editNumSerieInsu').val(row.find("td:eq(8)").text());
    $('#editComentariosInsumo').val(row.find("td:eq(9)").text());
    $('#editMesDePago').val(row.find("td:eq(10)").text());

    $('#editModalInsumo').modal('show');
});

$(document).on('click', '.crear-btn-insumo', function() {
    let id_E = '{{ $inventario->EmpleadoID }}';

    $('#editFormInsumo')[0].reset();
    
    document.getElementById('tituloinsumo').innerHTML = 'Crear insumo';
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

    let id = $('#editId_insumo').val();
    let id_E = $('#editEmp_insumo').val();
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

    $.ajax({
        url: url,
        method: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function(response) {
            if (response.errors) {
                
                Object.keys(response.errors).forEach(field => {
                    const input = $(`#edit${field}`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(response.errors[field][0]);
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

           
                if (id) {
                    updateisnumoTableRow(response.insumo);
                } else {
                    addinsumoNewRow(response.insumo);
                }

                $('#editModalInsumo').modal('hide');
            }
        },
        error: function(error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al guardar los datos',
            });
        }
    });
});


function updateisnumoTableRow(insumo) {
    let row = $(`tr[data-id=${insumo.InventarioID}]`);
    row.find('td:eq(1)').text(insumo.Categoriainsumo);
    row.find('td:eq(2)').text(insumo.Marca);
    row.find('td:eq(3)').text(insumo.Caracteristicas);
    row.find('td:eq(4)').text(insumo.Modelo);
    row.find('td:eq(5)').text(insumo.Precio);
    row.find('td:eq(6)').text(insumo.FechaAsignacion);
    row.find('td:eq(7)').text(insumo.FechaDeCompra);
    row.find('td:eq(8)').text(insumo.NumSerie);
    row.find('td:eq(9)').text(insumo.Folio);
    row.find('td:eq(10)').text(insumo.Gerenciainsumo);
    row.find('td:eq(11)').text(insumo.Comentarios);
}


function addinsumoNewRow(insumo) {
    let newRow = `
        <tr data-id="${insumo.InventarioID}">
            <td>
                <button class="btn btn-outline-secondary btn-xs edit-btn-insum" data-id="${insumo.InventarioID}">
                    <i class="fa fa-edit"></i>
                </button>
                <form method="POST" action="/inventarios/deleteInsumo/${insumo.InventarioID}" style="display:inline">
                    <button type="submit" class="btn btn-xs btn-outline-danger btn-flat delete-btn-insumo" data-id="${insumo.InventarioID}">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
            </td>
            <td>${insumo.CateogoriaInsumo}</td>
            <td>${insumo.NombreInsumo}</td>
            <td>${insumo.CostoMensual}</td>
            <td>${insumo.CostoAnual}</td>
            <td>${insumo.FrecuenciaDePago}</td>
            <td>${insumo.Observaciones}</td>
            <td>${insumo.FechaAsignacion}</td>
            <td>${insumo.NumSerie}</td>
            <td>${insumo.Comentarios}</td>
            <td>${insumo.MesDePago}</td>
        </tr>
    `;
    $('#insumosAsignadosTable tbody').append(newRow);
}

$(document).on('click', '.delete-btn-insumo', function(event) {
    event.preventDefault();

    var id = $(this).data('id');

    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el ID del insumo.',
        });
        return;
    }

    Swal.fire({
        title: `Eliminar`,
        text: "¿Realmente desea eliminar este insumo asignado?",
        icon: "warning",
        showDenyButton: true,
        confirmButtonText: 'Confirmar',
        denyButtonText: 'Cerrar',
        dangerMode: true,
    }).then(function(willDelete) {
        if (willDelete.isConfirmed) {
            $.ajax({
                url: `/inventarios/deleteInsumo/${id}`, 
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Eliminado!',
                            text: "El insumo fue eliminado correctamente.",
                            icon: 'success'
                        });

                        // Eliminar la fila de la tabla
                        $(`tr[data-id=${id}]`).remove();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo eliminar el insumo',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al eliminar el insumo.',
                    });
                }
            });
        }
    });
});

// Fin Seccion insumo

// Seccion telefono

$(document).on('click', '.edit-btn-linea', function() {
    let row = $(this).closest('tr');
    let id = row.data('id');
    
    document.getElementById('titulolinea').innerHTML = 'Editar Linea';

    // Asignar valores al formulario
    $('#editId_linea').val(id);
    $('#editId_linea2').val(''); 
    $('#editEmp_linea').val('');
    $('#editcomenl').val(row.find("td:eq(13)").text());
    $('#editfechalinea').val(row.find("td:eq(11)").text());
    
    $('#editModalLinea').modal('show');
});

$(document).on('click', '.crear-btn-linea', function() {

    let id_E = '{{ $inventario->EmpleadoID }}';

    $('#editFormLinea')[0].reset();
    
    document.getElementById('titulolinea').innerHTML = 'Crear Linea';
    var id = $(this).data('id');
    $('#editId_linea').val(''); 
    $('#editId_linea2').val(id);
    $('#editEmp_linea').val(id_E);

    $('#editModalLinea').modal('show');
});


$(document).on('click', '.submit_linea', function(event) {
    event.preventDefault();

    $('.error-message').remove();
    $('.is-invalid').removeClass('is-invalid');

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

    let id = $('#editId_linea').val();
    let id2 = $('#editId_linea2').val();
    let id_E = $('#editEmp_linea').val();
    let url = id ? '/inventarios/editar-linea/' + id  : '/inventarios/crear-linea/' + id_E +'/' + id2;
    let method = id ? 'PUT' : 'POST';

    let formData = {
        FechaAsignacion: $('#editfechalinea').val(),
        Comentarios: $('#editcomenl').val()
    };

    let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    $.ajax({
        url: url,
        method: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function(response) {
            if (response.errors) {
                
                Object.keys(response.errors).forEach(field => {
                    const input = $(`#edit${field}`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(response.errors[field][0]);
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
                    title: "Datos del telefonia guardado correctamente",
                    showConfirmButton: false,
                    timer: 1500
                });

           
                if (id) {
                    updatetelefTableRow(response.telefono);
                } else {
                    addtelefNewRow(response.telefono);
                }

                $('#editModalLinea').modal('hide');
            }
        },
        error: function(error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al guardar los datos',
            });
        }
    });
});


function updatetelefTableRow(telefono) {
    let row = $(`tr[data-id=${telefono.InventarioID}]`);
    row.find('td:eq(13)').text(telefono.Gerenciainsumo);
    row.find('td:eq(11)').text(telefono.Comentarios);
}


function addtelefNewRow(telefono) {
    let newRow = `
        <tr data-id="${telefono.InventarioID}">
            <td>
                <button class="btn btn-outline-secondary btn-xs edit-btn-linea" data-id="${telefono.InventarioID}">
                    <i class="fa fa-edit"></i>
                </button>
                <form method="POST" action="/inventarios/deleteL/${telefono.InventarioID}" style="display:inline">
                    <button type="submit" class="btn btn-xs btn-outline-danger btn-flat delete-btn-linea" data-id="${telefono.InventarioID}">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
            </td>
            <td>${telefono.NumTelefonico}</td>
            <td>${telefono.Compania}</td>
            <td>${telefono.PlanTel}</td>
            <td>${telefono.CostoRentaMensual}</td>
            <td>${telefono.CuentaPadre}</td>
            <td>${telefono.CuentaHija}</td>
            <td>${telefono.TipoLinea}</td>
            <td>${telefono.Obra}</td>
            <td>${telefono.FechaAsignacion}</td>
            <td>${telefono.CostoFianza}</td>
            <td>${telefono.FechaAsignacion}</td>
             <td>${telefono.Estado}</td>
            <td>${telefono.Comentarios}</td>
              <td>${telefono.MontoRenovacionFianza}</td>
              <td>${telefono.LineaID}</td>
        </tr>
    `;
    $('#lineasAsignadosTable tbody').append(newRow);
}

$(document).on('click', '.delete-btn-linea', function(event) {
    event.preventDefault();

    var id = $(this).data('id');

    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el ID del telefono.',
        });
        return;
    }

    Swal.fire({
        title: `Eliminar`,
        text: "¿Realmente desea eliminar este telefono asignado?",
        icon: "warning",
        showDenyButton: true,
        confirmButtonText: 'Confirmar',
        denyButtonText: 'Cerrar',
        dangerMode: true,
    }).then(function(willDelete) {
        if (willDelete.isConfirmed) {
            $.ajax({
                url: `/inventarios/deleteL/${id}`, 
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Eliminado!',
                            text: "El telefono fue eliminado correctamente.",
                            icon: 'success'
                        });

                        // Eliminar la fila de la tabla
                        $(`tr[data-id=${id}]`).remove();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo eliminar el telefono',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al eliminar el telefono.',
                    });
                }
            });
        }
    });
});

// Fin Seccion telefono





    </script>

 

@endpush
