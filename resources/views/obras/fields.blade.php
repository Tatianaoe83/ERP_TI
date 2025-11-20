<!-- Nombreobra Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreObra', 'Nombre obra:') !!}
    {!! Form::text('NombreObra', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Direccion Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Direccion', 'Direccion:') !!}
    {!! Form::text('Direccion', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Encargadodeobra Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('EncargadoDeObra', 'Encargado de obra:') !!}
    {!! Form::text('EncargadoDeObra', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Unidadnegocioid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('UnidadNegocioID', 'Unidad negocio:') !!}

    {!!Form::select('UnidadNegocioID',App\Models\UnidadesDeNegocio::all()->
    pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control','style' => 'width: 100%'])!!}

</div>


<!-- Estado Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('estado', 'Es obra:') !!}
    {!! Form::select('estado', [1 => 'Si', 0 => 'No'], null, ['class' => 'form-control','style' => 'width: 100%']) !!}
</div>