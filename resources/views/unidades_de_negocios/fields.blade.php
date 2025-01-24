<!-- Nombreempresa Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombreEmpresa', 'Nombre empresa:') !!}
    {!! Form::text('NombreEmpresa', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Rfc Field -->
<div class="form-group col-sm-6">
    {!! Form::label('RFC', 'Rfc:') !!}
    {!! Form::text('RFC', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Direccion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Direccion', 'Direccion:') !!}
    {!! Form::text('Direccion', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Numtelefono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NumTelefono', 'Num. telefono:') !!}
    {!! Form::text('NumTelefono', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>