<!-- Nombreobra Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombreObra', 'Nombre obra:') !!}
    {!! Form::text('NombreObra', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Direccion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Direccion', 'Direccion:') !!}
    {!! Form::text('Direccion', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Encargadodeobra Field -->
<div class="form-group col-sm-6">
    {!! Form::label('EncargadoDeObra', 'Encargado de obra:') !!}
    {!! Form::text('EncargadoDeObra', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Unidadnegocioid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('UnidadNegocioID', 'Unidad negocio:') !!}
 
    {!!Form::select('UnidadNegocioID',App\Models\UnidadesDeNegocio::all()->
        pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>