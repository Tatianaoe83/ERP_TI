<!-- Nombredepartamento Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombreDepartamento', 'Nombre departamento:') !!}
    {!! Form::text('NombreDepartamento', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Gerenciaid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('GerenciaID', 'Gerencia:') !!}
    {!! Form::number('GerenciaID', null, ['class' => 'form-control']) !!}
</div>