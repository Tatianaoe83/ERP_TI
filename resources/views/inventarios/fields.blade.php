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

@php
//dd ($inventario);

@endphp
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
            <!-- Productos Disponibles -->
            <h6>Equipos Disponibles</h6>
            <div class="drag-area" id="disponibles">
                <table class="table table-bordered table-md">
                    <tr>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                        
                    </tr>
                    
                </table>
            </div>

            <!-- Productos Seleccionados -->
            <h6>Equipos Asignados</h6>
            <div class="drag-area" id="seleccionados">
                <table class="table table-bordered table-md">
                    <tr>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                       
                    </tr>
                   
                </table>
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


