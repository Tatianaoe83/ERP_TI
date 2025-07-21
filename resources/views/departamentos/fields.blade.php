<!-- Nombredepartamento Field -->
<div class="col-sm-6 row text-[#101D49] dark:text-white">
    {!! Form::label('NombreDepartamento', 'Nombre departamento:') !!}
    {!! Form::text('NombreDepartamento', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Gerenciaid Field -->
<div class="col-sm-6 row text-[#101D49] dark:text-white">
    {!! Form::label('GerenciaID', 'Gerencia:') !!}

    {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
    pluck('NombreGerencia','GerenciaID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}
</div>