@php
    $empleadoActivo = $empleadoActivo ?? ($inventario->Estado == 1 || $inventario->Estado === true);
@endphp

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active dark:text-white" data-toggle="tab" href="#empleados">Empleado</a>
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

@if(!$empleadoActivo)
<div class="alert alert-warning mt-3 mb-0">
    Este empleado está <strong>inactivo</strong>. Solo puede consultar su inventario; no es posible asignar, editar ni eliminar elementos.
</div>
@endif

<div class="tab-content mt-3">




    <!-- TAB Empleado -->
    <div class="tab-pane fade show active" id="empleados">
        <div class="row">
            <!-- NombreEmpleado Field -->
            <div class="col-sm-6">
                {!! Form::label('NombreEmpleado', 'Nombre del Empleado:', ['class' => 'dark:text-white']) !!}
                {!! Form::text('NombreEmpleado', old('NombreEmpleado', $inventario->NombreEmpleado ?? ''), ['class' => 'form-control', 'maxlength' => 100, 'disabled']) !!}
            </div>

            <!-- UnidadNegocio Field -->
            <div class="col-sm-6">
                {!! Form::label('UnidadNegocioID', 'Unidad de Negocio:',['class' => 'dark:text-white']) !!}

                {!!Form::select('UnidadNegocioID',App\Models\UnidadesDeNegocio::all()->
                pluck('NombreEmpresa','UnidadNegocioID'),$inventario->UnidadNegocioID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

            <!-- UnidadNegocio Field -->
            <div class="col-sm-6">
                {!! Form::label('GerenciaID', 'Gerencia:', ['class' => 'dark:text-white']) !!}

                {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
                pluck('NombreGerencia','GerenciaID'),$inventario->GerenciaID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

            <!-- ObraID Field -->
            <div class="col-sm-6">
                {!! Form::label('ObraID', 'Obra:', ['class' => 'dark:text-white']) !!}

                {!!Form::select('ObraID',App\Models\Obras::all()->
                pluck('NombreObra','ObraID'),$inventario->ObraID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}


            </div>

            <!-- ObraID Field -->
            <div class="col-sm-6">
                {!! Form::label('DepartamentoID', 'Departamento:', ['class' => 'dark:text-white']) !!}

                {!!Form::select('DepartamentoID',App\Models\Departamentos::all()->
                pluck('NombreDepartamento','DepartamentoID'),$inventario->DepartamentoID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>


            <!-- PuestoID Field -->
            <div class="col-sm-6">
                {!! Form::label('PuestoID', 'Puesto:', ['class' => 'dark:text-white']) !!}
                {!!Form::select('PuestoID',App\Models\Puestos::all()->
                pluck('NombrePuesto','PuestoID'),$inventario->PuestoID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>


            <!-- NumTelefono Field -->
            <div class="col-sm-6">
                {!! Form::label('NumTelefono', 'Número de Teléfono:', ['class' => 'dark:text-white']) !!}
                {!! Form::text('NumTelefono', old('NumTelefono', $inventario->NumTelefono ?? ''), ['class' => 'form-control', 'maxlength' => 50, 'disabled']) !!}
            </div>

            <!-- Correo Field -->
            <div class="col-sm-6">
                {!! Form::label('Correo', 'Correo Electrónico:', ['class' => 'dark:text-white']) !!}
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
                            @if($permitePresupuestado)
                            <th>Presupuestado</th>
                            <th>Mes de pago</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equiposAsignados as $equiposAsignado)
                        <tr data-id="{{ $equiposAsignado->InventarioID }}">
                            <td>
                                @if($empleadoActivo)
                                <button class='btn btn-outline-secondary btn-xs edit-btn mt-2' data-id="{{ $equiposAsignado->InventarioID }}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroy', $equiposAsignado->InventarioID], 'style' => 'display:inline']) !!}
                                {!! Form::button('<i class="fa fa-trash"></i>', [
                                'type' => 'submit',
                                'class' => 'btn btn-xs btn-outline-danger btn-flat delete-btn mt-2',
                                'data-id' => $equiposAsignado->InventarioID
                                ]) !!}
                                {!! Form::close() !!}
                                @else
                                <span class="text-muted small">—</span>
                                @endif

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
                            @if($permitePresupuestado)
                            <td>{{ $equiposAsignado->Presupuestado ? 'Si' : 'No' }}</td>
                            <td>{{ $equiposAsignado->MesDePago }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- equiposAsignados Disponibles -->
            @if($empleadoActivo)
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
                                <td>{{ $equipo->categorias->Categoria }}</td>
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

            @endif



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
                            <th>Fecha de Renovacion</th>
                            <th>Observaciones</th>
                            <th>Fecha de Asignacion</th>
                            <th>Num. Serie</th>
                            <th>Comentarios</th>
                            <th>Mes de pago </th>
                            @if($permitePresupuestado)
                            <th>Presupuestado</th>
                            @endif

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumosAsignados as $insumosAsignado)
                        <tr data-id="{{ $insumosAsignado->InventarioID }}">
                            <td>
                                @if($empleadoActivo)
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
                                @else
                                <span class="text-muted small">—</span>
                                @endif


                            </td>


                            <td>{{ $insumosAsignado->CateogoriaInsumo }}</td>
                            <td>{{ $insumosAsignado->NombreInsumo }}</td>
                            <td>{{ $insumosAsignado->CostoMensual }}</td>
                            <td>{{ $insumosAsignado->CostoAnual }}</td>
                            <td>{{ $insumosAsignado->FrecuenciaDePago }}</td>
                            <td>{{ (empty($insumosAsignado->FechaRenovacion) || in_array($insumosAsignado->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($insumosAsignado->FechaRenovacion)->format('d/m/Y') }}</td>
                            <td>{{ $insumosAsignado->Observaciones }}</td>
<td>{{ $insumosAsignado->FechaAsignacion ? \Carbon\Carbon::parse($insumosAsignado->FechaAsignacion)->format('d/m/Y') : 'Sin asignar' }}</td>                            <td>{{ $insumosAsignado->NumSerie }}</td>
                            <td>{{ $insumosAsignado->Comentarios }}</td>
                            <td>{{ $insumosAsignado->MesDePago }}</td>
                            @if($permitePresupuestado)
                            <td>{{ $insumosAsignado->Presupuestado ? 'Si' : 'No' }}</td>
                            @endif
                        </tr>
                        @endforeach


                    </tbody>
                </table>
            </div>

            <!-- insumos Disponibles -->
            @if($empleadoActivo)
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
                                <th>Fecha de Renovacion</th>
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

                                <td>{{ $insumo->categorias->Categoria }}</td>
                                <td>{{ $insumo->NombreInsumo }}</td>
                                <td>{{ $insumo->CostoMensual }}</td>
                                <td>{{ $insumo->CostoAnual }}</td>
                                <td>{{ $insumo->FrecuenciaDePago }}</td>
                                <td>{{ (empty($insumo->FechaRenovacion) || in_array($insumo->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($insumo->FechaRenovacion)->format('d/m/Y') }}</td>
                                <td>{{ $insumo->Observaciones }}</td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @endif


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
                            <th>Num. Tel.</th>
                            <th>Compania</th>
                            <th>Plan</th>
                            <th>Costo Renta Mensual</th>
                            <th>Cuenta Padre</th>
                            <th>Cuenta Hija</th>
                            <th>Tipo Linea</th>
                            <th>Obra</th>
                            <th>Fecha Fianza</th>
                            <th>Costo Fianza</th>
                            <th>Fecha Asignación</th>
                            <th>Comentario</th>
                            <th>Monto Renovación Fianza</th>
                            <th>Fecha Renovación</th>
                            @if($permitePresupuestado)
                            <th>Presupuestado</th>
                            @endif



                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($LineasAsignados as $LineasAsignado)
                        <tr data-id="{{ $LineasAsignado->InventarioID }}">
                            <td>
                                @if($empleadoActivo)
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
                                @else
                                <span class="text-muted small">—</span>
                                @endif


                            </td>


                            <td>{{ $LineasAsignado->NumTelefonico}}</td>
                            <td>{{ $LineasAsignado->Compania}}</td>
                            <td>{{ $LineasAsignado->PlanTel}}</td>
                            <td>{{ $LineasAsignado->CostoRentaMensual}}</td>
                            <td>{{ $LineasAsignado->CuentaPadre}}</td>
                            <td>{{ $LineasAsignado->CuentaHija}}</td>
                            <td>{{ $LineasAsignado->TipoLinea}}</td>  
                            <td>{{ $LineasAsignado->lineastelefonicas->obras->NombreObra ?? 'Sin asignar'}}</td>
                            <td>{{ $LineasAsignado->FechaFianza ? \Carbon\Carbon::parse($LineasAsignado->FechaFianza)->format('d/m/Y') : '' }}</td>
                            <td>{{ $LineasAsignado->CostoFianza}}</td>
                            <td>{{ $LineasAsignado->FechaAsignacion ? \Carbon\Carbon::parse($LineasAsignado->FechaAsignacion)->format('d/m/Y') : '' }}</td>
                            <td>{{ $LineasAsignado->Comentarios}}</td>
                            <td>{{ $LineasAsignado->MontoRenovacionFianza}}</td>
                            <td>{{ (empty($LineasAsignado->FechaRenovacion) || in_array($LineasAsignado->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($LineasAsignado->FechaRenovacion)->format('d/m/Y') }}</td>
                            @if($permitePresupuestado)
                            <td>{{ $LineasAsignado->Presupuestado ? 'Si' : 'No' }}</td>
                            @endif

                        </tr>
                        @endforeach


                    </tbody>
                </table>
            </div>

            <!-- lineas Disponibles -->
            @if($empleadoActivo)
            <span class="badge badge-primary" style="margin-bottom: 15px;margin-top: 15px;">Lineas Disponibles</span>
            <div class="drag-area" id="disponibles">
                <div class="table-responsive">
                    <table id="lineasTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Asignar</th>
                                <th>Num. Tel.</th>
                                <th>Plan</th>
                                <th>Cuenta Padre</th>
                                <th>Cuenta Hija</th>
                                <th>Tipo Linea</th>
                                <th>Obra</th>
                                <th>Fecha Fianza</th>
                                <th>Costo Fianza</th>
                                <th>Activo</th>
                                <th>Monto Renovación Fianza</th>
                                <th>Fecha Renovación</th>



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
                                <td>{{ $Linea->planes->NombrePlan}}</td>
                                <td>{{ $Linea->CuentaPadre}}</td>
                                <td>{{ $Linea->CuentaHija}}</td>
                                <td>{{ $Linea->TipoLinea}}</td>
                                <td>{{ $Linea->obras->NombreObra}}</td>
                                <td>{{(empty($Linea->FechaFianza) ||in_array($Linea->FechaFianza, ['Sin asignar', 'Sin asigna', '0000-00-00']))? 'Sin asignar': \Carbon\Carbon::parse($Linea->FechaFianza)->format('d/m/Y')}}</td>
                                <td>{{ $Linea->CostoFianza}}</td>
                                <td>
                                    <input class="form-check-input" type="checkbox" value="" id="flexCheckCheckedDisabled1" checked disabled>
                                    <label class="form-check-label" for="flexCheckCheckedDisabled1">
                                    </label>
                                </td>
                                <td>{{ $Linea->MontoRenovacionFianza}}</td>
                                <td>{{ (empty($Linea->FechaRenovacion) || in_array($Linea->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($Linea->FechaRenovacion)->format('d/m/Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @endif


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
    const empleadoInventarioActivo = @json($empleadoActivo);
    const permitePresupuestado = @json($permitePresupuestado);

    // El switch sólo existe en el DOM para FISICA/EXTRAORDINARIO; para el resto
    // estas funciones son inocuas y el campo viaja siempre en 0.
    function setPresupuestado(selector, texto) {
        const marcado = String(texto ?? '').trim().toLowerCase() === 'si';
        $(selector).prop('checked', marcado);
        $(selector + 'Label').text(marcado ? 'Si' : 'No');
    }

    function getPresupuestado(selector) {
        return permitePresupuestado && $(selector).is(':checked') ? 1 : 0;
    }

    // Mantener la etiqueta del switch en sync con su estado
    $(document).on('change', '.form-check-input[role="switch"]', function() {
        $('#' + this.id + 'Label').text(this.checked ? 'Si' : 'No');
    });

    function bloquearAccionInventarioInactivo() {
        if (!empleadoInventarioActivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Empleado inactivo',
                text: 'No se pueden realizar acciones de inventario porque el empleado está dado de baja.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return true;
        }

        return false;
    }
</script>

<script>
    $(document).ready(function() {
        if ($('#equiposTable').length) {
        let table1_1 = $('#equiposTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,
        });
        }

        if ($('#insumosTable').length) {
        let table2_1 = $('#insumosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,
        });
        }

        if ($('#lineasTable').length) {
        let table3_1 = $('#lineasTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,

        });
        }

        let table = $('#equiposAsignadosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,

        });

        let table2 = $('#insumosAsignadosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,

        });

        let table3 = $('#lineasAsignadosTable').DataTable({
            "responsive": true,
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
        $('#myTab a').on('click', function(event) {
            event.preventDefault();
            var target = $(this).attr('href');

            $('#myTab a').removeClass('active');
            $('.tab-pane').removeClass('show active');

            $(this).addClass('active');
            $(target).addClass('show active');
        });
    });

    // Seccion equipo 
    // Editar equipo (abriendo el modal con los datos)
    $(document).on('click', '.edit-btn', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

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
        setPresupuestado('#editPresupuestadoEquipo', row.find("td:eq(12)").text());
        $('#editMesDePagoEquipo').val(row.find("td:eq(13)").text().trim());

        $('#editModal').modal('show');
    });

    // Crear equipo (con valores vacíos para nuevo registro)
    $(document).on('click', '.crear-btn', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

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
        setPresupuestado('#editPresupuestadoEquipo', 'No');
        $('#editMesDePagoEquipo').val('');

        $('#editModal').modal('show');
    });

    // Validación en tiempo real del Folio (al escribir o al salir del campo)
    let folioTimer = null;
    let folioValido = true; // Estado de validez del folio actual

    // Función para cargar los últimos 3 folios
    function cargarUltimosFolios() {
        const excluirId = $('#editId').val();
        $.ajax({
            url: '/inventarios/verificar-folio',
            method: 'GET',
            data: { folio: '', excluir_id: excluirId },
            success: function(response) {
                if (response.ultimos_folios && response.ultimos_folios.length > 0) {
                    $('#ultimos-folios-lista').html(
                        '<ul class="mb-0 pl-3"><li>' +
                        response.ultimos_folios.join('</li><li>') +
                        '</li></ul>'
                    );  
                } else {
                    $('#ultimos-folios-lista').text('Ninguno registrado aún');
                }
            }
        });
    }

    // Mostrar últimos 3 folios registrados cuando el usuario hace focus al input
    $(document).on('focus', '#editFolio', function() {
        cargarUltimosFolios();
        $('#folio-Info').fadeIn(200);
    });

    // Ocultar la advertencia al perder el foco
    $(document).on('blur', '#editFolio', function() {
        $('#folio-Info').fadeOut(200);
    });

    $(document).on('input', '#editFolio', function() {
        clearTimeout(folioTimer);
        const folioInput = $(this);
        const folio = folioInput.val().trim();
        const excluirId = $('#editId').val();
        const feedbackEl = folioInput.siblings('.invalid-feedback');

        // Limpiar estado previo
        folioInput.removeClass('is-invalid is-valid');
        folioValido = true;

        if (!folio) return;

        // Esperar 500ms después de que el usuario deje de escribir
        folioTimer = setTimeout(function() {
            $.ajax({
                url: '/inventarios/verificar-folio',
                method: 'GET',
                data: { folio: folio, excluir_id: excluirId },
                success: function(response) {
                    if (response.disponible) {
                        folioInput.removeClass('is-invalid').addClass('is-valid');
                        feedbackEl.text('');
                        folioValido = true;
                    } else {
                        folioInput.removeClass('is-valid').addClass('is-invalid');
                        if (feedbackEl.length) {
                            feedbackEl.text(response.mensaje);
                        } else {
                            folioInput.after('<div class="invalid-feedback" style="display:block">' + response.mensaje + '</div>');
                        }
                        folioValido = false;
                    }
                    
                    // Actualizar también la lista si cambia
                    if (response.ultimos_folios && response.ultimos_folios.length > 0) {
                        $('#ultimos-folios-lista').html(
                            '<ul class="mb-0 pl-3"><li>' +
                            response.ultimos_folios.join('</li><li>') +
                            '</li></ul>'
                        );
                    }
                }
            });
        }, 500);
    });

    // Limpiar estado de validación del folio al abrir el modal
    $('#editModal').on('show.bs.modal', function() {
        folioValido = true;
        $('#editFolio').removeClass('is-invalid is-valid');
        $('#folio-Info').hide();
    });

    // Enviar formulario de edición o creación con AJAX
    $(document).on('click', '.submit_equipo', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

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
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        // Bloquear el envío si el folio ya fue detectado como duplicado
        if (!folioValido) {
            Swal.fire({
                icon: 'error',
                title: 'Folio duplicado',
                text: 'El folio ingresado ya está registrado. Por favor ingrese un folio único.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            $('#editFolio').addClass('is-invalid').focus();
            return;
        }

        let id = $('#editId').val();
        let id_E = $('#editEmp').val();
        let folio = $('#editFolio').val().trim();
        let excluirId = id || null;
        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Verificación final de unicidad del folio antes de enviar
        $.ajax({
            url: '/inventarios/verificar-folio',
            method: 'GET',
            data: { folio: folio, excluir_id: excluirId },
            success: function(verifyResponse) {
                if (!verifyResponse.disponible) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Folio duplicado',
                        text: verifyResponse.mensaje,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                    $('#editFolio').addClass('is-invalid').focus();
                    folioValido = false;
                    return;
                }

                // Folio único: proceder con el guardado
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
                    Folio: folio,
                    FechaDeCompra: $('#editFechaDeCompra').val(),
                    Comentarios: $('#editComentarios').val(),
                    FechaRenovacion: $('#editFechaDeRenovacion').val(),
                    Presupuestado: getPresupuestado('#editPresupuestadoEquipo'),
                    MesDePago: $('#editMesDePagoEquipo').val(),
                };

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
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        } else {
                            // Si la solicitud fue exitosa, actualizar la fila correspondiente o agregar una nueva
                            Swal.fire({
                                position: "top-end",
                                icon: "success",
                                title: "Datos del equipo guardados correctamente",
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
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
                    error: function(xhr) {
                        // Manejar error 422 del backend (folio duplicado)
                        if (xhr.status === 422) {
                            let resp = xhr.responseJSON;
                            if (resp && resp.errors && resp.errors.Folio) {
                                $('#editFolio').addClass('is-invalid').focus();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Folio duplicado',
                                    text: resp.errors.Folio[0],
                                    customClass: {
                                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                    }
                                });
                            }
                        } else {
                            console.error('Error:', xhr);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Ocurrió un error al guardar los datos',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    }
                });
            },
            error: function() {
                // Si falla la verificación, dejar pasar y que el backend valide
                console.warn('No se pudo verificar el folio en tiempo real.');
            }
        });
    });


    // Helper para formatear fechas a dd/mm/yyyy o 'Sin asignar'
    function formatFechaRenovacion(fecha) {
        if (!fecha || fecha === 'Sin asignar' || fecha === 'Sin asigna' || fecha === '0000-00-00' || fecha === 'null') {
            return 'Sin asignar';
        }
        let raw = fecha.toString().substring(0, 10);
        let parts = raw.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }
        return fecha;
    }

    // Helper para convertir dd/mm/yyyy a yyyy-mm-dd (para inputs type=date)
    function fechaDisplayToInput(fechaDisplay) {
        if (!fechaDisplay || fechaDisplay === 'Sin asignar' || fechaDisplay === 'Sin asigna' || fechaDisplay === '0000-00-00') {
            return '';
        }
        let parts = fechaDisplay.trim().split('/');
        if (parts.length === 3 && parts[2].length === 4) {
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        // Si ya está en yyyy-mm-dd, retornar como está
        return fechaDisplay.trim().substring(0, 10);
    }

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
        if (permitePresupuestado) {
            row.find('td:eq(12)').text(equipo.Presupuestado ? 'Si' : 'No');
            row.find('td:eq(13)').text(equipo.MesDePago ?? '');
        }
        row.find('.edit-btn').data('id', equipo.InventarioID);
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
            ${permitePresupuestado ? `<td>${equipo.Presupuestado ? 'Si' : 'No'}</td><td>${equipo.MesDePago ?? ''}</td>` : ''}
        </tr>
    `;
        $('#equiposAsignadosTable tbody').append(newRow);
    }

    // Eliminar equipo con AJAX
    $(document).on('click', '.delete-btn', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var id = $(this).data('id'); // ✅ Obtener el ID del botón delete-btn

        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del equipo.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
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
            customClass: {
                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
            }
        }).then(function(willDelete) {
            if (willDelete.isConfirmed) {
                $.ajax({
                    url: `/inventarios/${id}`, // ✅ Se pasa el ID en la URL correctamente
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Eliminado!',
                                text: "El equipo fue eliminado correctamente.",
                                icon: 'success',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });

                            // Eliminar la fila de la tabla
                            $(`tr[data-id=${id}]`).remove();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el equipo',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al eliminar el equipo.',
                            customClass: {
                                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                            }
                        });
                    }
                });
            }
        });
    });

    // Fin seccion equipo 

    // Seccion insumo

    $(document).on('click', '.edit-btn-insum', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

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
        // Convertir dd/mm/yyyy del <td> a yyyy-mm-dd para el input date
        $('#editFechaDeRenovacion').val(fechaDisplayToInput(row.find("td:eq(6)").text()));
        $('#editobserv').val(row.find("td:eq(7)").text());
        $('#editFechaDeAsigna').val(fechaDisplayToInput(row.find("td:eq(8)").text()));
        $('#editNumSerieInsu').val(row.find("td:eq(9)").text());
        $('#editComentariosInsumo').val(row.find("td:eq(10)").text());
        $('#editMesDePago').val(row.find("td:eq(11)").text());
        setPresupuestado('#editPresupuestadoInsumo', row.find("td:eq(12)").text());

        $('#editModalInsumo').modal('show');
    });

    $(document).on('click', '.crear-btn-insumo', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editFormInsumo')[0].reset();

        document.getElementById('tituloinsumo').innerHTML = 'Crear insumo';
        let row = $(this).closest('tr');
        let categoria = row.find("td:eq(1)").text();
        let nombreinsumo = row.find("td:eq(2)").text();
        let costomensual = row.find("td:eq(3)").text();
        let costoanual = row.find("td:eq(4)").text();
        let frecuenciadepago = row.find("td:eq(5)").text();
        let fecharenovacion = row.find("td:eq(6)").text().trim();
        // Si la fecha trae hora (ej. 2026-04-29 00:00:00), tomamos solo los primeros 10 caracteres
        if (fecharenovacion.length > 10) {
            fecharenovacion = fecharenovacion.substring(0, 10);
        }
        // Si la fecha es un texto como 'Sin asignar', enviar vacío en vez del string
        if (fecharenovacion === 'Sin asignar' || fecharenovacion === 'Sin asigna' || fecharenovacion === '0000-00-00') {
            fecharenovacion = '';
        }
        let observaciones = row.find("td:eq(7)").text();

        $('#editCategoriaInsumo').val(categoria);
        $('#editNombreInsumo').val(nombreinsumo);
        $('#editCostoMensual').val(costomensual);
        $('#editCostoAnual').val(costoanual);
        $('#editFrecuenciaDePago').val(frecuenciadepago);
        $('#editFechaDeRenovacion').val(fecharenovacion);
        $('#editobserv').val(observaciones);
        $('#editId_insumo').val('');
        $('#editEmp_insumo').val(id_E);
        setPresupuestado('#editPresupuestadoInsumo', 'No');

        $('#editModalInsumo').modal('show');
    });


    $(document).on('click', '.submit_insumo', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

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
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        let id = $('#editId_insumo').val();
        let id_E = $('#editEmp_insumo').val();
        let url = id ? '/inventarios/editar-insumo/' + id : '/inventarios/crear-insumo/' + id_E;
        let method = id ? 'PUT' : 'POST';

        // Limpiar FechaRenovacion: enviar vacío si tiene texto no-fecha
        let fechaRenovInsumo = $('#editFechaDeRenovacion').val();
        if (fechaRenovInsumo === 'Sin asignar' || fechaRenovInsumo === 'Sin asigna' || fechaRenovInsumo === '0000-00-00') {
            fechaRenovInsumo = '';
        }

        let formData = {
            CateogoriaInsumo: $('#editCategoriaInsumo').val(),
            NombreInsumo: $('#editNombreInsumo').val(),
            CostoMensual: $('#editCostoMensual').val(),
            CostoAnual: $('#editCostoAnual').val(),
            FrecuenciaDePago: $('#editFrecuenciaDePago').val(),
            FechaRenovacion: fechaRenovInsumo,
            Observaciones: $('#editobserv').val(),
            FechaAsignacion: $('#editFechaDeAsigna').val(),
            NumSerie: $('#editNumSerieInsu').val(),
            Comentarios: $('#editComentariosInsumo').val(),
            MesDePago: $('#editMesDePago').val(),
            Presupuestado: getPresupuestado('#editPresupuestadoInsumo'),
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
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                } else {

                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Datos del insumo guardado correctamente",
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
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
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                });
            }
        });
    });


    function updateisnumoTableRow(insumo) {
        let row = $(`tr[data-id=${insumo.InventarioID}]`);
        row.find('td:eq(1)').text(insumo.CateogoriaInsumo);
        row.find('td:eq(2)').text(insumo.NombreInsumo);
        row.find('td:eq(3)').text(insumo.CostoMensual);
        row.find('td:eq(4)').text(insumo.CostoAnual);
        row.find('td:eq(5)').text(insumo.FrecuenciaDePago);
        row.find('td:eq(6)').text(formatFechaRenovacion(insumo.FechaRenovacion));
        row.find('td:eq(7)').text(insumo.Observaciones);
        row.find('td:eq(8)').text(formatFechaRenovacion(insumo.FechaAsignacion));
        row.find('td:eq(9)').text(insumo.NumSerie);
        row.find('td:eq(10)').text(insumo.Comentarios);
        row.find('td:eq(11)').text(insumo.MesDePago);
        if (permitePresupuestado) {
            row.find('td:eq(12)').text(insumo.Presupuestado ? 'Si' : 'No');
        }
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
            <td>${formatFechaRenovacion(insumo.FechaRenovacion)}</td>
            <td>${insumo.Observaciones}</td>
            <td>${formatFechaRenovacion(insumo.FechaAsignacion)}</td>
            <td>${insumo.NumSerie}</td>
            <td>${insumo.Comentarios}</td>
            <td>${insumo.MesDePago}</td>
            ${permitePresupuestado ? `<td>${insumo.Presupuestado ? 'Si' : 'No'}</td>` : ''}
        </tr>
    `;
        $('#insumosAsignadosTable tbody').append(newRow);
    }

    $(document).on('click', '.delete-btn-insumo', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var id = $(this).data('id');

        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del insumo.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
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
            customClass: {
                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
            }
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
                                icon: 'success',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });

                            // Eliminar la fila de la tabla
                            $(`tr[data-id=${id}]`).remove();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el insumo',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al eliminar el insumo.',
                            customClass: {
                                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                            }
                        });
                    }
                });
            }
        });
    });

    // Fin Seccion insumo

    // Seccion telefono

    $(document).on('click', '.edit-btn-linea', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = $(this).closest('tr');
        let id = row.data('id');

        document.getElementById('titulolinea').innerHTML = 'Editar Linea';

        // Asignar valores al formulario
        $('#editId_linea').val(id);
        $('#editId_linea2').val('');
        $('#editEmp_linea').val('');
        $('#editcomenl').val(row.find("td:eq(12)").text());
        $('#editfechalinea').val(fechaDisplayToInput(row.find("td:eq(11)").text()));
        $('#editMontoRenovacionFianza').val(row.find("td:eq(13)").text());
        // Convertir dd/mm/yyyy del <td> a yyyy-mm-dd para el hidden input
        $('#editFechaRenovacion').val(fechaDisplayToInput(row.find("td:eq(14)").text()));
        setPresupuestado('#editPresupuestadoLinea', row.find("td:eq(15)").text());

        $('#editModalLinea').modal('show');
    });
    $(document).on('click', '.crear-btn-linea', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editFormLinea')[0].reset();

        document.getElementById('titulolinea').innerHTML = 'Asignar Linea';
        let row = $(this).closest('tr');
        
        let boton = $(this);
        let id = boton.data('id');

        let monto = row.find("td:eq(10)").text();
        let fecha = row.find("td:eq(11)").text().trim();

        // Limpiar fecha si trae hora
        if (fecha.length > 10) {
            fecha = fecha.substring(0, 10);
        }

        // Si la fecha es un texto como 'Sin asignar', enviar vacío en vez del string
        if (fecha === 'Sin asignar' || fecha === 'Sin asigna' || fecha === '0000-00-00') {
            fecha = '';
        }

        $('#editId_linea').val('');
        $('#editId_linea2').val(id);
        $('#editEmp_linea').val(id_E);
        $('#editMontoRenovacionFianza').val(monto);
        $('#editFechaRenovacion').val(fecha);
        setPresupuestado('#editPresupuestadoLinea', 'No');

        $('#editModalLinea').modal('show');
    });


    $(document).on('click', '.submit_linea', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

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
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        let id = $('#editId_linea').val();
        let id2 = $('#editId_linea2').val();
        let id_E = $('#editEmp_linea').val();
        let url = id ? '/inventarios/editar-linea/' + id : '/inventarios/crear-linea/' + id_E + '/' + id2;
        let method = id ? 'PUT' : 'POST';

        // Limpiar FechaRenovacion: enviar vacío si tiene texto no-fecha
        let fechaRenov = $('#editFechaRenovacion').val();
        if (fechaRenov === 'Sin asignar' || fechaRenov === 'Sin asigna' || fechaRenov === '0000-00-00') {
            fechaRenov = '';
        }

        let formData = {
            FechaAsignacion: $('#editfechalinea').val(),
            Comentarios: $('#editcomenl').val(),
            MontoRenovacionFianza: $('#editMontoRenovacionFianza').val(),
            FechaRenovacion: fechaRenov,
            Presupuestado: getPresupuestado('#editPresupuestadoLinea')
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
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                } else {

                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Datos del telefonia guardado correctamente",
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
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
                let errorMessage = 'Ocurrió un error al guardar los datos';
                if (error.responseJSON && error.responseJSON.message) {
                    errorMessage = error.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                });
            }
        });
    });


    function updatetelefTableRow(telefono) {
        let row = $(`tr[data-id=${telefono.InventarioID}]`);
        row.find('td:eq(11)').text(formatFechaRenovacion(telefono.FechaAsignacion));
        row.find('td:eq(12)').text(telefono.Comentarios);
        row.find('td:eq(13)').text(telefono.MontoRenovacionFianza);
        row.find('td:eq(14)').text(formatFechaRenovacion(telefono.FechaRenovacion));
        if (permitePresupuestado) {
            row.find('td:eq(15)').text(telefono.Presupuestado ? 'Si' : 'No');
        }
    }


    function addtelefNewRow(telefono) {
        const table = $('#lineasAsignadosTable').DataTable();

        const newRow = [
            `<button class="btn btn-outline-secondary btn-xs edit-btn-linea" data-id="${telefono.InventarioID}">
            <i class="fa fa-edit"></i>
        </button>
        <form method="POST" action="/inventarios/deleteL/${telefono.InventarioID}" style="display:inline">
            <button type="submit" class="btn btn-xs btn-outline-danger btn-flat delete-btn-linea" data-id="${telefono.InventarioID}">
                <i class="fa fa-trash"></i>
            </button>
        </form>`,
            telefono.NumTelefonico,
            telefono.Compania,
            telefono.PlanTel,
            telefono.CostoRentaMensual,
            telefono.CuentaPadre,
            telefono.CuentaHija,
            telefono.TipoLinea,
            telefono.Obra,
            formatFechaRenovacion(telefono.FechaFianza),
            telefono.CostoFianza,
            formatFechaRenovacion(telefono.FechaAsignacion),
            telefono.Comentarios,
            telefono.MontoRenovacionFianza,
            formatFechaRenovacion(telefono.FechaRenovacion)
        ];

        // La columna sólo existe para FISICA/EXTRAORDINARIO; DataTables exige que el
        // array tenga exactamente tantos elementos como columnas tenga la tabla.
        if (permitePresupuestado) {
            newRow.push(telefono.Presupuestado ? 'Si' : 'No');
        }

        table.row.add(newRow).draw(false);
    }

    $(document).on('click', '.delete-btn-linea', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var id = $(this).data('id');

        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del telefono.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
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
            didOpen: () => {
                $('#editEmpleado').select2({
                    dropdownParent: $('.swal2-popup'),
                    width: '100%',
                    theme: 'classic'
                });

                $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                $('.swal2-title').addClass('dark:text-white');
            }
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
                                icon: 'success',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });

                            const table = $('#lineasAsignadosTable').DataTable();
                            table.row($(`.delete-btn-linea[data-id="${id}"]`).closest('tr')).remove().draw(false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el telefono',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al eliminar el telefono.',
                            customClass: {
                                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                            }
                        });
                    }
                });
            }
        });
    });

    // Fin Seccion telefono
</script>



@endpush